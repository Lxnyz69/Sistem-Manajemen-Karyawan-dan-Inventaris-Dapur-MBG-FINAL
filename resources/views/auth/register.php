<?php $logo = setting('logo'); ?>
<div class="auth">
  <aside class="auth-side">
    <div class="brand">
      <?php if ($logo): ?><img src="<?= e(url($logo)) ?>" alt=""><?php else: ?><span class="mark"><i class="fa-solid fa-building"></i></span><?php endif; ?>
      <?= e(setting('site_name', 'Manajemen Perusahaan')) ?>
    </div>
    <div>
      <h2>Daftar sebagai karyawan</h2>
      <p>Akun aktif setelah administrator memeriksa dan menyetujui pendaftaran Anda.</p>
    </div>
    <small><?= e(setting('footer_text')) ?></small>
  </aside>
  <main class="auth-main">
    <div class="auth-box wide">
      <div class="auth-brand-sm"><?= e(setting('site_name', 'Manajemen Perusahaan')) ?></div>
      <h1>Pendaftaran akun</h1>
      <p class="lead">Akun baru berstatus <b>Menunggu Konfirmasi</b> sampai disetujui administrator.</p>
      <form method="post" action="<?= e(url('/register')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="field"><label class="label" for="fn">Nama lengkap</label><input class="input" id="fn" name="full_name" value="<?= e(old('full_name')) ?>" required minlength="3"></div>
        <div class="field"><label class="label" for="wa">Nomor WhatsApp</label><input class="input" id="wa" name="whatsapp" inputmode="tel" placeholder="081234567890" value="<?= e(old('whatsapp')) ?>" required></div>
        <div class="field"><label class="label" for="ad">Alamat</label><textarea class="input" id="ad" name="address" rows="2" required><?= e(old('address')) ?></textarea></div>
        <div class="form-grid cols-2">
          <div class="field"><label class="label" for="p1">Password</label><input class="input" id="p1" type="password" name="password" minlength="8" required autocomplete="new-password"><p class="hint">Minimal 8 karakter.</p></div>
          <div class="field"><label class="label" for="p2">Ulangi password</label><input class="input" id="p2" type="password" name="password2" minlength="8" required autocomplete="new-password"></div>
        </div>
        <button class="btn btn-primary btn-block">Daftar</button>
      </form>
      <p class="auth-links">Sudah punya akun? <a href="<?= e(url('/login')) ?>">Masuk</a></p>
    </div>
  </main>
</div>
