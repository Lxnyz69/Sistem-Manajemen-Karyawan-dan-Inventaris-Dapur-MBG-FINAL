<div class="grid-1-2">
  <div class="stack" style="align-content:start">
    <section class="panel">
      <?php if ($i['photo']): ?><img src="<?= e(url($i['photo'])) ?>" class="item-photo" alt=""><?php endif; ?>
      <h2 class="panel-title"><?= e($i['name']) ?></h2><div class="u-small u-muted"><?= e($i['code']) ?></div>
      <?php if ($i['description']): ?><p class="u-muted" style="margin:.6rem 0 0"><?= e($i['description']) ?></p><?php endif; ?>
      <dl class="kv" style="margin-top:1rem">
        <div><dt>Stok</dt><dd class="stock-big"><?= num($i['stock']) ?> <span class="u-small u-muted" style="font-weight:500"><?= e($i['unit']) ?></span></dd></div>
        <div><dt>Harga per unit</dt><dd><?= rupiah($i['price']) ?></dd></div>
        <div><dt>Total nilai</dt><dd><?= rupiah($i['stock'] * $i['price']) ?></dd></div>
      </dl>
      <div class="form-actions"><a class="btn btn-ghost" href="<?= e(url('/admin/stok')) ?>">&larr; Kembali</a>
        <?php if (can('stok.edit')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/stok/barang/' . $i['id'] . '/edit')) ?>">Edit barang</a><?php endif; ?></div>
    </section>
    <?php if (can('stok.edit')): ?>
    <section class="panel">
      <h2 class="panel-title u-mb">Barang keluar / penyesuaian</h2>
      <form method="post" action="<?= e(url('/admin/stok/barang/' . $i['id'] . '/gerak')) ?>" class="stack" style="gap:.75rem" data-confirm="Simpan perubahan stok ini?">
        <?= csrf_field() ?>
        <div class="field"><label class="label" for="mt">Jenis</label><select class="input" id="mt" name="type"><option value="out">Barang keluar (kurangi)</option><option value="adjust">Penyesuaian ke jumlah…</option></select></div>
        <div class="field"><label class="label" for="mq">Jumlah</label><input class="input" id="mq" type="number" min="0" name="qty" required></div>
        <div class="field"><label class="label" for="mn">Catatan</label><input class="input" id="mn" name="note" placeholder="Wajib diisi" required minlength="3"></div>
        <button class="btn btn-primary btn-block">Simpan</button></form>
    </section>
    <?php endif; ?>
  </div>
  <section class="panel panel-flush" style="align-self:start">
    <div class="panel-head"><h2 class="panel-title">Riwayat pergerakan stok</h2></div>
    <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>Waktu</th><th>Jenis</th><th class="num">Jumlah</th><th class="num">Saldo</th><th>Ref / catatan</th><th>Oleh</th></tr></thead><tbody>
    <?php foreach ($mv as $m): ?><tr><td data-label="Waktu"><?= dmyhi($m['created_at']) ?></td>
      <td data-label="Jenis"><?= ['in' => '<span class="badge badge-green">Masuk</span>', 'out' => '<span class="badge badge-red">Keluar</span>', 'adjust' => '<span class="badge badge-gray">Penyesuaian</span>'][$m['type']] ?></td>
      <td data-label="Jumlah" class="num"><?= ($m['qty'] > 0 ? '+' : '') . num($m['qty']) ?></td><td data-label="Saldo" class="num"><?= num($m['balance_after']) ?></td><td data-label="Ref / catatan"><?= e(trim(($m['ref'] ?? '') . ' ' . ($m['note'] ?? ''))) ?></td><td data-label="Oleh"><?= e($m['full_name'] ?? 'Sistem') ?></td></tr>
    <?php endforeach; if (!$mv): ?><tr><td colspan="6" class="empty">Belum ada riwayat.</td></tr><?php endif; ?></tbody></table></div>
  </section>
</div>
