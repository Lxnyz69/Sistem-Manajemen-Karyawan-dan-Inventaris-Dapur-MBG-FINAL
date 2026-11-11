<?php $canAdd = can('tambah_stok.create'); ?>
<div class="<?= $canAdd ? 'grid-1-2' : '' ?>">
  <?php if ($canAdd): ?>
  <section class="panel" style="align-self:start"><h2 class="panel-title u-mb">Ajukan tambah stok</h2>
    <form data-live-ignore method="post" action="<?= e(url('/admin/stok/tambah')) ?>" class="stack" style="gap:.9rem"><?= csrf_field() ?>
      <div class="field"><label class="label" for="itemSel">Barang</label><select class="input" name="item_id" id="itemSel" required><option value="">— pilih barang —</option>
        <?php foreach ($items as $it): ?><option value="<?= $it['id'] ?>" data-stock="<?= $it['stock'] ?>" data-unit="<?= e($it['unit']) ?>" <?= (int)old('item_id') === (int)$it['id'] ? 'selected' : '' ?>><?= e($it['code'] . ' — ' . $it['name']) ?></option><?php endforeach; ?></select></div>
      <div class="stat-inline">Stok saat ini: <b id="curStock">-</b> <span id="curUnit"></span></div>
      <div class="field"><label class="label" for="qty">Jumlah penambahan</label><input class="input" id="qty" type="number" name="quantity" min="1" value="<?= e(old('quantity')) ?>" required></div>
      <div class="field"><label class="label" for="why">Alasan pengajuan</label><textarea class="input" id="why" name="reason" rows="3" required minlength="5"><?= e(old('reason')) ?></textarea></div>
      <p class="hint" style="margin:0">Tanggal pengajuan: <?= date('d-m-Y') ?>. Stok baru bertambah setelah disetujui administrator.</p>
      <button class="btn btn-primary">Kirim pengajuan</button></form></section>
  <?php endif; ?>
  <section class="panel panel-flush" style="align-self:start"><div class="panel-head"><h2 class="panel-title">Pengajuan saya</h2></div>
    <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>No.</th><th>Tanggal</th><th>Barang</th><th class="num">Jumlah</th><th>Status</th><th>Catatan keputusan</th></tr></thead><tbody>
    <?php foreach ($mine as $r): ?><tr><td data-label="No."><?= e($r['request_no']) ?></td><td data-label="Tanggal"><?= dmy($r['created_at']) ?></td><td data-label="Barang" class="strong"><?= e($r['item_name']) ?></td><td data-label="Jumlah" class="num">+<?= num($r['quantity']) ?> <?= e($r['unit']) ?></td><td data-label="Status"><?= badge($r['status']) ?></td><td data-label="Catatan keputusan"><?= e($r['decision_note'] ?? '-') ?></td></tr>
    <?php endforeach; if (!$mine): ?><tr><td colspan="6" class="empty">Belum ada pengajuan.</td></tr><?php endif; ?></tbody></table></div></section>
</div>
<script>
(function(){const s=document.getElementById('itemSel');if(!s)return;
 const up=()=>{const o=s.selectedOptions[0];document.getElementById('curStock').textContent=o&&o.dataset.stock?o.dataset.stock:'-';document.getElementById('curUnit').textContent=o&&o.dataset.unit?o.dataset.unit:'';};
 s.addEventListener('change',up);up();})();
</script>
