<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') ? $title . ' — ' . setting('site_name', 'Manajemen Perusahaan') : setting('site_title', 'Manajemen Perusahaan')) ?></title>
<?php if (setting('logo') && is_file(ROOT . '/public/' . setting('logo'))): ?><link rel="icon" href="<?= e(url(setting('logo'))) ?>"><?php endif; ?>
<?php if (is_file(ROOT . '/public/assets/css/app.css')): ?>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?php else: ?>
<!-- Mode pengembangan: build CSS lokal dengan "npm run build:css" (lihat README) -->
<script src="https://cdn.tailwindcss.com"></script>
<?php endif; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<meta name="app-base" content="<?= e(base_path()) ?>">
<?php if (auth_user()): ?><meta name="csrf-token" content="<?= e(csrf_token()) ?>"><?php endif; ?>
<style>
:root{--c-primary:<?= e(setting('color_primary', '#2563eb')) ?>;--c-secondary:<?= e(setting('color_secondary', '#0ea5e9')) ?>;--c-sidebar:<?= e(setting('color_sidebar', '#0f172a')) ?>;--c-button:<?= e(setting('color_button', '#2563eb')) ?>}
</style>
<link rel="stylesheet" href="<?= e(asset('css/ui.css') . '?v=' . filemtime(ROOT . '/public/assets/css/ui.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/live.css') . '?v=' . filemtime(ROOT . '/public/assets/css/live.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/motion.css') . '?v=' . filemtime(ROOT . '/public/assets/css/motion.css')) ?>">
