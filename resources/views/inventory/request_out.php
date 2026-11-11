<?php $canAdd = can('keluar_stok.create'); ?>
<div class="<?= $canAdd ? 'grid-1-2' : '' ?>">
  <?php if ($canAdd): ?>
  <section class="panel" style="align-self:start"><h2 class="panel-title u-mb">Ajukan barang keluar</h2>
    <form data-live-ignore method="post" action="<?= e(url('/admin/stok/keluar')) ?>" class="stack" style="gap:.9rem"><?= csrf_field() ?>
      <div class="field"><label class="label" for="itemSel">Barang (dari stok)</label><select class="input" name="item_id" id="itemSel" required><option value="">— pilih barang —</option>
        <?php foreach ($items as $it): $avail = (int)$it['stock'] - (int)$it['pending']; ?><option value="<?= $it['id'] ?>" data-avail="<?= $avail ?>" data-stock="<?= (int)$it['stock'] ?>" data-pending="<?= (int)$it['pending'] ?>" data-unit="<?= e($it['unit']) ?>" <?= $avail < 1 ? 'disabled' : '' ?> <?= (int)old('item_id') === (int)$it['id'] ? 'selected' : '' ?>><?= e($it['code'] . ' — ' . $it['name']) ?><?= $avail < 1 ? ' (stok sudah dipesan pengajuan lain)' : '' ?></option><?php endforeach; ?></select>
        <?php if (!$items): ?><small class="u-muted">Tidak ada barang dengan stok tersedia.</small><?php endif; ?></div>
      <div class="stat-inline">Stok saat ini: <b id="curStock">-</b> <span class="curUnit"></span> · Tersedia diajukan: <b id="curAvail">-</b> <span class="curUnit"></span><span id="curPending" class="u-muted u-small"></span></div>
      <div class="field"><label class="label" for="qty">Jumlah keluar</label><input class="input" id="qty" type="number" name="quantity" min="1" value="<?= e(old('quantity')) ?>" required></div>
      <div class="field"><label class="label" for="why">Alasan / tujuan pengeluaran</label><textarea class="input" id="why" name="reason" rows="3" required minlength="5"><?= e(old('reason')) ?></textarea></div>
      <p class="hint" style="margin:0">Tanggal pengajuan: <?= date('d-m-Y') ?>. Stok baru berkurang setelah disetujui administrator.</p>
      <button class="btn btn-primary">Kirim pengajuan</button></form></section>
  <?php endif; ?>
  <section class="panel panel-flush" style="align-self:start"><div class="panel-head"><h2 class="panel-title">Pengajuan barang keluar saya</h2></div>
    <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>No.</th><th>Tanggal</th><th>Barang</th><th class="num">Jumlah</th><th>Status</th><th>Catatan keputusan</th></tr></thead><tbody>
    <?php foreach ($mine as $r): ?><tr><td data-label="No."><?= e($r['request_no']) ?></td><td data-label="Tanggal"><?= dmy($r['created_at']) ?></td><td data-label="Barang" class="strong"><?= e($r['item_name']) ?></td><td data-label="Jumlah" class="num">-<?= num($r['quantity']) ?> <?= e($r['unit']) ?></td><td data-label="Status"><?= badge($r['status']) ?></td><td data-label="Catatan keputusan"><?= e($r['decision_note'] ?? '-') ?></td></tr>
    <?php endforeach; if (!$mine): ?><tr><td colspan="6" class="empty">Belum ada pengajuan.</td></tr><?php endif; ?></tbody></table></div></section>
</div>
<script>
(function(){const s=document.getElementById('itemSel');if(!s)return;const q=document.getElementById('qty');
 const up=()=>{const o=s.selectedOptions[0],has=o&&o.value;
  document.getElementById('curStock').textContent=has?o.dataset.stock:'-';
  document.getElementById('curAvail').textContent=has?o.dataset.avail:'-';
  document.querySelectorAll('.curUnit').forEach(x=>x.textContent=has?o.dataset.unit:'');
  document.getElementById('curPending').textContent=has&&+o.dataset.pending>0?' ('+o.dataset.pending+' menunggu persetujuan)':'';
  if(q){if(has)q.max=o.dataset.avail;else q.removeAttribute('max');}};
 s.addEventListener('change',up);up();})();
</script>
