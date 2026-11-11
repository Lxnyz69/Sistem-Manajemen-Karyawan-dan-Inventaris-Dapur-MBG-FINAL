<?php $logo = setting('logo'); ?>
<div class="auth">
  <aside class="auth-side">
    <div class="brand">
      <?php if ($logo): ?><img src="<?= e(url($logo)) ?>" alt=""><?php else: ?><span class="mark"><i class="fa-solid fa-building"></i></span><?php endif; ?>
      <?= e(setting('site_name', 'Manajemen Perusahaan')) ?>
    </div>
    <div>
      <h2><?= e(setting('site_title', 'Manajemen Perusahaan')) ?></h2>
      <p>Data karyawan, penggajian, dan inventaris dalam satu tempat.</p>
    </div>
    <small><?= e(setting('footer_text')) ?></small>
  </aside>
  <main class="auth-main">
    <div class="auth-box">
      <div class="auth-brand-sm">
        <?php if ($logo): ?><img src="<?= e(url($logo)) ?>" alt=""><?php else: ?><i class="fa-solid fa-building" style="color:var(--c-primary)"></i><?php endif; ?>
        <?= e(setting('site_name', 'Manajemen Perusahaan')) ?>
      </div>
      <h1>Masuk</h1>
      <p class="lead">Gunakan ID karyawan atau username Anda.</p>
      <form method="post" action="<?= e(url('/login')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="field"><label class="label" for="login">ID Karyawan / Username</label><input class="input" id="login" name="login" value="<?= e(old('login')) ?>" required autofocus autocomplete="username"></div>
        <div class="field"><label class="label" for="pw">Password</label><input class="input" id="pw" type="password" name="password" required autocomplete="current-password"></div>
        <button class="btn btn-primary btn-block">Masuk</button>
      </form>
      <p class="auth-links"><?php if (registration_open()): ?>Belum punya akun? <a href="<?= e(url('/register')) ?>">Daftar</a> · <?php endif; ?><a href="<?= e(url('/')) ?>">Beranda</a></p>
    </div>
  </main>
</div>
