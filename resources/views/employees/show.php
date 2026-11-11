<div class="grid-1-2">
  <section class="panel">
    <h2 class="panel-title u-mb"><?= e($u['full_name']) ?></h2>
    <dl class="kv stacked">
      <div><dt class="u-small">ID Karyawan</dt><dd><?= e($u['employee_code'] ?? '-') ?></dd></div>
      <div><dt class="u-small">WhatsApp</dt><dd><?= e($u['whatsapp']) ?></dd></div>
      <div><dt class="u-small">Alamat</dt><dd><?= e($u['address']) ?></dd></div>
      <div><dt class="u-small">Role</dt><dd><?= e($u['role_name'] ?? '-') ?></dd></div>
      <div><dt class="u-small">Status</dt><dd><?= badge($u['status']) ?></dd></div>
      <div><dt class="u-small">Bergabung</dt><dd><?= dmy($u['joined_at']) ?></dd></div>
      <?php if ($u['reject_reason']): ?><div><dt class="u-small">Alasan penolakan</dt><dd><?= e($u['reject_reason']) ?></dd></div><?php endif; ?>
    </dl>
    <div class="form-actions"><a class="btn btn-ghost" href="<?= e(url('/admin/karyawan')) ?>">&larr; Kembali</a>
      <?php if (can('karyawan.edit')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/karyawan/' . $u['id'] . '/edit')) ?>">Edit</a><?php endif; ?></div>
  </section>
  <section class="panel panel-flush">
    <div class="panel-head"><h2 class="panel-title">Riwayat gaji</h2></div>
    <?php if (!$payrolls): ?><p class="empty-note">Belum ada riwayat gaji.</p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>Periode</th><th class="num">Hadir</th><th class="num">Gaji bersih</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($payrolls as $p): ?><tr><td data-label="Periode" class="strong"><?= e(bulan_id((int)$p['period_month']) . ' ' . $p['period_year']) ?></td><td data-label="Hadir" class="num"><?= $p['days_present'] ?></td><td data-label="Gaji bersih" class="num"><?= rupiah($p['net']) ?></td><td data-label="Status"><?= badge($p['status']) ?></td>
      <td class="cell-actions"><?php if (can('gaji.view') && $p['is_final']): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/gaji/invoice/' . $p['invoice_no'])) ?>">Slip</a><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </section>
</div>
