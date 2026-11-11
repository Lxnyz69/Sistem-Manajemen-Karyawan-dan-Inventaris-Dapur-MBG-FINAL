<?php
$u = auth_user();
$path = request_path();
// Menu berasal dari nav_items() (helpers.php) agar sama dengan logika halaman awal setelah login.
$items = []; $best = null; $bestLen = -1;
foreach (nav_items() as $m) {
    $items[] = $m;
    if (($path === $m[1] || str_starts_with($path, $m[1] . '/')) && strlen($m[1]) > $bestLen) { $best = $m[1]; $bestLen = strlen($m[1]); }
}
$cPending = 0; $cWaiting = 0;
if (can('konfirmasi.view')) $cPending = (int)DB::val("SELECT COUNT(*) FROM users WHERE status='pending'");
if (can('persetujuan_stok.view')) $cWaiting = (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='waiting'");
$notif = $cPending + $cWaiting;
$navCount = ['/admin/karyawan/konfirmasi' => $cPending, '/admin/stok/persetujuan' => $cWaiting];
$initial = mb_strtoupper(mb_substr(trim((string)$u['full_name']), 0, 1)) ?: '?';
$curGroup = null;
$liveOn = live_page($path);
?>
<!doctype html>
<html lang="id"><head><?php require __DIR__ . '/_head.php'; ?></head>
<body>
<?php require __DIR__ . '/_flash.php'; ?>
<div id="navOverlay" class="overlay hidden"></div>
<div class="app">
  <aside id="sidebar" class="sidebar" aria-label="Menu utama">
    <a href="<?= e(url('/')) ?>" class="sb-brand">
      <?php if (setting('logo')): ?><img src="<?= e(url(setting('logo'))) ?>" alt=""><?php else: ?><span class="mark"><i class="fa-solid fa-building"></i></span><?php endif; ?>
      <span class="lbl truncate"><?= e(setting('site_name', 'Manajemen')) ?></span>
    </a>
    <nav class="sb-nav">
      <?php foreach ($items as $m):
        $on = $m[1] === $best;
        if ($m[4] !== $curGroup) { if ($curGroup !== null) echo '</div>'; $curGroup = $m[4]; echo '<div class="sb-group">'; if ($curGroup !== '') echo '<div class="sb-label">' . e($curGroup) . '</div>'; }
        $c = $navCount[$m[1]] ?? 0; ?>
        <a href="<?= e(url($m[1])) ?>" class="nav-link <?= $on ? 'on' : '' ?>" title="<?= e($m[0]) ?>" <?= $on ? 'aria-current="page"' : '' ?>>
          <i class="fa-solid <?= $m[2] ?>"></i><span class="lbl"><?= e($m[0]) ?></span><?php if ($c > 0): ?><span class="nav-count"><?= $c ?></span><?php endif; ?>
        </a>
      <?php endforeach; if ($curGroup !== null) echo '</div>'; ?>
    </nav>
    <div class="sb-foot">
      <button type="button" id="collapseBtn" class="sb-collapse" title="Ciutkan menu"><i class="fa-solid fa-angles-left"></i><span class="lbl">Ciutkan menu</span></button>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button type="button" id="navToggle" class="icon-btn menu-btn" aria-label="Buka menu" aria-controls="sidebar"><i class="fa-solid fa-bars"></i></button>
      <h1 class="page-title"><?= e($title ?? '') ?></h1>
      <?php if ($liveOn): ?><span class="live-pill" id="livePill" data-state="on"><i></i>Live</span><?php endif; ?>
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
    <main class="content" id="liveRoot"<?= $liveOn ? ' data-live="1"' : '' ?><?= $path === '/notifikasi' ? ' data-live-notif="1"' : '' ?>><?= $content ?></main>
    <footer class="footbar"><?= e(setting('footer_text')) ?></footer>
  </div>
</div>
<?php require __DIR__ . '/_modal.php'; ?>
<script src="<?= e(asset('js/app.js') . '?v=' . filemtime(ROOT . '/public/assets/js/app.js')) ?>"></script>
<script src="<?= e(asset('js/ui.js')) ?>"></script>
<script src="<?= e(asset('js/live.js') . '?v=' . filemtime(ROOT . '/public/assets/js/live.js')) ?>"></script>
<?php if (!empty($GLOBALS['__track'])): ?><script src="<?= e(asset('js/track.js') . '?v=' . filemtime(ROOT . '/public/assets/js/track.js')) ?>"></script><?php endif; ?>
</body></html>
