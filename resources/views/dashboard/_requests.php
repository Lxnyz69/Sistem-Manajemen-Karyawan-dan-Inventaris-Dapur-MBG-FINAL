  <section class="panel panel-flush">
    <div class="panel-head"><h2 class="panel-title u-grow">Pengajuan stok saya</h2>
      <?php if (can('tambah_stok.create')): ?><a class="btn btn-primary btn-sm" href="<?= e(url('/admin/stok/tambah')) ?>">Ajukan</a><?php endif; ?><?php if (can('keluar_stok.create')): ?> <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/stok/keluar')) ?>">Barang keluar</a><?php endif; ?></div>
    <?php if (!$myReq): ?><p class="empty-note">Belum ada pengajuan.</p><?php else: ?>
    <div class="table-wrap"><table class="tbl"><tbody><?php foreach ($myReq as $r): ?>
      <tr><td><span class="strong"><?= e($r['request_no']) ?></span><span class="sub"><?= e($r['item_name']) ?> · <?= ($r['type'] ?? 'in') === 'out' ? '-' : '+' ?><?= num($r['quantity']) ?></span></td><td class="u-right"><?= badge($r['status']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
