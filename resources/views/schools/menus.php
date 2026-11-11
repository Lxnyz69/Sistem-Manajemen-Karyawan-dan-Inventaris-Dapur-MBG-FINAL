<?php $canRow = can('menu.edit') || can('menu.delete'); ?>
<section class="dist-page">
  <header class="dist-head"><div><h2>Menu Makanan Sekolah</h2><p>Kelola jadwal dan porsi menu per sekolah.</p></div><?php if (can('delivery.view')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/pengantaran')) ?>"><i class="fa-solid fa-truck"></i> Kelola pengantaran</a><?php endif; ?></header>
  <form class="dist-filter" method="get" action="<?= e(url('/admin/menu')) ?>">
    <label class="dist-field"><span>Sekolah</span><select class="input" name="school_id"><option value="0">Semua sekolah</option><?php foreach($schools as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $sid==(int)$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></label>
    <label class="dist-field"><span>Tanggal distribusi</span><input class="input" type="date" name="date" value="<?= e($date) ?>"></label>
    <label class="dist-field dist-search"><span>Cari menu</span><input class="input" name="q" value="<?= e($q) ?>" placeholder="Nama atau deskripsi menu"></label>
    <div class="dist-filter-action"><button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button><a class="btn btn-ghost" href="<?= e(url('/admin/menu')) ?>">Reset</a></div>
  </form>
  <?php if (can('menu.create')): ?>
  <form class="dist-card" data-live-ignore method="post" action="<?= e(url('/admin/menu/simpan')) ?>">
    <?= csrf_field() ?><div class="dist-card-head"><div><h3>Tambah menu</h3><p>Isi satu atau beberapa menu. Setiap baris disimpan sebagai data terpisah.</p></div></div>
    <div class="dist-card-body">
      <label class="dist-field dist-school"><span>Sekolah <b>*</b></span><select required class="input" name="school_id"><option value="">Pilih sekolah</option><?php foreach($schools as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $sid==(int)$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></label>
      <div id="menuRows" class="dist-menu-rows"><div class="dist-menu-row">
        <label class="dist-field"><span>Nama menu <b>*</b></span><input required class="input" name="name[]" maxlength="150" placeholder="Contoh: Nasi ayam panggang"></label>
        <label class="dist-field"><span>Tanggal distribusi <b>*</b></span><input required class="input" type="date" name="delivery_date[]" value="<?= date('Y-m-d') ?>"></label>
        <label class="dist-field"><span>Jumlah porsi <b>*</b></span><input required class="input" type="number" min="0" step="1" name="portions[]" value="0"></label>
        <label class="dist-field"><span>Status menu</span><select class="input" name="menu_status[]"><option value="available">Tersedia</option><option value="preparing">Sedang disiapkan</option><option value="completed">Selesai</option></select></label>
        <label class="dist-field dist-description"><span>Deskripsi</span><textarea class="input" name="description[]" rows="2" placeholder="Catatan bahan atau informasi menu"></textarea></label>
      </div></div>
      <div class="dist-actions"><button class="btn btn-ghost" type="button" id="addMenuRow"><i class="fa-solid fa-plus"></i> Tambah baris menu</button><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan menu</button></div>
    </div>
  </form>
  <?php endif; ?>
  <section class="dist-card"><div class="dist-card-head"><div><h3>Daftar menu</h3><p><?= count($rows) ?> data ditampilkan</p></div></div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Sekolah</th><th>Tanggal</th><th>Menu</th><th class="num">Porsi</th><th>Status</th><?php if ($canRow): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody>
    <?php foreach($rows as $m): ?><tr><td><strong><?= e($m['school_name']) ?></strong></td><td><?= e(date('d-m-Y',strtotime($m['delivery_date']))) ?></td><td><strong><?= e($m['name']) ?></strong><?php if($m['description']): ?><span class="sub"><?= e($m['description']) ?></span><?php endif; ?></td><td class="num"><?= number_format((int)$m['portions']) ?></td><td><span class="badge <?= $m['status']==='completed'?'badge-green':($m['status']==='preparing'?'badge-yellow':'badge-blue') ?>"><?= ['available'=>'Tersedia','preparing'=>'Sedang disiapkan','completed'=>'Selesai'][$m['status']]??e($m['status']) ?></span></td><?php if ($canRow): ?><td><div class="row-actions">
      <?php if (can('menu.edit')): ?><form method="post" action="<?= e(url('/admin/menu/'.$m['id'].'/edit')) ?>"><?= csrf_field() ?><input type="hidden" name="name" value="<?= e($m['name']) ?>"><input type="hidden" name="description" value="<?= e($m['description']) ?>"><input type="hidden" name="delivery_date" value="<?= e($m['delivery_date']) ?>"><input type="hidden" name="portions" value="<?= (int)$m['portions'] ?>"><select class="input input-sm" name="menu_status" aria-label="Ubah status menu"><option value="available" <?= $m['status']==='available'?'selected':'' ?>>Tersedia</option><option value="preparing" <?= $m['status']==='preparing'?'selected':'' ?>>Disiapkan</option><option value="completed" <?= $m['status']==='completed'?'selected':'' ?>>Selesai</option></select><button class="btn btn-ghost btn-sm">Simpan status</button></form><?php endif; ?>
      <?php if (can('menu.delete')): ?><form method="post" action="<?= e(url('/admin/menu/'.$m['id'].'/hapus')) ?>" onsubmit="return confirm('Hapus menu ini?')"><?= csrf_field() ?><button class="btn btn-danger btn-sm">Hapus</button></form><?php endif; ?>
    </div></td><?php endif; ?></tr><?php endforeach; if(!$rows): ?><tr><td class="empty" colspan="<?= $canRow ? 6 : 5 ?>">Belum ada menu untuk filter ini.<?= can('menu.create') ? ' Tambahkan menu melalui formulir di atas.' : '' ?></td></tr><?php endif; ?></tbody></table></div></section>
</section>
<script>
document.getElementById('addMenuRow')?.addEventListener('click',()=>{const row=document.querySelector('.dist-menu-row').cloneNode(true);row.querySelectorAll('input:not([type=hidden]),textarea').forEach(el=>{if(el.type==='date')el.value='<?= date('Y-m-d') ?>';else if(el.type==='number')el.value='0';else el.value='';});row.querySelectorAll('select').forEach(el=>el.selectedIndex=0);const del=document.createElement('button');del.type='button';del.className='btn btn-ghost btn-sm dist-remove';del.textContent='Hapus baris';del.addEventListener('click',()=>row.remove());row.append(del);document.getElementById('menuRows').append(row);});
</script>