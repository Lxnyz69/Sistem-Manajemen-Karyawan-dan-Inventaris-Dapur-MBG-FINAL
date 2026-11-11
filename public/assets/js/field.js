/* Halaman Tugas Kurir: GPS WAJIB aktif (halaman terkunci sampai lokasi didapat), ikuti pergerakan,
 * kirim posisi ke peta selama "Dalam Pengantaran", dan ubah status tugas. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var meta = function (n) { var m = $('meta[name="' + n + '"]'); return m ? m.content : ''; };
  var BASE = meta('app-base'), CSRF = meta('csrf-token');
  var bar = $('#gpsBar'), gate = $('#gpsGate');
  if (!bar || !gate) return;
  var txt = $('#gpsText', bar), gMsg = $('#gateMsg'), gTitle = $('#gateTitle'), gSteps = $('#gateSteps'), gBtn = $('#gateBtn');
  var watchId = null, last = null, lastFix = 0, lastSent = 0, lastSentPos = null, timer = null, wake = null, sending = false, denied = false;

  function transit() { return $$('[data-delivery][data-status="in_transit"]').length; }
  function dist(a, b) { var R = 6371000, r = Math.PI / 180, dLa = (b.lat - a.lat) * r, dLn = (b.lng - a.lng) * r, x = Math.sin(dLa / 2) * Math.sin(dLa / 2) + Math.cos(a.lat * r) * Math.cos(b.lat * r) * Math.sin(dLn / 2) * Math.sin(dLn / 2); return 2 * R * Math.asin(Math.sqrt(x)); }
  function setBar(cls, msg) { bar.dataset.state = cls; txt.textContent = msg; }

  /* --- kunci halaman bila GPS tidak aktif --- */
  function lock(mode, title, msg, steps) {
    document.body.classList.add('gps-locked'); gate.dataset.mode = mode;
    gTitle.textContent = title; gMsg.textContent = msg;
    gSteps.innerHTML = ''; (steps || []).forEach(function (s) { var li = document.createElement('li'); li.innerHTML = s; gSteps.appendChild(li); });
    gSteps.hidden = !(steps && steps.length);
    gBtn.hidden = false;
  }
  function unlock() { document.body.classList.remove('gps-locked'); }
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  function insecure() {
    lock('off', 'Browser memblokir GPS di alamat ini', 'Alamat website ini tidak memakai HTTPS, sehingga browser tidak mengizinkan akses lokasi. Pilih salah satu cara:', [
      '<b>Disarankan:</b> minta admin mengaktifkan HTTPS (SSL) untuk website ini.',
      'Atau di <b>Chrome HP</b>: buka <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code>, isi <code>' + esc(location.origin) + '</code>, ubah ke <b>Enabled</b>, lalu <b>Relaunch</b>.',
      'Setelah itu buka ulang halaman ini dan izinkan lokasi.'
    ]);
    gBtn.hidden = true; setBar('err', 'GPS diblokir browser (bukan HTTPS)');
  }
  function deniedMsg() {
    denied = true;
    lock('off', 'Izin lokasi ditolak', 'Aktifkan izin lokasi untuk situs ini agar halaman Tugas Kurir dapat dipakai.', [
      'Ketuk ikon <b>gembok / pengaturan</b> di sebelah alamat website.',
      'Pilih <b>Izin / Permissions → Lokasi</b>, lalu ubah ke <b>Izinkan</b>.',
      'Nyalakan juga <b>Lokasi (GPS)</b> di pengaturan HP, lalu ketuk tombol di bawah.'
    ]);
    setBar('err', 'Izin lokasi ditolak');
  }

  function lockScreen() { if (navigator.wakeLock && !wake) navigator.wakeLock.request('screen').then(function (l) { wake = l; l.addEventListener('release', function () { wake = null; }); }).catch(function () {}); }

  function start() {
    if (watchId !== null) return;
    if (!navigator.geolocation) { lock('off', 'GPS tidak tersedia', 'Perangkat atau browser ini tidak mendukung GPS. Gunakan browser lain.'); gBtn.hidden = true; return; }
    if (!window.isSecureContext) return insecure();
    denied = false; setBar('wait', 'Meminta izin lokasi…');
    lock('wait', 'Menunggu GPS…', 'Izinkan akses lokasi pada permintaan browser dan pastikan GPS HP menyala.');
    gBtn.hidden = true;
    watchId = navigator.geolocation.watchPosition(function (p) {
      last = { lat: p.coords.latitude, lng: p.coords.longitude, acc: p.coords.accuracy }; lastFix = Date.now();
      setBar('on', (transit() ? 'Lokasi dibagikan ke peta' : 'GPS aktif') + ' · akurasi ±' + Math.round(last.acc) + ' m');
      unlock(); send();
    }, function (err) {
      if (err.code === 1) { stop(); deniedMsg(); }
      else setBar('wait', 'Mencari sinyal GPS…');
    }, { enableHighAccuracy: true, maximumAge: 3000, timeout: 20000 });
    timer = setInterval(function () {
      if (!lastFix || Date.now() - lastFix > 60000) { lock('wait', 'Sinyal GPS hilang', 'Pastikan GPS HP menyala dan Anda berada di area terbuka. Halaman akan terbuka otomatis saat lokasi kembali.'); gBtn.hidden = true; setBar('wait', 'Sinyal GPS hilang'); }
      send();
    }, 5000);
    lockScreen();
  }
  function stop() {
    if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
    clearInterval(timer); timer = null; last = null; lastFix = 0; lastSentPos = null;
  }
  function send() {
    if (!last || sending || !transit()) return;   // posisi dikirim ke server hanya saat sedang mengantar
    var now = Date.now();
    if (lastSentPos && dist(lastSentPos, last) < 5 && now - lastSent < 30000) return;
    sending = true;
    fetch(BASE + '/kurir/lokasi', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ lat: last.lat, lng: last.lng, acc: Math.round(last.acc) }).toString() })
      .then(function (r) { return r.json(); })
      .then(function () { lastSent = Date.now(); lastSentPos = { lat: last.lat, lng: last.lng }; })
      .catch(function () { setBar('wait', 'Sinyal internet terputus, mencoba lagi…'); })
      .then(function () { sending = false; });
  }

  gBtn.addEventListener('click', function () { stop(); start(); });
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { lockScreen(); if (watchId === null && !denied) start(); } });
  window.addEventListener('live:updated', function () { if (last) setBar('on', (transit() ? 'Lokasi dibagikan ke peta' : 'GPS aktif') + ' · akurasi ±' + Math.round(last.acc) + ' m'); });

  /* --- tombol aksi status: popup konfirmasi -> proses -> tombol berganti otomatis tanpa refresh --- */
  var LABEL = { in_transit: 'Dalam Pengantaran', arrived: 'Sampai di Sekolah', received: 'Makanan Diterima' };
  var ASK = { in_transit: 'Mulai antar', arrived: 'Sampai di sekolah', received: 'Makanan diterima' };
  var modal = $('#advModal'), pending = null, busyIds = {};
  function openConfirm(b) {
    pending = { id: b.dataset.id, next: b.dataset.next, school: b.dataset.school, portions: b.dataset.portions };
    $('#advTitle').textContent = ASK[pending.next] + '?';
    $('#advText').textContent = 'Tandai pengiriman ke ' + pending.school + ' (' + pending.portions + ' porsi) menjadi "' + LABEL[pending.next] + '". Pastikan data sudah benar.';
    $('#advYes').textContent = 'Ya, ' + ASK[pending.next].toLowerCase();
    modal.hidden = false; $('#advYes').focus();
  }
  function closeConfirm() { modal.hidden = true; pending = null; }
  $('#advNo').addEventListener('click', closeConfirm);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeConfirm(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeConfirm(); });
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act="advance"]'); if (!b || b.disabled || busyIds[b.dataset.id]) return;
    openConfirm(b);
  });
  $('#advYes').addEventListener('click', function () {
    var p = pending; if (!p || busyIds[p.id]) return; closeConfirm(); busyIds[p.id] = 1;
    var card = $('[data-delivery="' + p.id + '"]'), b = card && $('[data-act="advance"]', card), notes = card && $('textarea[name=notes]', card);
    var orig = b ? b.innerHTML : '';
    if (b) { b.disabled = true; b.classList.add('is-busy'); b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses…'; }
    var ctl = window.AbortController ? new AbortController() : null, to = setTimeout(function () { ctl && ctl.abort(); }, 20000);
    fetch(BASE + '/kurir/' + p.id + '/status', { signal: ctl ? ctl.signal : undefined, method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ status: p.next, notes: notes ? notes.value : '' }).toString() })
      .then(function (r) { return r.json().catch(function () { return { ok: false, msg: 'Sesi berakhir, muat ulang halaman.' }; }); })
      .then(function (d) {
        if (!d.ok) { toast(d.msg || 'Gagal memperbarui status.'); return window.Live.refreshNow(); }
        return window.Live.refreshNow().then(function () { toast('Status diperbarui: ' + LABEL[p.next] + '.'); send(); });
      })
      .catch(function () { toast('Gagal terhubung. Periksa sinyal lalu coba lagi.'); return window.Live.refreshNow().catch(function () {}); })
      .then(function () {
        clearTimeout(to); delete busyIds[p.id];
        // Tombol TIDAK boleh tertinggal beku: aktifkan lagi (morph tidak menyentuh atribut disabled).
        $$('.job-btn[data-act="advance"]').forEach(function (x) {
          if (busyIds[x.dataset.id]) return;
          if (x.classList.contains('is-busy') && /Memproses/.test(x.textContent)) x.innerHTML = orig;
          x.disabled = false; x.classList.remove('is-busy');
        });
      });
  });
  // tombol baru "muncul" dengan animasi setelah status berubah
  var seen = {};
  function swapFx() {
    $$('.job-btn[data-act="advance"]').forEach(function (b) {
      var k = b.dataset.id + ':' + b.dataset.next;
      if (seen[b.dataset.id] && seen[b.dataset.id] !== k) { b.classList.add('swap'); setTimeout(function () { b.classList.remove('swap'); }, 700); }
      seen[b.dataset.id] = k;
    });
  }
  window.addEventListener('live:updated', swapFx); swapFx();
  /* --- filter tugas menurut nama sekolah (otomatis saat mengetik, tetap berlaku saat data live masuk) --- */
  var fin = $('#jobFilter'), fclr = $('#jobFilterClr'), fnone = $('#jobNone');
  function applyFilter() {
    if (!fin) return;
    var q = fin.value.trim().toLowerCase(), shown = 0, cards = $$('[data-delivery]');
    cards.forEach(function (c) { var on = !q || (c.dataset.name || '').indexOf(q) > -1; c.classList.toggle('is-hidden', !on); if (on) shown++; });
    if (fclr) fclr.hidden = !q;
    if (fnone) fnone.hidden = !(q && cards.length && !shown);
  }
  if (fin) {
    fin.addEventListener('input', applyFilter);
    fclr.addEventListener('click', function () { fin.value = ''; applyFilter(); fin.focus(); });
    window.addEventListener('live:updated', applyFilter);
  }
  var syncEl = $('#fSync');
  function stamp() { if (syncEl) syncEl.textContent = 'Sinkron ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); }
  window.addEventListener('live:sync', stamp); window.addEventListener('live:updated', stamp); stamp();
  function toast(m) {
    var box = $('#toasts'); if (!box) return;
    var t = document.createElement('div'); t.className = 'toast t-info'; t.innerHTML = '<span class="m"></span>'; t.firstChild.textContent = m;
    box.appendChild(t); setTimeout(function () { t.remove(); }, 4000);
  }
  start();
})();
