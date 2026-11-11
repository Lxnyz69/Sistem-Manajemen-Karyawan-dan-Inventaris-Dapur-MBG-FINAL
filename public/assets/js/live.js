/* Live: halaman memperbarui diri tanpa refresh + lonceng notifikasi.
 * - Polling ringan /live/ping (token data + notifikasi). Berhenti saat tab tersembunyi, lanjut seketika saat dibuka.
 * - Bila token berubah: halaman yang sama diambil ulang, lalu DOM "di-morph" (hanya bagian yang berbeda disentuh),
 *   jadi fokus, isian formulir, dan pencarian pengguna tidak hilang. Bila pengguna sedang mengisi formulir, pembaruan
 *   ditunda sampai selesai.
 * - Tandai bagian yang dikelola JS lain dengan atribut data-live-ignore (tidak disentuh sama sekali).
 */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var root = $('#liveRoot');
  var bell = $('#notif');
  if (!root && !bell) return;

  var meta = function (n) { var m = $('meta[name="' + n + '"]'); return m ? m.content : ''; };
  var BASE = meta('app-base');
  var CSRF = meta('csrf-token');
  var liveOn = !!(root && root.dataset.live === '1');
  var liveNotif = !!(root && root.dataset.liveNotif === '1');
  var INTERVAL = 15000, MAX_WAIT = 60000;   // INTERVAL hanya dipakai bila SSE tidak tersedia (cadangan)
  var es = null, streamErrors = 0, useStream = !!window.EventSource, lastLoc = null;

  var token = null, pendingToken = null, nk = '', timer = null, failures = 0, retries = 0;
  var wantRefresh = false, refreshing = false, refreshRetryAt = 0, skew = 0, firstNotif = true, knownMax = 0;

  /* ---------------------------------------------------------------- indikator "Live" */
  var pill = $('#livePill');
  var PILL = { on: ['Live', 'Data diperbarui otomatis'], off: ['Terputus', 'Koneksi terputus, mencoba lagi…'], wait: ['Menunggu', 'Ada data baru. Selesaikan isian Anda agar halaman diperbarui.'] };
  function setPill(s) {
    if (!pill || !liveOn) return;
    pill.hidden = false; pill.dataset.state = s;
    pill.lastChild.nodeValue = PILL[s][0]; pill.title = PILL[s][1];
  }

  /* ---------------------------------------------------------------- polling */
  function schedule(ms) { clearTimeout(timer); timer = setTimeout(tick, ms); }
  function tick() {
    if (document.hidden) { schedule(INTERVAL); return Promise.resolve(); }
    return fetch(BASE + '/live/ping?nk=' + encodeURIComponent(nk), { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json', 'X-Live': '1' } })
      .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
      .then(function (d) { failures = 0; apply(d); })
      .catch(function () { failures++; setPill('off'); })
      .then(function () { return wantRefresh ? tryRefresh(false) : null; })
      .then(function () { schedule(failures ? Math.min(MAX_WAIT, INTERVAL * (1 + failures)) : INTERVAL); });
  }
  function apply(d) {
    if (!wantRefresh) setPill('on');
    skew = d.now - Math.floor(Date.now() / 1000);
    window.dispatchEvent(new CustomEvent('live:sync'));
    if (d.n) handleNotif(d.n);
    if (d.c) handleCounts(d.c);
    if (d.l !== undefined && d.l !== lastLoc) { var first = lastLoc === null; lastLoc = d.l; if (!first) window.dispatchEvent(new CustomEvent('live:loc')); }
    if (liveOn) {
      if (token === null) token = d.t;
      else if (d.t !== token) { wantRefresh = true; pendingToken = d.t; }
    }
  }
  /* Utama: Server-Sent Events (data masuk seketika). Cadangan: polling tiap 2 detik. */
  function openStream() {
    if (!useStream || es || document.hidden) return;
    es = new EventSource(BASE + '/live/stream?nk=' + encodeURIComponent(nk));
    es.onmessage = function (e) {
      streamErrors = 0; failures = 0;
      try { apply(JSON.parse(e.data)); } catch (x) { return; }
      if (wantRefresh) tryRefresh(false);
    };
    es.onerror = function () {
      setPill('off');
      if (++streamErrors >= 3) { es.close(); es = null; useStream = false; schedule(INTERVAL); }
    };
  }
  function closeStream() { if (es) { es.close(); es = null; } }
  setInterval(function () { if (wantRefresh && !refreshing && !document.hidden && Date.now() >= refreshRetryAt) tryRefresh(false); }, 1000);   // coba lagi bila pembaruan sempat ditunda
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) { closeStream(); return; }
    if (useStream) openStream(); else { clearTimeout(timer); tick(); }
  });

  /* ---------------------------------------------------------------- apakah pengguna sedang mengisi? */
  function dirty(el) {
    var t = (el.type || '').toLowerCase();
    if (el.tagName === 'SELECT') return Array.prototype.some.call(el.options, function (o) { return o.selected !== o.defaultSelected; });
    if (t === 'checkbox' || t === 'radio') return el.checked !== el.defaultChecked;
    if (t === 'file') return el.files && el.files.length > 0;
    if (['hidden', 'submit', 'button', 'password', 'reset', 'image'].indexOf(t) > -1) return false;
    return el.value !== el.defaultValue;
  }
  function busy() {
    var m = $('#confirmModal'); if (m && !m.classList.contains('hidden')) return true;
    var a = document.activeElement;
    if (a && root.contains(a) && a.form && (a.form.method || '').toLowerCase() === 'post' && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName) && ['checkbox', 'radio', 'submit', 'button'].indexOf(a.type) < 0) return true;
    return $$('form[method="post" i]', root).some(function (f) {
      if (f.closest('[data-live-ignore]') || f.dataset.sent) return false;
      return Array.prototype.some.call(f.elements, function (el) { return !el.closest('[data-live-ignore]') && dirty(el); });
    });
  }

  /* ---------------------------------------------------------------- ambil ulang halaman + morph */
  function tryRefresh(force) {
    if (!root || !liveOn || refreshing) return Promise.resolve();
    if (!force && busy()) { setPill('wait'); return Promise.resolve(); }
    refreshing = true; var tk = pendingToken;
    return fetch(location.href, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Live': '1', 'Accept': 'text/html' } })
      .then(function (r) {
        var ct = r.headers.get('content-type') || '';
        if (!r.ok || ct.indexOf('text/html') < 0) throw new Error('bad');
        if (r.redirected && new URL(r.url).pathname !== location.pathname) throw new Error('moved');
        return r.text();
      })
      .then(function (html) {
        var fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('liveRoot');
        if (!fresh) throw new Error('noroot');
        if (!force && busy()) { setPill('wait'); return; }
        flashes = 0; morphChildren(root, fresh);
        if (tk) token = tk;
        wantRefresh = false; retries = 0; refreshRetryAt = 0; setPill('on');
        afterMorph();
      })
      .catch(function () {
        retries++;
        // Jangan menganggap token sudah diterapkan setelah kegagalan jaringan.
        // Pertahankan update yang tertunda lalu coba ulang dengan jeda bertahap.
        refreshRetryAt = Date.now() + Math.min(30000, 1000 * Math.pow(2, Math.min(retries - 1, 5)));
        setPill('off');
      })
      .then(function () { refreshing = false; });
  }
  function afterMorph() {
    $$('input[data-filter]', root).forEach(function (i) { if (i.value) i.dispatchEvent(new Event('input', { bubbles: true })); });
    var ss = $('#schoolSearch'); if (ss && ss.value) ss.dispatchEvent(new Event('input'));
    tickTimes();
    window.dispatchEvent(new CustomEvent('live:updated'));
  }

  /* ---------------------------------------------------------------- morph DOM */
  var flashes = 0;
  var SKIP_ATTR = { 'open': 1, 'aria-expanded': 1, 'data-dir': 1, 'data-sent': 1, 'data-ok': 1 };
  var KEEP_CLASS = ['is-hidden', 'is-open', 'has-chips', 'live-flash', 'opacity-60'];
  function mark(el) {
    if (!el || el.nodeType !== 1 || flashes > 60) return;
    flashes++; el.classList.add('live-flash');
    setTimeout(function () { el.classList.remove('live-flash'); }, 1900);
  }
  function kids(el) {
    return Array.prototype.filter.call(el.childNodes, function (n) {
      if (n.nodeType === 1) return !n.hasAttribute('data-live-ignore') && n.tagName !== 'SCRIPT' && n.tagName !== 'STYLE';
      return n.nodeType === 3 && n.nodeValue.trim() !== '';
    });
  }
  function keyOf(n) { return n.nodeType === 1 ? n.getAttribute('data-live-key') : null; }
  function morphChildren(from, to) {
    var A = kids(from), B = kids(to), i, c, cur;
    if (!A.some(keyOf) && !B.some(keyOf)) {   // tanpa kunci: cocokkan menurut urutan
      var n = Math.min(A.length, B.length);
      for (i = 0; i < n; i++) morphNode(A[i], B[i]);
      for (i = A.length - 1; i >= B.length; i--) A[i].remove();
      for (i = n; i < B.length; i++) { c = document.importNode(B[i], true); from.appendChild(c); mark(c); }
      return;
    }
    // Ada data-live-key (mis. kartu tugas): elemen dicocokkan menurut kunci dan DIPINDAHKAN bila urutannya berubah,
    // sehingga isian/catatan yang sedang diketik tetap menempel pada kartunya.
    var byKey = {}; A.forEach(function (a) { var k = keyOf(a); if (k) byKey[k] = a; });
    for (i = 0; i < B.length; i++) {
      var b = B[i], k = keyOf(b), a = k ? byKey[k] : null; cur = kids(from)[i];
      if (a) {
        delete byKey[k];
        if (cur !== a) { from.insertBefore(a, cur || null); mark(a); }
        morphNode(a, b);
      } else if (!k && cur && !keyOf(cur)) morphNode(cur, b);
      else { c = document.importNode(b, true); from.insertBefore(c, cur || null); mark(c); }
    }
    var rest = kids(from); for (i = rest.length - 1; i >= B.length; i--) rest[i].remove();
  }
  function syncAttrs(a, b) {
    var tag = a.tagName, i, at;
    var skip = function (name) {
      if (SKIP_ATTR[name]) return true;
      if (tag === 'TR' && (name === 'style' || name === 'hidden')) return true;   // diatur filter pencarian sisi klien
      if ((tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') && (name === 'value' || name === 'checked')) return true;
      if (tag === 'BUTTON' && name === 'disabled') return true;
      return false;
    };
    for (i = 0; i < b.attributes.length; i++) {
      at = b.attributes[i];
      if (skip(at.name)) continue;
      if (at.name === 'class') {
        var want = {}; b.className.split(/\s+/).forEach(function (c) { if (c) want[c] = 1; });
        KEEP_CLASS.forEach(function (c) { if (a.classList.contains(c)) want[c] = 1; });
        var next = Object.keys(want).join(' ');
        if (a.className.split(/\s+/).filter(Boolean).sort().join(' ') !== Object.keys(want).sort().join(' ')) a.className = next;
      } else if (a.getAttribute(at.name) !== at.value) a.setAttribute(at.name, at.value);
    }
    for (i = a.attributes.length - 1; i >= 0; i--) {
      at = a.attributes[i];
      if (!b.hasAttribute(at.name) && !skip(at.name) && at.name !== 'class') a.removeAttribute(at.name);
    }
    if (!b.hasAttribute('class') && a.className && !KEEP_CLASS.some(function (c) { return a.classList.contains(c); })) a.removeAttribute('class');
  }
  function morphNode(a, b) {
    if (a.nodeType !== b.nodeType || (a.nodeType === 1 && a.tagName !== b.tagName)) { var c = document.importNode(b, true); a.replaceWith(c); mark(c); return; }
    if (a.nodeType === 3) { if (a.nodeValue !== b.nodeValue) { a.nodeValue = b.nodeValue; mark(a.parentElement); } return; }
    syncAttrs(a, b);
    var t = a.tagName, focused = a === document.activeElement;
    if (t === 'INPUT') {
      var ty = (a.type || '').toLowerCase();
      if (ty === 'hidden') { a.value = b.getAttribute('value') || ''; return; }
      if (ty === 'checkbox' || ty === 'radio') { var d1 = a.checked !== a.defaultChecked, nc = b.hasAttribute('checked'); a.defaultChecked = nc; if (!d1 && !focused) a.checked = nc; return; }
      if (['file', 'password', 'submit', 'button', 'reset', 'image'].indexOf(ty) > -1) return;
      var d2 = a.value !== a.defaultValue, nv = b.getAttribute('value') || ''; a.defaultValue = nv; if (!d2 && !focused) a.value = nv; return;
    }
    if (t === 'TEXTAREA') { var d3 = a.value !== a.defaultValue, tv = b.textContent; if (a.defaultValue !== tv) a.defaultValue = tv; if (!d3 && !focused) a.value = tv; return; }
    if (t === 'SELECT') {
      var d4 = dirty(a); morphChildren(a, b);
      if (!d4 && !focused) { var idx = 0; Array.prototype.forEach.call(b.options, function (o, i) { if (o.hasAttribute('selected')) idx = i; }); a.selectedIndex = idx; }
      return;
    }
    morphChildren(a, b);
  }

  /* ---------------------------------------------------------------- penanda angka menu samping */
  function handleCounts(c) {
    $$('a.nav-link').forEach(function (a) {
      var k = a.pathname; if (!(k in c)) return;
      var b = $('.nav-count', a), n = c[k];
      if (n > 0) { if (!b) { b = document.createElement('span'); b.className = 'nav-count'; a.appendChild(b); } if (b.textContent !== String(n)) b.textContent = n; }
      else if (b) b.remove();
    });
  }

  /* ---------------------------------------------------------------- waktu relatif */
  function relTime(ts) {
    var s = Math.floor(Date.now() / 1000) + skew - ts;
    if (s < 45) return 'baru saja';
    if (s < 3600) return Math.max(1, Math.round(s / 60)) + ' mnt lalu';
    if (s < 86400) return Math.floor(s / 3600) + ' jam lalu';
    return Math.floor(s / 86400) + ' hari lalu';
  }
  function tickTimes() { $$('time[data-ts] .t-rel').forEach(function (el) { var t = relTime(+el.parentNode.dataset.ts); if (el.textContent !== t) el.textContent = t; }); }
  setInterval(tickTimes, 20000);

  /* ---------------------------------------------------------------- lonceng notifikasi */
  var nBtn = $('#notifBtn'), nPanel = $('#notifPanel'), nList = $('#notifList'), nEmpty = $('#notifEmpty'), nDot = $('#notifDot'), nAll = $('#notifReadAll');
  var items = [];
  var ICON = {
    low_stock: ['#b3261e', '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>'],
    stock_request: ['#8a5a00', '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>'],
    stock_decision: ['#17794a', '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="M22 4 12 14l-3-3"/>'],
    delivery: ['#1d5fa8', '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
    delivery_assigned: ['#1d5fa8', '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
    account: ['#4a5565', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>']
  };
  function icon(type) {
    var i = ICON[type] || ['#4a5565', '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>'];
    return '<span class="notif-ico" style="color:' + i[0] + '"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + i[1] + '</svg></span>';
  }
  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  function renderNotif() {
    if (!nList) return;
    nList.innerHTML = items.slice(0, 10).map(function (n) {
      return '<li><a class="notif-item' + (n.read ? '' : ' is-unread') + '" href="' + esc(n.link || '#') + '" data-nid="' + n.id + '">' + icon(n.type) +
        '<span class="notif-txt"><b>' + esc(n.title) + '</b>' + (n.body ? '<span class="notif-body">' + esc(n.body) + '</span>' : '') +
        '<time data-ts="' + n.ts + '"><span class="t-clock">' + esc(n.label) + '</span> · <span class="t-rel">' + relTime(n.ts) + '</span></time></span>' +
        (n.read ? '' : '<i class="notif-unread" aria-label="Belum dibaca"></i>') + '</a></li>';
    }).join('');
    if (nEmpty) nEmpty.hidden = items.length > 0;
    if (nAll) nAll.hidden = !items.some(function (n) { return !n.read; });
  }
  function setDot(n) { if (!nDot) return; nDot.hidden = !(n > 0); nDot.textContent = n > 99 ? '99+' : n; if (nBtn) nBtn.setAttribute('aria-label', n > 0 ? 'Notifikasi, ' + n + ' belum dibaca' : 'Notifikasi'); }
  function toast(n) {
    var box = $('#toasts'); if (!box) return;
    var t = document.createElement('div'); t.className = 'toast t-info';
    var m = document.createElement('span'); m.className = 'm';
    var b = document.createElement('b'); b.textContent = n.title; m.appendChild(b);
    if (n.body) { m.appendChild(document.createElement('br')); m.appendChild(document.createTextNode(n.body)); }
    var x = document.createElement('button'); x.type = 'button'; x.setAttribute('aria-label', 'Tutup'); x.innerHTML = '&times;';
    x.onclick = function () { t.remove(); };
    if (n.link) { m.style.cursor = 'pointer'; m.onclick = function () { location.href = n.link; }; }
    t.appendChild(m); t.appendChild(x); box.appendChild(t);
    setTimeout(function () { t.remove(); }, 8000);
  }
  function handleNotif(n) {
    setDot(n.unread);
    var changed = n.key !== nk; nk = n.key;
    if (n.items) {
      var fresh = n.items.filter(function (i) { return i.id > knownMax && !i.read; });
      knownMax = Math.max.apply(null, [knownMax].concat(n.items.map(function (i) { return i.id; })));
      if (!firstNotif) fresh.slice(0, 3).forEach(toast);
      items = n.items; renderNotif();
    }
    firstNotif = false;
    if (changed && liveNotif && liveOn) { wantRefresh = true; if (pendingToken === null) pendingToken = token; }
  }
  function post(data) {
    var body = new URLSearchParams(data).toString();
    return fetch(bell ? bell.dataset.readUrl : BASE + '/notifikasi/baca', { method: 'POST', credentials: 'same-origin', keepalive: true,
      headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json', 'X-Live': '1', 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
      .then(function (r) { return r.json(); }).catch(function () { return null; });
  }
  function markLocal(id) { items.forEach(function (n) { if (id === 'all' || n.id === id) n.read = true; }); }
  if (bell) {
    try { var boot = JSON.parse($('#notifBoot').textContent); items = boot.items || []; nk = boot.key; skew = boot.now - Math.floor(Date.now() / 1000); knownMax = items.reduce(function (m, i) { return Math.max(m, i.id); }, 0); renderNotif(); } catch (e) { /* abaikan */ }
    var open = function (o) { nPanel.hidden = !o; nBtn.setAttribute('aria-expanded', o ? 'true' : 'false'); if (o) tickTimes(); };
    nBtn.addEventListener('click', function (e) { e.stopPropagation(); open(nPanel.hidden); });
    document.addEventListener('click', function (e) { if (!nPanel.hidden && !bell.contains(e.target)) open(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !nPanel.hidden) { open(false); nBtn.focus(); } });
    if (nAll) nAll.addEventListener('click', function () { markLocal('all'); renderNotif(); setDot(0); post({ all: 1 }).then(function (r) { if (r) { nk = r.key; setDot(r.unread); } }); });
  }
  // Klik notifikasi (lonceng maupun halaman semua notifikasi): tandai dibaca lalu buka tautannya.
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-nid]'); if (!a) return;
    var id = +a.dataset.nid, href = a.getAttribute('href'), go = href && href !== '#';
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
    e.preventDefault();
    var wasUnread = a.classList.contains('is-unread');
    if (wasUnread) { a.classList.remove('is-unread'); var u = $('.notif-unread', a); if (u) u.remove(); markLocal(id); }
    var done = function () { if (go) location.href = href; };
    if (!wasUnread) return done();
    var finished = false, fin = function (r) { if (finished) return; finished = true; if (r) { nk = r.key; setDot(r.unread); } done(); };
    post({ id: id }).then(fin); setTimeout(fin, 700);
  });

  window.Live = { refreshNow: function () {   // segera ambil data terbaru (menunggu bila refresh lain sedang berjalan)
    wantRefresh = true;
    return new Promise(function (res) { (function go() { if (refreshing) return setTimeout(go, 60); tryRefresh(true).then(res); })(); });
  }, poll: tick };
  var publicRefresh = root && liveOn ? parseInt(root.dataset.liveRefresh || '0', 10) : 0;
  if (publicRefresh > 0) {
    // Landing dirender dari server, lalu disinkronkan segera dan berkala sebagai
    // pengaman tambahan bila token/SSE gagal atau halaman tersaji dari cache.
    setTimeout(function () { if (!document.hidden) window.Live.refreshNow(); }, 700);
    setInterval(function () { if (!document.hidden) window.Live.refreshNow(); }, Math.max(5000, publicRefresh));
  }
  tickTimes();
  if (useStream) openStream(); else schedule(INTERVAL);
})();
