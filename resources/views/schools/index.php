<?php $canRow = can('sekolah.edit') || can('sekolah.delete'); ?>
<section class="dist-page school-module">
  <header class="dist-head"><div><h2>Data Sekolah</h2><p><?= (int)$total_schools ?> sekolah tersimpan</p></div><?php if (can('sekolah.create')): ?><a class="btn btn-primary" href="<?= e(url('/admin/sekolah/baru')) ?>"><i class="fa-solid fa-plus"></i> Tambah Sekolah</a><?php endif; ?></header>
  <form class="dist-filter" method="get" action="<?= e(url('/admin/sekolah')) ?>">
    <label class="dist-field dist-search"><span>Pencarian</span><input class="input" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau alamat"></label>
    <label class="dist-field"><span>Status sekolah</span><select class="input" name="status"><option value="">Semua status</option><option value="active" <?= $status==='active'?'selected':'' ?>>Aktif</option><option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Nonaktif</option></select></label>
    <div class="dist-filter-action"><button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Cari</button><a class="btn btn-ghost" href="<?= e(url('/admin/sekolah')) ?>">Reset</a></div>
  </form>
  <section class="dist-card"><div class="dist-card-head"><div><h3>Daftar sekolah</h3><p><?= (int)$total ?> data sekolah</p></div></div>
    <div class="table-wrap"><table class="tbl school-list-table"><thead><tr><th>Sekolah</th><th>WhatsApp</th><th class="num">Jumlah siswa</th><th>Status</th><?php if ($canRow): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr><td><a class="school-name" href="<?= e(url('/admin/sekolah/'.$r['id'])) ?>"><?= e($r['name']) ?></a><span class="sub"><?= e($r['address']) ?></span></td><td><?= e($r['whatsapp']) ?></td><td class="num"><?= number_format((int)$r['daily_portions']) ?></td><td><span class="badge <?= $r['status']==='active'?'badge-green':'badge-red' ?>"><?= $r['status']==='active'?'Aktif':'Nonaktif' ?></span></td><?php if ($canRow): ?><td><div class="row-actions">
      <?php if (can('sekolah.edit')): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/sekolah/'.$r['id'].'/edit')) ?>">Edit</a>
      <form method="post" action="<?= e(url('/admin/sekolah/'.$r['id'].'/status')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= $r['status']==='active'?'Nonaktifkan':'Aktifkan' ?></button></form><?php endif; ?>
      <?php if (can('sekolah.delete')): ?><form method="post" action="<?= e(url('/admin/sekolah/'.$r['id'].'/hapus')) ?>" onsubmit="return confirm('Hapus sekolah ini? Data terkait akan diperiksa terlebih dahulu.')"><?= csrf_field() ?><button class="btn btn-danger btn-sm">Hapus</button></form><?php endif; ?>
    </div></td><?php endif; ?></tr><?php endforeach; if(!$rows): ?><tr><td class="empty" colspan="<?= $canRow ? 5 : 4 ?>">Belum ada data sekolah.<?= can('sekolah.create') ? ' Gunakan tombol Tambah Sekolah untuk memulai.' : '' ?></td></tr><?php endif; ?></tbody></table></div><?= pager(['rows'=>$rows,'total'=>$total,'pages'=>$pages,'page'=>$page],['q'=>$q,'status'=>$status]) ?>
  </section>
</section>
