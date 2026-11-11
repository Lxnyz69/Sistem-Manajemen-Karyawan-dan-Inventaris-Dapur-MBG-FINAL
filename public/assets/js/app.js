(function () {
  const $ = (s, r = document) => r.querySelector(s);

  // Toast auto-hide
  document.querySelectorAll('.toast').forEach(t => setTimeout(() => t.remove(), 6000));

  // Sidebar: drawer (mobile) & collapse (desktop)
  const sb = $('#sidebar'), ov = $('#navOverlay') || $('#overlay');
  const openDrawer = o => { if (!sb) return; sb.classList.toggle('is-open', !!o); ov && ov.classList.toggle('hidden', !o); };
  // Tombol #navToggle ditangani di ui.js (jangan dipasang dua kali: klik jadi buka lalu langsung tutup).
  const menuBtn = !$('#navToggle') && $('#menuBtn');
  menuBtn && menuBtn.addEventListener('click', () => openDrawer(!sb.classList.contains('is-open')));
  ov && ov.addEventListener('click', () => openDrawer(false));
  const cb = $('#collapseBtn');
  if (cb) {
    if (localStorage.getItem('sb-collapsed') === '1') document.body.classList.add('collapsed');
    cb.addEventListener('click', () => {
      document.body.classList.toggle('collapsed');
      localStorage.setItem('sb-collapsed', document.body.classList.contains('collapsed') ? '1' : '0');
    });
  }

  // Modal konfirmasi
  const modal = $('#confirmModal');
  let onYes = null;
  function ask(text, cb) {
    $('#confirmText').textContent = text; onYes = cb;
    modal.classList.remove('hidden'); modal.classList.add('flex');
  }
  function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); onYes = null; }
  $('#confirmNo') && $('#confirmNo').addEventListener('click', closeModal);
  $('#confirmYes') && $('#confirmYes').addEventListener('click', () => { const f = onYes; closeModal(); f && f(); });

  // Submit: konfirmasi + cegah kirim ganda + loading state
  document.addEventListener('submit', e => {
    const f = e.target;
    const cmsg = (e.submitter && e.submitter.dataset.confirm) || f.dataset.confirm;
    if (cmsg && !f.dataset.ok) {
      e.preventDefault();
      const sub = e.submitter;
      ask(cmsg, () => { f.dataset.ok = '1'; sub ? f.requestSubmit(sub) : f.requestSubmit(); });
      return;
    }
    if (f.dataset.sent) { e.preventDefault(); return; }
    f.dataset.sent = '1';
    setTimeout(() => f.querySelectorAll('button[type=submit],button:not([type])').forEach(b => {
      b.disabled = true; b.classList.add('opacity-60');
      if (!b.dataset.keep) b.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-spinner fa-spin"></i> ');
    }), 10);
  });
  window.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });

  // Foto: dikecilkan otomatis di browser (maks. 1600 px, <= ~1,6 MB) supaya foto besar dari HP tetap bisa diunggah.
  // <input type=file accept="image/..." data-preview="#idImg"> (data-preview opsional)
  const IMG_MAX_BYTES = 1.6 * 1024 * 1024, IMG_MAX_DIM = 1600, IMG_HARD_LIMIT = 4.5 * 1024 * 1024;
  const loadImg = f => new Promise((res, rej) => {
    const u = URL.createObjectURL(f), i = new Image();
    i.onload = () => { URL.revokeObjectURL(u); res(i); };
    i.onerror = () => { URL.revokeObjectURL(u); rej(new Error('img')); };
    i.src = u;
  });
  async function shrinkImage(file) {
    let img; try { img = await loadImg(file); } catch (e) { return file; }
    const w0 = img.naturalWidth, h0 = img.naturalHeight;
    if (!w0 || !h0 || (file.size <= IMG_MAX_BYTES && Math.max(w0, h0) <= IMG_MAX_DIM)) return file;
    const png = file.type === 'image/png';
    let scale = Math.min(1, IMG_MAX_DIM / Math.max(w0, h0));
    for (let n = 0; n < 6; n++) {
      const c = document.createElement('canvas');
      c.width = Math.max(1, Math.round(w0 * scale)); c.height = Math.max(1, Math.round(h0 * scale));
      const ctx = c.getContext('2d');
      if (!ctx) return file;
      if (!png) { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height); }
      ctx.drawImage(img, 0, 0, c.width, c.height);
      const blob = await new Promise(r => c.toBlob(r, png ? 'image/png' : 'image/jpeg', 0.85));
      if (!blob) return file;
      if (blob.size <= IMG_MAX_BYTES) return new File([blob], file.name.replace(/\.[^.]+$/, '') + (png ? '.png' : '.jpg'), { type: blob.type });
      scale *= 0.8; // masih besar: kecilkan lagi
    }
    return file;
  }
  document.querySelectorAll('input[type=file][accept^="image"]').forEach(inp => {
    inp.addEventListener('change', async () => {
      let f = inp.files && inp.files[0];
      if (!f) return;
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { alert('Gunakan gambar JPG, PNG, atau WebP.'); inp.value = ''; return; }
      const btns = inp.form ? [...inp.form.querySelectorAll('button:not([type=button]), input[type=submit]')] : [];
      btns.forEach(b => { b.disabled = true; });
      try {
        const small = await shrinkImage(f);
        if (small !== f && window.DataTransfer) {
          try { const dt = new DataTransfer(); dt.items.add(small); inp.files = dt.files; f = small; } catch (e) { /* tetap pakai file asli */ }
        }
      } finally { btns.forEach(b => { b.disabled = false; }); }
      if (f.size > IMG_HARD_LIMIT) { alert('Ukuran foto masih terlalu besar. Pilih foto lain.'); inp.value = ''; return; }
      const img = inp.dataset.preview ? $(inp.dataset.preview) : null;
      if (img) { img.src = URL.createObjectURL(f); img.classList.remove('hidden'); }
    });
  });

  // Filter tabel sederhana di sisi klien: <input data-filter="#tabelId">
  document.querySelectorAll('input[data-filter]').forEach(inp => {
    inp.addEventListener('input', () => {
      const q = inp.value.toLowerCase();
      document.querySelectorAll(inp.dataset.filter + ' tbody tr').forEach(tr => { tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none'; });
    });
  });

  // Urut tabel: klik header <th data-sort>
  document.querySelectorAll('table.tbl').forEach(tb => {
    tb.querySelectorAll('th[data-sort]').forEach((th, _i) => {
      th.style.cursor = 'pointer';
      th.addEventListener('click', () => {
        const idx = [...th.parentElement.children].indexOf(th), asc = th.dataset.dir !== 'asc';
        th.dataset.dir = asc ? 'asc' : 'desc';
        const rows = [...tb.tBodies[0].rows];
        rows.sort((a, b) => {
          const x = a.cells[idx].textContent.trim(), y = b.cells[idx].textContent.trim();
          const nx = parseFloat(x.replace(/[^\d.-]/g, '')), ny = parseFloat(y.replace(/[^\d.-]/g, ''));
          const c = (!isNaN(nx) && !isNaN(ny)) ? nx - ny : x.localeCompare(y, 'id');
          return asc ? c : -c;
        });
        rows.forEach(r => tb.tBodies[0].appendChild(r));
      });
    });
  });
})();
