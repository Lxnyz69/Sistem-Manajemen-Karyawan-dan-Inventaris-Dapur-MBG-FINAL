<section class="panel panel-flush">
  <div class="panel-head"><div class="u-grow"><h2 class="panel-title">Role &amp; hak akses</h2><div class="panel-sub">Izin diperiksa di server pada setiap halaman dan aksi.</div></div>
    <?php if (can('role.create')): ?><a class="btn btn-primary" href="<?= e(url('/admin/role/baru')) ?>"><i class="fa-solid fa-plus"></i> Tambah role</a><?php endif; ?></div>
  <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>Role</th><th>Deskripsi</th><th class="num">Pengguna</th><th class="num">Izin</th><?php if (can('role.edit') || can('role.delete')): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody>
  <?php foreach ($roles as $r): ?><tr><td class="strong cell-main"><?= e($r['name']) ?> <?= $r['is_super'] ? '<span class="badge badge-blue">Akses penuh</span>' : '' ?></td><td data-label="Deskripsi"><?= e($r['description']) ?></td><td data-label="Pengguna" class="num"><?= (int)$r['users_count'] ?></td><td data-label="Izin" class="num"><?= $r['is_super'] ? 'Semua' : (int)$r['perm_count'] ?></td>
    <td class="cell-actions"><?php if ($r['is_super']): ?><span class="u-small u-muted">Dilindungi</span><?php else: ?><div class="row-actions">
      <?php if (can('role.edit')): ?><a class="btn btn-ghost btn-sm btn-icon" href="<?= e(url('/admin/role/' . $r['id'] . '/edit')) ?>" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></a><?php endif; ?>
      <?php if (can('role.delete')): ?><form method="post" action="<?= e(url('/admin/role/' . $r['id'] . '/hapus')) ?>" data-confirm="Hapus role ini?"><?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-icon text-red-600" title="Hapus" aria-label="Hapus"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?></div><?php endif; ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
</section>
