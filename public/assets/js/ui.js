/* Perilaku UI tambahan (file baru; app.js tetap dipakai apa adanya). */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // Drawer menu (layar kecil)
  const sb = $('#sidebar'), ov = $('#navOverlay'), tg = $('#navToggle');
  function drawer(open) {
    if (!sb) return;
    sb.classList.toggle('is-open', open);
    if (ov) ov.classList.toggle('hidden', !open);
    if (tg) tg.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  tg && tg.addEventListener('click', () => drawer(!sb.classList.contains('is-open')));
  ov && ov.addEventListener('click', () => drawer(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') drawer(false); });
  window.matchMedia('(min-width: 1024px)').addEventListener('change', e => { if (e.matches) drawer(false); });

  // Menu profil (Profil Saya / Logout)
  const um = $('#userMenu'), umBtn = $('#userMenuBtn'), umList = $('#userMenuList');
  function userMenu(open) {
    if (!umBtn || !umList) return;
    umList.hidden = !open;
    umBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  if (um && umBtn && umList) {
    umBtn.addEventListener('click', e => { e.stopPropagation(); userMenu(umList.hidden); });
    document.addEventListener('click', e => { if (!um.contains(e.target)) userMenu(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !umList.hidden) { userMenu(false); umBtn.focus(); } });
    umList.addEventListener('keydown', e => {
      if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
      const it = $$('[role=menuitem]', umList), i = it.indexOf(document.activeElement);
      e.preventDefault(); it[(i + (e.key === 'ArrowDown' ? 1 : -1) + it.length) % it.length].focus();
    });
    umBtn.addEventListener('keydown', e => { if (e.key === 'ArrowDown') { e.preventDefault(); userMenu(true); const f = $('[role=menuitem]', umList); f && f.focus(); } });
  }

  // Label status pada tombol switch (Aktif / Nonaktif)
  $$('.switch input[type=checkbox]').forEach(cb => {
    const lbl = cb.closest('.switch-row') && $('.sw-state', cb.closest('.switch-row'));
    if (!lbl) return;
    const sync = () => { lbl.textContent = cb.checked ? lbl.dataset.on : lbl.dataset.off; lbl.classList.toggle('on', cb.checked); };
    cb.addEventListener('change', sync); sync();
  });

  // Filter yang bisa dibuka-tutup di layar kecil
  $$('.filters').forEach(f => {
    const b = $('.filters-toggle', f);
    if (!b) return;
    b.setAttribute('aria-expanded', f.classList.contains('is-open') ? 'true' : 'false');
    b.addEventListener('click', () => {
      const o = f.classList.toggle('is-open');
      b.setAttribute('aria-expanded', o ? 'true' : 'false');
    });
  });

  // Kalender kehadiran: warna sel mengikuti status
  const syncCal = () => $$('.cal-d').forEach(d => {
    const s = $('select.att', d);
    if (s) d.dataset.status = s.value;
  });
  if ($('.cal')) {
    syncCal();
    document.addEventListener('change', e => { if (e.target.matches && e.target.matches('select.att')) syncCal(); });
    ['bulkWeekdays', 'bulkApply'].forEach(id => { const b = document.getElementById(id); b && b.addEventListener('click', syncCal); });
    // klik baris hari = centang (nyaman di layar sentuh)
  }

  // Filter: kirim otomatis saat pilihan berubah, chip filter aktif, pintasan "/" untuk pencarian
  $$('.filters').forEach(f => {
    const form = $('.filters-body', f);
    if (!form) return;
    const reset = $$('a.btn', form).find(a => /reset/i.test(a.textContent));
    form.addEventListener('change', e => {
      const t = e.target;
      if (t.matches('select, input[type=date], input[type=month], input[type=checkbox]')) form.requestSubmit();
    });
    // chip
    const box = document.createElement('div'); box.className = 'chips';
    f.appendChild(box);
    const fields = $$('input[name], select[name]', form).filter(el => el.type !== 'hidden' && el.name !== '_csrf');
    const label = el => { const l = el.id && $('label[for="' + el.id + '"]', form); return l ? l.textContent.trim() : el.name; };
    const text = el => el.tagName === 'SELECT' ? el.selectedOptions[0].textContent.trim() : (el.type === 'checkbox' ? '' : el.value);
    let n = 0;
    fields.forEach(el => {
      const on = el.type === 'checkbox' ? el.checked : el.value !== '';
      if (!on) return; n++;
      const c = document.createElement('span'); c.className = 'chip';
      const v = text(el);
      c.innerHTML = '<span></span><button type="button" aria-label="Hapus filter"></button>';
      c.firstChild.innerHTML = el.type === 'checkbox' ? '<b></b>' : label(el).replace(/[<>&]/g, '') + ': <b></b>';
      c.querySelector('b').textContent = el.type === 'checkbox' ? el.closest('label').textContent.trim() : v;
      const x = c.querySelector('button'); x.textContent = '\u00d7';
      x.addEventListener('click', () => { if (el.type === 'checkbox') el.checked = false; else el.value = ''; form.requestSubmit(); });
      box.appendChild(c);
    });
    if (n > 1 && reset) { const b = document.createElement('a'); b.className = 'chip-clear'; b.href = reset.href; b.textContent = 'Hapus semua'; box.appendChild(b); }
    if (n) form.classList.add('has-chips');
  });
  document.addEventListener('keydown', e => {
    if (e.key !== '/' || e.ctrlKey || e.metaKey || /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)) return;
    const q = $('.filters .search input'); if (q) { e.preventDefault(); q.focus(); }
  });
})();
