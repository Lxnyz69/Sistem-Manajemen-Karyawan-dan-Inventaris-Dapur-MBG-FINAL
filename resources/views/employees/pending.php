<section class="panel panel-flush">
  <div class="panel-head"><div class="u-grow"><h2 class="panel-title">Pendaftaran menunggu konfirmasi</h2><div class="panel-sub"><?= num($pg['total'] ?? count($pg['rows'])) ?> pendaftar</div></div></div>
<div class="table-wrap">
<table class="tbl tbl-stack"><thead><tr><th>No.</th><th>Nama</th><th>WhatsApp</th><th>Alamat</th><th>Tanggal daftar</th><th>Status</th><?php if (can('konfirmasi.approve')): ?><th>Keputusan</th><?php endif; ?></tr></thead><tbody>
<?php foreach ($pg['rows'] as $i => $u): ?>
  <tr><td data-label="No.">#<?= (int)$u['id'] ?></td><td class="strong cell-main"><?= e($u['full_name']) ?></td><td data-label="WhatsApp"><?= e($u['whatsapp']) ?></td><td data-label="Alamat"><?= e($u['address']) ?></td><td data-label="Tanggal daftar"><?= dmyhi($u['created_at']) ?></td><td data-label="Status"><?= badge('pending') ?></td>
  <?php if (can('konfirmasi.approve')): ?><td class="cell-wide">
    <div class="decision-cell">
      <form method="post" action="<?= e(url('/admin/karyawan/konfirmasi/' . $u['id'] . '/setujui')) ?>" class="inline-decision" data-confirm="Setujui pendaftaran ini dan buat ID karyawan otomatis?"><?= csrf_field() ?>
        <select class="input input-sm" name="role_id" required aria-label="Role"><option value="">Pilih role…</option><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-ok btn-sm">Setujui</button></form>
      <form method="post" action="<?= e(url('/admin/karyawan/konfirmasi/' . $u['id'] . '/tolak')) ?>" class="inline-decision" data-confirm="Tolak pendaftaran ini?"><?= csrf_field() ?>
        <input class="input input-sm" name="reason" placeholder="Alasan penolakan" required minlength="3" aria-label="Alasan penolakan"><button class="btn btn-danger btn-sm">Tolak</button></form>
    </div></td><?php endif; ?></tr>
<?php endforeach; if (!$pg['rows']): ?><tr><td colspan="<?= can('konfirmasi.approve') ? 7 : 6 ?>" class="empty">Tidak ada pendaftaran yang menunggu.</td></tr><?php endif; ?>
</tbody></table></div><?= pager($pg) ?></section>
