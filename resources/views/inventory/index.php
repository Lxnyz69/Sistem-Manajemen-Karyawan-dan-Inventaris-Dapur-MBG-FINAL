<?php $qs = array_filter(['q' => $_GET['q'] ?? '', 'low' => $_GET['low'] ?? '', 'unit' => $_GET['unit'] ?? '']); $nf = count($qs); ?>
<section class="panel panel-flush">
  <div class="panel-head">
    <div class="u-grow"><h2 class="panel-title">Daftar barang</h2><div class="panel-sub"><?= num($pg['total']) ?> barang</div></div>
    <?php if (can('stok.export')): ?><a class="btn btn-ghost" href="<?= e(url('/admin/stok/export?' . http_build_query($qs))) ?>"><i class="fa-solid fa-file-csv"></i> Ekspor CSV</a><?php endif; ?>
    <?php if (can('stok.create')): ?><a class="btn btn-primary" href="<?= e(url('/admin/stok/barang/baru')) ?>"><i class="fa-solid fa-plus"></i> Tambah barang</a><?php endif; ?>
  </div>
  <div class="filters <?= $nf ? 'is-open' : '' ?>">
    <button type="button" class="filters-toggle"><span><i class="fa-solid fa-sliders"></i> Filter</span><span class="count"><?= $nf ? $nf . ' aktif' : '' ?></span></button>
    <form method="get" class="filters-body">
      <div class="filters-grid">
        <div class="f-wide search"><label class="label" for="fq">Pencarian</label><i class="fa-solid fa-magnifying-glass"></i><input class="input" id="fq" name="q" placeholder="Kode atau nama barang" value="<?= e($_GET['q'] ?? '') ?>"></div>
        <div><label class="label" for="fu">Satuan</label><select class="input" id="fu" name="unit"><option value="">Semua satuan</option><?php foreach (InventoryController::UNITS as $un): ?><option <?= ($_GET['unit'] ?? '') === $un ? 'selected' : '' ?>><?= $un ?></option><?php endforeach; ?></select></div>
        <div><label class="label">&nbsp;</label><label class="check" style="min-height:2.25rem"><input type="checkbox" name="low" value="1" <?= ($_GET['low'] ?? '') === '1' ? 'checked' : '' ?>> Hanya stok menipis</label></div>
        <div class="filters-actions"><button class="btn btn-primary">Terapkan</button><a class="btn btn-ghost" href="<?= e(url('/admin/stok')) ?>">Reset</a></div>
      </div>
    </form>
  </div>
  <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>Foto</th><th data-sort>Kode</th><th data-sort>Nama</th><th>Satuan</th><th class="num" data-sort>Harga</th><th class="num" data-sort>Stok</th><th class="num" data-sort>Total nilai</th><th>Diperbarui</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach ($pg['rows'] as $i): $low = $i['stock'] <= $i['min_stock']; ?>
    <tr><td class="cell-main"><?php if ($i['photo']): ?><img src="<?= e(url($i['photo'])) ?>" class="thumb" alt=""><?php else: ?><div class="thumb"><i class="fa-regular fa-image"></i></div><?php endif; ?></td>
    <td data-label="Kode"><?= e($i['code']) ?></td>
    <td data-label="Nama" class="strong"><?= e($i['name']) ?><?php if ($i['description']): ?><span class="sub trunc" style="max-width:16rem"><?= e($i['description']) ?></span><?php endif; ?></td>
    <td data-label="Satuan"><?= e($i['unit']) ?></td><td data-label="Harga" class="num"><?= rupiah($i['price']) ?></td>
    <td data-label="Stok" class="num"><?= $low ? '<span class="badge badge-red">menipis</span> ' : '' ?><?= num($i['stock']) ?></td><td data-label="Total nilai" class="num"><?= rupiah($i['total_value']) ?></td><td data-label="Diperbarui"><?= dmyhi($i['updated_at']) ?></td>
    <td class="cell-actions"><div class="row-actions">
      <a class="btn btn-ghost btn-sm btn-icon" href="<?= e(url('/admin/stok/barang/' . $i['id'])) ?>" title="Detail dan riwayat" aria-label="Detail dan riwayat"><i class="fa-solid fa-eye"></i></a>
      <?php if (can('stok.edit')): ?><a class="btn btn-ghost btn-sm btn-icon" href="<?= e(url('/admin/stok/barang/' . $i['id'] . '/edit')) ?>" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i></a><?php endif; ?>
      <?php if (can('stok.delete')): ?><form method="post" action="<?= e(url('/admin/stok/barang/' . $i['id'] . '/hapus')) ?>" data-confirm="Hapus barang ini?"><?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-icon text-red-600" title="Hapus" aria-label="Hapus"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?>
    </div></td></tr>
  <?php endforeach; if (!$pg['rows']): ?><tr><td colspan="9" class="empty">Belum ada barang yang cocok.</td></tr><?php endif; ?>
  </tbody></table></div>
  <?= pager($pg, $qs) ?>
</section>
