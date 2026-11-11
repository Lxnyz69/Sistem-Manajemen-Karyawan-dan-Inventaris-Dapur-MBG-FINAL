<?php if ((int)$s['pending'] + (int)$s['req_wait'] + (int)$s['low'] + (int)$s['unpaid'] > 0): ?>
<section class="u-mb" aria-label="Perlu tindakan">
  <div class="attn" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))">
    <?php foreach ([
      [$s['pending'], 'Akun menunggu konfirmasi', '/admin/karyawan/konfirmasi', 'konfirmasi.view', '#b7791f'],
      [$s['req_wait'], 'Pengajuan stok menunggu', '/admin/stok/persetujuan', 'persetujuan_stok.view', '#b7791f'],
      [$s['unpaid'], 'Gaji belum dibayar', '/admin/gaji?status=unpaid', 'gaji.view', '#b7791f'],
      [$s['low'], 'Stok menipis', '/admin/stok?low=1', 'stok.view', '#b3261e'],
    ] as [$n, $t, $href, $perm, $tone]): if (!can($perm) || (int)$n === 0) continue; ?>
      <a href="<?= e(url($href)) ?>" style="--tone:<?= $tone ?>"><span class="t"><?= e($t) ?></span><span class="n"><?= num($n) ?></span></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div class="stack">
  <div class="grid-2" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,26rem),1fr))">
    <?php if (!empty($budget)): ?>
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Anggaran</h2></div>
      <div class="metrics plain">
        <?= stat_card('', 'Saldo anggaran', rupiah($budget['balance']), '#2563eb', true) ?>
        <?= stat_card('', 'Dana masuk minggu ini', rupiah($budget['week_in'])) ?>
        <?= stat_card('', 'Pengeluaran minggu ini', rupiah($budget['week_out'])) ?>
        <?= stat_card('', 'Pengeluaran gaji', rupiah($budget['out_payroll'])) ?>
        <?= stat_card('', 'Pengeluaran stok', rupiah($budget['out_stock'])) ?>
      </div>
    </section>
    <?php endif; ?>
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Karyawan</h2></div>
      <div class="metrics plain">
        <?= stat_card('', 'Total akun', num($s['accounts'])) ?>
        <?= stat_card('', 'Aktif', num($s['active'])) ?>
        <?= stat_card('', 'Menunggu konfirmasi', num($s['pending']), '#b7791f', (int)$s['pending'] > 0) ?>
        <?= stat_card('', 'Tidak aktif', num($s['inactive'])) ?>
      </div>
    </section>
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Penggajian</h2></div>
      <div class="metrics plain">
        <?= stat_card('', 'Total pengeluaran gaji', rupiah($s['payroll'])) ?>
        <?= stat_card('', 'Gaji belum dibayar', num($s['unpaid']), '#b7791f', (int)$s['unpaid'] > 0) ?>
      </div>
    </section>
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Inventaris</h2></div>
      <div class="metrics plain">
        <?= stat_card('', 'Jenis barang', num($s['items'])) ?>
        <?= stat_card('', 'Total kuantitas stok', num($s['stock'])) ?>
        <?= stat_card('', 'Nilai inventaris', rupiah($s['value'])) ?>
        <?= stat_card('', 'Stok menipis', num($s['low']), '#b3261e', (int)$s['low'] > 0) ?>
      </div>
    </section>
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Pengajuan stok</h2></div>
      <div class="metrics plain">
        <?= stat_card('', 'Menunggu', num($s['req_wait']), '#b7791f', (int)$s['req_wait'] > 0) ?>
        <?= stat_card('', 'Disetujui / ditolak', $s['req_ok'] . ' / ' . $s['req_no']) ?>
      </div>
    </section>
  </div>

  <div class="grid-2">
    <section class="panel"><h2 class="panel-title u-mb">Tren penggajian <span class="panel-sub">· 6 bulan terakhir</span></h2>
      <?php $d = []; foreach ($trend as $t) $d[bulan_id((int)$t['m']) . ' ' . $t['y']] = $t['total']; echo bar_chart($d, 'var(--c-primary)', true); ?></section>
    <section class="panel"><h2 class="panel-title u-mb">Kehadiran bulan ini</h2>
      <?= bar_chart(['Hadir' => $att['hadir'] ?? 0, 'Izin' => $att['izin'] ?? 0, 'Tanpa keterangan' => $att['alpa'] ?? 0, 'Libur' => $att['libur'] ?? 0], 'var(--c-primary)') ?></section>
  </div>

  <div class="grid-2">
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Pergerakan stok <span class="panel-sub">· 14 hari</span></h2></div>
      <?php if (!$moves): ?><p class="empty-note">Belum ada pergerakan stok.</p><?php else: ?>
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Tanggal</th><th class="num">Masuk</th><th class="num">Keluar</th></tr></thead><tbody>
      <?php foreach ($moves as $m): ?><tr><td><?= dmy($m['d']) ?></td><td class="num pos">+<?= num($m['masuk']) ?></td><td class="num neg">−<?= num(abs((int)$m['keluar'])) ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </section>
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Aktivitas administrasi terbaru</h2></div>
      <?php if (!$logs): ?><p class="empty-note">Belum ada aktivitas.</p><?php else: ?>
      <ul class="feed"><?php foreach ($logs as $l): ?>
        <li><span class="who"><?= e($l['full_name'] ?? 'Sistem') ?></span> · <?= e($l['action']) ?>
          <div class="meta"><?= e($l['detail']) ?> — <?= dmyhi($l['created_at']) ?></div></li>
      <?php endforeach; ?></ul><?php endif; ?>
    </section>
  </div>
</div>
