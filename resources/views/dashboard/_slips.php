  <section class="panel panel-flush">
    <div class="panel-head"><h2 class="panel-title">Slip gaji saya</h2></div>
    <?php $fin = array_filter($slips, fn($x) => $x['is_final']); if (!$fin): ?><p class="empty-note">Belum ada slip gaji.</p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>Periode</th><th class="num">Gaji bersih</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($fin as $s): ?>
      <tr><td data-label="Periode" class="strong"><?= e(bulan_id((int)$s['period_month']) . ' ' . $s['period_year']) ?></td><td data-label="Gaji bersih" class="num"><?= rupiah($s['net']) ?></td><td data-label="Status"><?= badge($s['status']) ?></td>
      <td class="cell-actions"><a class="btn btn-ghost btn-sm" href="<?= e(url('/slip/' . $s['invoice_no'])) ?>">Lihat slip</a></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
