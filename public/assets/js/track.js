/* Peta pelacakan kurir (Leaflet + OpenStreetMap). Posisi diperbarui otomatis dari /live/lokasi. */
(function () {
  'use strict';
  var box = document.querySelector('[data-track-map]');
  if (!box) return;
  var base = (document.querySelector('meta[name="app-base"]') || {}).content || '';
  var school = box.dataset.school || '0';
  var L_JS = base + '/assets/vendor/leaflet/leaflet.js';
  var L_CSS = base + '/assets/vendor/leaflet/leaflet.css';
  var lines = {}, officeMk = null, SLOW = 20000, timerId = null, map, courierMk = {}, schoolMk = {}, lastIds = '', skew = 0, empty, listEl = document.getElementById('trackList'), subEl = document.getElementById('trackSub');

  function load(cb) {
    if (window.L) return cb();
    var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = L_CSS; document.head.appendChild(l);
    var s = document.createElement('script'); s.src = L_JS; s.onload = cb; s.onerror = function () { box.textContent = 'Peta tidak dapat dimuat (periksa koneksi internet).'; };
    document.head.appendChild(s);
  }
  var svgTruck = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
  var svgOffice = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5l8-2v18M12 9h8v12M7 8h2M7 12h2M7 16h2M15 13h2M15 17h2M2 21h20"/></svg>';
  var svgSchool = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10 12 4l9 6M5 10v9h14v-9M10 19v-5h4v5"/></svg>';
  function icon(cls, svg) { return L.divIcon({ className: '', html: '<div class="trk-pin ' + cls + '">' + svg + '</div>', iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -16] }); }
  function ago(ts) {
    var s = Math.floor(Date.now() / 1000) + skew - ts;
    return s < 10 ? 'baru saja' : s < 60 ? s + ' detik lalu' : Math.round(s / 60) + ' menit lalu';
  }
  function popup(it, who) {
    var d = document.createElement('div'); d.className = 'trk-pop';
    var b = document.createElement('b'); b.textContent = it.school; d.appendChild(b);
    var m = document.createElement('div'); m.textContent = who + ' · ' + it.menu + ' · ' + it.portions + ' porsi'; d.appendChild(m);
    if (it.ts) { var t = document.createElement('div'); t.className = 'trk-ago'; t.textContent = 'Posisi diperbarui ' + ago(it.ts); d.appendChild(t); }
    return d;
  }
  function init() {
    map = L.map(box, { zoomControl: true, attributionControl: true }).setView([-6.2, 106.82], 11);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    empty = document.createElement('div'); empty.className = 'trk-empty'; empty.textContent = 'Belum ada kurir yang sedang mengantar.'; box.appendChild(empty);
    var c = L.control({ position: 'topright' });
    c.onAdd = function () { var a = L.DomUtil.create('button', 'trk-fit'); a.type = 'button'; a.textContent = 'Pusatkan'; L.DomEvent.on(a, 'click', function (e) { L.DomEvent.stop(e); fit(true); }); return a; };
    c.addTo(map);
    // Pada HP ukuran kontainer dapat berubah setelah Leaflet selesai dibuat.
    // Hitung ulang segera agar tile tidak baru muncul setelah refresh.
    function resizeMap() { if (map && box.offsetWidth && box.offsetHeight) map.invalidateSize({ pan: false }); }
    requestAnimationFrame(resizeMap);
    setTimeout(resizeMap, 250);
    setTimeout(resizeMap, 900);
    window.addEventListener('resize', resizeMap, { passive: true });
    window.addEventListener('orientationchange', function () { setTimeout(resizeMap, 300); }, { passive: true });
    document.addEventListener('visibilitychange', function () { if (!document.hidden) setTimeout(resizeMap, 150); });
    if ('ResizeObserver' in window) new ResizeObserver(resizeMap).observe(box);
    if ('IntersectionObserver' in window) {
      var mapObserver = new IntersectionObserver(function (entries) {
        if (entries.some(function (entry) { return entry.isIntersecting; })) {
          setTimeout(resizeMap, 100);
          mapObserver.disconnect();
        }
      });
      mapObserver.observe(box);
    }
    poll();
  }
  var pts = [];
  function fit() { if (!pts.length) return; if (pts.length === 1) map.setView(pts[0], 16); else map.fitBounds(L.latLngBounds(pts), { padding: [40, 40], maxZoom: 17 }); }
  function render(d) {
    skew = d.now - Math.floor(Date.now() / 1000);
    var seen = {}, sSeen = {}; pts = [];
    (d.schools || []).forEach(function (sc) {   // semua sekolah selalu tampil
      sSeen[sc.id] = 1; var sp = [sc.lat, sc.lng]; pts.push(sp);
      var m = schoolMk[sc.id] || (schoolMk[sc.id] = L.marker(sp, { icon: icon('trk-school', svgSchool) }).addTo(map));
      m.setLatLng(sp); m.bindPopup(sc.name);
    });
    var off = d.office ? [d.office.lat, d.office.lng] : null;
    if (off) {   // lokasi kantor (hanya tampil di peta)
      pts.push(off);
      if (!officeMk) officeMk = L.marker(off, { icon: icon('trk-office', svgOffice) }).addTo(map).bindPopup('Kantor');
      officeMk.setLatLng(off);
    } else if (officeMk) { map.removeLayer(officeMk); officeMk = null; }
    var lineSeen = {};
    function line(key, a, b, opt) {
      lineSeen[key] = 1;
      if (lines[key]) lines[key].setLatLngs([a, b]); else lines[key] = L.polyline([a, b], opt).addTo(map);
    }
    d.items.forEach(function (it) {
      if (it.s_lat != null) {   // garis kantor -> sekolah tujuan, dan posisi kurir -> sekolah tujuan
        if (off) line('o' + it.id, off, [it.s_lat, it.s_lng], { color: '#6b7686', weight: 3, dashArray: '6 8', opacity: .85 });
        if (it.lat != null) line('c' + it.id, [it.lat, it.lng], [it.s_lat, it.s_lng], { color: '#1d5fa8', weight: 4, opacity: .9 });
      }
      if (it.lat != null) {
        seen[it.id] = 1; var p = [it.lat, it.lng]; pts.push(p);
        var stale = it.ts && (Math.floor(Date.now() / 1000) + skew - it.ts) > 300;
        var k = courierMk[it.id] || (courierMk[it.id] = L.marker(p, { icon: icon('trk-courier', svgTruck), zIndexOffset: 500 }).addTo(map));
        k.setLatLng(p); k.bindPopup(popup(it, 'Kurir')); k.getElement() && k.getElement().classList.toggle('is-stale', !!stale);
      }
    });
    Object.keys(lines).forEach(function (k) { if (!lineSeen[k]) { map.removeLayer(lines[k]); delete lines[k]; } });
    Object.keys(courierMk).forEach(function (id) { if (!seen[id]) { map.removeLayer(courierMk[id]); delete courierMk[id]; } });
    Object.keys(schoolMk).forEach(function (id) { if (!sSeen[id]) { map.removeLayer(schoolMk[id]); delete schoolMk[id]; } });
    var ids = Object.keys(seen).sort().join(',') + '|' + Object.keys(sSeen).sort().join(',');
    if (ids !== lastIds) { lastIds = ids; fit(); }
    var live = Object.keys(seen).length;
    empty.hidden = live > 0;
    empty.textContent = d.items.length ? 'Kurir sedang dalam perjalanan, posisi GPS belum dibagikan.' : 'Belum ada kurir yang sedang mengantar.';
    if (subEl) subEl.textContent = d.items.length ? d.items.length + ' pengantaran berjalan' : 'Tidak ada pengantaran berjalan';
    if (listEl) {
      listEl.innerHTML = '';
      d.items.forEach(function (it) {
        var li = document.createElement('li');
        var s = document.createElement('b'); s.textContent = it.school; li.appendChild(s);
        var t = document.createElement('span'); t.textContent = it.menu + ' · ' + it.portions + ' porsi · ' + (it.ts ? 'posisi ' + ago(it.ts) : 'posisi belum dibagikan') + (it.eta ? ' · estimasi tiba ' + it.eta : ''); li.appendChild(t);
        listEl.appendChild(li);
      });
    }
  }
  function poll() {
    clearTimeout(timerId);
    if (document.hidden) { timerId = setTimeout(poll, SLOW); return; }
    fetch(base + '/live/lokasi?school=' + encodeURIComponent(school), { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Live': '1' } })
      .then(function (r) { return r.ok ? r.json() : null; }).then(function (d) { if (d) render(d); })
      .catch(function () {}).then(function () { timerId = setTimeout(poll, SLOW); });
  }
  window.addEventListener('live:loc', function () { if (map) poll(); });   // kurir bergerak -> peta langsung bergeser
  load(init);
})();
