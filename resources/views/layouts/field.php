<?php
/* Halaman Tugas Kurir: topbar, lonceng notifikasi, dan menu profil SAMA dengan dasbor (layouts/app.php).
   Bedanya: tanpa menu samping; navigasi bawah hanya "Tugas". */
$u = auth_user(); $path = request_path();
$initial = mb_strtoupper(mb_substr(trim((string)$u['full_name']), 0, 1)) ?: '?';
$liveOn = true;
?>
<!doctype html>
<html lang="id"><head><?php require __DIR__ . '/_head.php'; ?>
<link rel="stylesheet" href="<?= e(asset('css/field.css') . '?v=' . filemtime(ROOT . '/public/assets/css/field.css')) ?>">
</head>
<body class="has-bnav gps-locked">
<?php require __DIR__ . '/_flash.php'; ?>
<div class="app">
  <div class="main">
    <header class="topbar">
      <a href="<?= e(url(home_path())) ?>" class="icon-btn menu-btn" style="display:inline-flex" aria-label="Ke Dasbor" title="Dasbor"><i class="fa-solid fa-house"></i></a>
      <h1 class="page-title"><?= e($title ?? '') ?></h1>
      <span class="live-pill" id="livePill" data-state="on"><i></i>Live</span>
      <?php require __DIR__ . '/_notif.php'; ?>
      <div class="user-menu" id="userMenu">
        <button type="button" class="user-chip" id="userMenuBtn" aria-haspopup="menu" aria-expanded="false" aria-controls="userMenuList">
          <span class="avatar" aria-hidden="true"><?= e($initial) ?></span>
          <span class="user-meta"><b><?= e($u['full_name']) ?></b><span><?= e($u['role_name'] ?? '-') ?></span></span>
          <i class="fa-solid fa-chevron-down user-caret" aria-hidden="true"></i>
        </button>
        <div class="user-dropdown" id="userMenuList" role="menu" hidden>
          <div class="user-dropdown-head"><b><?= e($u['full_name']) ?></b><span><?= e($u['role_name'] ?? '-') ?></span></div>
          <?php if (can('profile.view')): ?><a href="<?= e(url('/profile')) ?>" role="menuitem" class="user-dropdown-item"><i class="fa-regular fa-id-card"></i> Profil Saya</a><?php endif; ?>
          <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button type="submit" role="menuitem" class="user-dropdown-item danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
        </div>
      </div>
    </header>
    <main class="content f-content" id="liveRoot" data-live="1"><?= $content ?></main>
  </div>
</div>
<nav class="bnav" aria-label="Navigasi bawah">
  <a href="<?= e(url('/kurir')) ?>" class="on" aria-current="page"><i class="fa-solid fa-truck-fast"></i><span>Tugas</span></a>
</nav>
<?php require __DIR__ . '/_modal.php'; ?>
<script src="<?= e(asset('js/app.js') . '?v=' . filemtime(ROOT . '/public/assets/js/app.js')) ?>"></script>
<script src="<?= e(asset('js/ui.js')) ?>"></script>
<script src="<?= e(asset('js/live.js') . '?v=' . filemtime(ROOT . '/public/assets/js/live.js')) ?>"></script>
<script src="<?= e(asset('js/field.js') . '?v=' . filemtime(ROOT . '/public/assets/js/field.js')) ?>"></script>
</body></html>
