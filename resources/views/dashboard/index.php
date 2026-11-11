<?php
/*
 * Dasbor tunggal yang mengikuti hak akses:
 *  - hanya "Dashboard Admin"     -> bagian admin
 *  - hanya "Dashboard Karyawan"  -> bagian karyawan
 *  - keduanya                    -> semua bagian admin + karyawan
 *  - Slip gaji saya tampil di semua dasbor (bila punya hak "Slip Gaji Saya")
 */
$tiles = [];
if ($showSlips) $tiles[] = 'slips';
if ($showRequests) $tiles[] = 'requests';
?>
<?php if ($showAdmin) include __DIR__ . '/_admin.php'; ?>
<?php if ($tiles): ?>
<div class="<?= count($tiles) > 1 ? 'grid-2' : '' ?><?= $showAdmin ? ' u-mt' : '' ?>">
  <?php if ($showSlips) include __DIR__ . '/_slips.php'; ?>
  <?php if ($showRequests) include __DIR__ . '/_requests.php'; ?>
</div>
<?php elseif (!$showAdmin): ?>
<p class="empty-note panel">Belum ada informasi yang bisa ditampilkan untuk akun Anda.</p>
<?php endif; ?>
