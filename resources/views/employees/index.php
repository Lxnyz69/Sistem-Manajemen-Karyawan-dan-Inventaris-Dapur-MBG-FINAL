<?php $qs = array_filter(['q' => $_GET['q'] ?? '', 'role' => $_GET['role'] ?? '', 'status' => $_GET['status'] ?? '', 'from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? '']); $nf = count($qs); ?>
<section class="panel panel-flush">
  <div class="panel-head">
    <div class="u-grow"><h2 class="panel-title">Daftar karyawan</h2><div class="panel-sub"><?= num($pg['total']) ?> data</div></div>
    <?php if (can('karyawan.export')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/karyawan/export?' . http_build_query($qs))) ?>"><i class="fa-solid fa-file-csv"></i> Ekspor CSV</a><?php endif; ?>
    <?php if (can('karyawan.create')): ?><a class="btn btn-primary" href="<?= e(url('/admin/karyawan/tambah')) ?>"><i class="fa-solid fa-plus"></i> Tambah karyawan</a><?php endif; ?>
  </div>
  <div class="filters <?= $nf ? 'is-open' : '' ?>">
    <button type="button" class="filters-toggle"><span><i class="fa-solid fa-sliders"></i> Filter</span><span class="count"><?= $nf ? $nf . ' aktif' : '' ?></span></button>
    <form method="get" class="filters-body">
      <div class="filters-grid">
        <div class="f-wide search"><label class="label" for="fq">Pencarian</label><i class="fa-solid fa-magnifying-glass"></i><input class="input" id="fq" name="q" placeholder="Nama atau ID karyawan" value="<?= e($_GET['q'] ?? '') ?>"></div>
        <div><label class="label" for="frole">Role</label><select class="input" id="frole" name="role"><option value="">Semua role</option>
          <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= ($_GET['role'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="label" for="fstatus">Status</label><select class="input" id="fstatus" name="status"><option value="">Semua status</option>
          <?php foreach (['active' => 'Aktif', 'pending' => 'Menunggu', 'inactive' => 'Tidak Aktif', 'rejected' => 'Ditolak'] as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div><label class="label" for="ffrom">Bergabung dari</label><input class="input" id="ffrom" type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>"></div>
        <div><label class="label" for="fto">Sampai</label><input class="input" id="fto" type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>"></div>
        <div class="filters-actions"><button class="btn btn-primary">Terapkan</button><a class="btn btn-ghost" href="<?= e(url('/admin/karyawan')) ?>">Reset</a></div>
      </div>
    </form>
  </div>
  <div class="table-wrap">
  <table class="tbl tbl-stack"><thead><tr><th data-sort>ID</th><th data-sort>Nama</th><th>WhatsApp</th><th>Alamat</th><th>Role</th><th>Status</th><th data-sort>Bergabung</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach ($pg['rows'] as $u): ?>
    <tr>
      <td data-label="ID"><?= e($u['employee_code'] ?? '-') ?></td>
      <td class="strong cell-main"><?= e($u['full_name']) ?></td>
      <td data-label="WhatsApp"><?= e($u['whatsapp']) ?></td>
      <td data-label="Alamat" class="trunc" title="<?= e($u['address']) ?>"><?= e($u['address']) ?></td>
      <td data-label="Role"><?= e($u['role_name'] ?? '-') ?></td>
      <td data-label="Status"><?= badge($u['status']) ?></td>
      <td data-label="Bergabung"><?= dmy($u['joined_at']) ?></td>
      <td class="cell-actions"><div class="row-actions">
        <a class="btn btn-ghost btn-sm btn-icon" title="Detail" aria-label="Detail" href="<?= e(url('/admin/karyawan/' . $u['id'])) ?>"><i class="fa-solid fa-eye"></i></a>
        <?php if (can('karyawan.edit')): ?>
          <a class="btn btn-ghost btn-sm btn-icon" title="Edit" aria-label="Edit" href="<?= e(url('/admin/karyawan/' . $u['id'] . '/edit')) ?>"><i class="fa-solid fa-pen"></i></a>
          <?php if (in_array($u['status'], ['active', 'inactive'], true)): ?>
          <form method="post" action="<?= e(url('/admin/karyawan/' . $u['id'] . '/status')) ?>" data-confirm="<?= $u['status'] === 'active' ? 'Nonaktifkan akun ini? Karyawan tidak bisa login.' : 'Aktifkan kembali akun ini?' ?>"><?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm btn-icon" title="<?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="Aktif/nonaktif"><i class="fa-solid <?= $u['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' ?>" style="color:<?= $u['status'] === 'active' ? 'var(--ok)' : 'var(--faint)' ?>"></i></button></form>
          <?php endif; ?>
        <?php endif; ?>
        <?php if (can('karyawan.delete')): ?>
          <form method="post" action="<?= e(url('/admin/karyawan/' . $u['id'] . '/hapus')) ?>" data-confirm="Hapus karyawan ini? Tindakan tidak bisa dibatalkan."><?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm btn-icon text-red-600" title="Hapus" aria-label="Hapus"><i class="fa-solid fa-trash"></i></button></form>
        <?php endif; ?>
      </div></td>
    </tr>
  <?php endforeach; if (!$pg['rows']): ?><tr><td colspan="8" class="empty">Belum ada data karyawan yang cocok.</td></tr><?php endif; ?>
  </tbody></table></div>
  <?= pager($pg, $qs) ?>
</section>
