<?php if (!can('pengaturan.edit')): ?>
<section class="panel wide settings-ro">
  <p class="hint" style="margin:0 0 1rem">Anda hanya memiliki akses lihat. Perubahan pengaturan hanya dapat dilakukan oleh pengguna dengan izin ubah.</p>
  <?php $on = fn($v) => '<span class="badge ' . ($v ? 'badge-green' : 'badge-gray') . '">' . ($v ? 'Aktif' : 'Nonaktif') . '</span>'; ?>
  <dl class="kv stacked">
    <?php foreach ([
      'Nama website' => setting('site_name'), 'Judul website' => setting('site_title'), 'Nama perusahaan' => setting('company_name'),
      'Prefix ID karyawan' => setting('employee_code_prefix', 'KRY'), 'Teks footer' => setting('footer_text'),
      'Titik lokasi kantor (peta)' => setting('office_lat') ? setting('office_lat') . ', ' . setting('office_lng') : '', 'Alamat' => setting('company_address'), 'WhatsApp' => setting('company_phone'), 'Email' => setting('company_email'),
    ] as $k => $val): ?>
      <div><dt class="u-small"><?= e($k) ?></dt><dd><?= e($val !== '' && $val !== null ? $val : '-') ?></dd></div>
    <?php endforeach; ?>
    <div><dt class="u-small">Warna</dt><dd class="swatches"><?php foreach (['color_primary' => 'Utama', 'color_secondary' => 'Sekunder', 'color_sidebar' => 'Menu samping', 'color_button' => 'Tombol'] as $k => $l): ?><span class="swatch"><i style="background:<?= e(setting($k, '#2563eb')) ?>"></i><?= e($l) ?> <small><?= e(setting($k, '#2563eb')) ?></small></span><?php endforeach; ?></dd></div>
    <?php if (setting('logo')): ?><div><dt class="u-small">Logo</dt><dd><img class="img-preview" src="<?= e(url(setting('logo'))) ?>" alt="Logo"></dd></div><?php endif; ?>
    <div><dt class="u-small">Mode pemeliharaan</dt><dd><?= $on(setting('maintenance') === '1') ?></dd></div>
    <div><dt class="u-small">Tombol Masuk di beranda</dt><dd><?= $on(login_button_visible()) ?></dd></div>
    <div><dt class="u-small">Status halaman pendaftaran</dt><dd><?= $on(registration_open()) ?></dd></div>
  </dl>
</section>
<?php return; endif; ?>
<form method="post" enctype="multipart/form-data" class="panel wide" action="<?= e(url('/admin/pengaturan')) ?>"><?= csrf_field() ?>
  <div class="set-section">
    <div><h2>Identitas</h2><p class="desc">Nama yang tampil di menu, halaman login, dan dokumen resmi.</p></div>
    <div class="form-grid cols-2">
      <div class="field"><label class="label" for="s1">Nama website</label><input class="input" id="s1" name="site_name" value="<?= e(setting('site_name')) ?>" required></div>
      <div class="field"><label class="label" for="s2">Judul website</label><input class="input" id="s2" name="site_title" value="<?= e(setting('site_title')) ?>"></div>
      <div class="field"><label class="label" for="s3">Nama perusahaan</label><input class="input" id="s3" name="company_name" value="<?= e(setting('company_name')) ?>"><p class="hint">Dipakai pada dokumen resmi seperti slip gaji.</p></div>
      <div class="field"><label class="label" for="s4">Prefix ID karyawan</label><input class="input" id="s4" name="employee_code_prefix" value="<?= e(setting('employee_code_prefix', 'KRY')) ?>" pattern="[A-Z]{2,6}"><p class="hint">2–6 huruf kapital, contoh KRY.</p></div>
      <div class="field span-2"><label class="label" for="s5">Teks footer</label><input class="input" id="s5" name="footer_text" value="<?= e(setting('footer_text')) ?>"></div>
    </div>
  </div>
  <div class="set-section">
    <div><h2>Kontak perusahaan</h2><p class="desc">Tampil pada kop slip gaji.</p></div>
    <div class="form-grid cols-2">
      <div class="field span-2"><label class="label" for="c1">Alamat</label><textarea class="input" id="c1" name="company_address" rows="2"><?= e(setting('company_address')) ?></textarea></div>
      <div class="field"><label class="label" for="c2">WhatsApp</label><input class="input" id="c2" name="company_phone" value="<?= e(setting('company_phone')) ?>"></div>
      <div class="field"><label class="label" for="c3">Email</label><input class="input" id="c3" type="email" name="company_email" value="<?= e(setting('company_email')) ?>"></div>
    </div>
  </div>
  <div class="set-section">
    <div><h2>Lokasi kantor</h2><p class="desc">Hanya dipakai pada peta pelacakan pengantaran. Tidak ditampilkan di surat atau slip gaji.</p></div>
    <div class="form-grid cols-2">
      <div class="field span-2"><label class="label" for="oc">Titik koordinat kantor</label><input class="input" id="oc" name="office_coords" inputmode="decimal" placeholder="-6.2088, 106.8456" value="<?= e(setting('office_lat') !== '' && setting('office_lat') !== null ? setting('office_lat') . ', ' . setting('office_lng') : '') ?>"></div>
    </div>
  </div>
  <div class="set-section">
    <div><h2>Warna</h2><p class="desc">Warna aksen antarmuka. Perubahan berlaku di semua halaman.</p></div>
    <div class="color-grid">
      <?php foreach (['color_primary' => 'Utama', 'color_secondary' => 'Sekunder', 'color_sidebar' => 'Menu samping', 'color_button' => 'Tombol'] as $k => $l): ?>
        <div class="field"><label class="label" for="<?= $k ?>"><?= $l ?></label><input class="input" id="<?= $k ?>" type="color" name="<?= $k ?>" value="<?= e(setting($k, '#2563eb')) ?>"></div><?php endforeach; ?>
    </div>
  </div>
  <div class="set-section">
    <div><h2>Logo</h2><p class="desc">Juga dipakai sebagai favicon dan di slip gaji.</p></div>
    <div>
      <img id="logoPv" class="img-preview <?= setting('logo') ? '' : 'hidden' ?>" src="<?= setting('logo') ? e(url(setting('logo'))) : '' ?>" alt="Logo">
      <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" data-preview="#logoPv">
      <p class="hint">JPG, PNG, atau WebP. Gambar besar dikecilkan otomatis. File lama baru dihapus setelah yang baru tersimpan.</p>
    </div>
  </div>
  <div class="set-section">
    <div><h2>Akses publik</h2><p class="desc">Atur siapa yang bisa membuka halaman publik. Perubahan berlaku setelah disimpan.</p></div>
    <div>
      <div class="switch-row">
        <div class="sw-text"><b>Mode pemeliharaan</b><p class="hint">Aktif: beranda, halaman sekolah, dan pendaftaran ditutup untuk pengunjung. Halaman login tetap bisa dibuka agar admin bisa masuk.</p></div>
        <div class="sw-ctl"><span class="sw-state" data-on="Aktif" data-off="Nonaktif"></span><label class="switch" title="Mode pemeliharaan"><input type="checkbox" name="maintenance" value="1" <?= setting('maintenance') === '1' ? 'checked' : '' ?> aria-label="Mode pemeliharaan"><span class="track"></span></label></div>
      </div>
      <div class="switch-row">
        <div class="sw-text"><b>Tombol Masuk di beranda</b><p class="hint">Aktif: tombol "Masuk" tampil di beranda untuk pengunjung. Nonaktif: tombol disembunyikan, tetapi halaman login tetap bisa dibuka langsung lewat alamat <code>/login</code> sehingga admin tidak terkunci.</p></div>
        <div class="sw-ctl"><span class="sw-state" data-on="Tampil" data-off="Tersembunyi"></span><label class="switch" title="Tombol Masuk di beranda"><input type="checkbox" name="show_login_button" value="1" <?= login_button_visible() ? 'checked' : '' ?> aria-label="Tombol Masuk di beranda"><span class="track"></span></label></div>
      </div>
      <div class="switch-row">
        <div class="sw-text"><b>Status halaman pendaftaran</b><p class="hint">Aktif: halaman pendaftaran karyawan bisa diakses. Nonaktif: halaman tidak bisa diakses dan tautan "Daftar" disembunyikan.</p></div>
        <div class="sw-ctl"><span class="sw-state" data-on="Aktif" data-off="Nonaktif"></span><label class="switch" title="Status halaman pendaftaran"><input type="checkbox" name="registration_open" value="1" <?= registration_open() ? 'checked' : '' ?> aria-label="Status halaman pendaftaran"><span class="track"></span></label></div>
      </div>
    </div>
  </div>
  <div class="form-actions" style="margin-top:0"><button class="btn btn-primary">Simpan pengaturan</button></div>
</form>
