<?php $edit = (bool)$i; $v = fn($k, $d = '') => old($k, $i[$k] ?? $d); ?>
<form method="post" enctype="multipart/form-data" class="panel wide" action="<?= e(url($edit ? '/admin/stok/barang/' . $i['id'] . '/edit' : '/admin/stok/barang/baru')) ?>">
  <?= csrf_field() ?>
  <div class="form-grid cols-2">
    <div class="field"><label class="label" for="code">Kode barang</label><input class="input" id="code" name="code" value="<?= e($v('code')) ?>" required></div>
    <div class="field"><label class="label" for="name">Nama barang</label><input class="input" id="name" name="name" value="<?= e($v('name')) ?>" required></div>
    <div class="field"><label class="label" for="unit">Satuan</label><select class="input" id="unit" name="unit" required><?php foreach (InventoryController::UNITS as $un): ?><option <?= $v('unit') === $un ? 'selected' : '' ?>><?= $un ?></option><?php endforeach; ?></select></div>
    <div class="field"><label class="label" for="price">Harga per unit (Rp)</label><input class="input" id="price" name="price" inputmode="numeric" value="<?= e($edit ? rtrim(rtrim((string)$i['price'], '0'), '.') : $v('price', 0)) ?>" required></div>
    <?php if (!$edit): ?><div class="field"><label class="label" for="stock">Stok awal</label><input class="input" id="stock" type="number" min="0" name="stock" value="<?= e(old('stock', 0)) ?>"></div><?php endif; ?>
    <div class="field"><label class="label" for="min">Batas stok menipis</label><input class="input" id="min" type="number" min="0" name="min_stock" value="<?= e($v('min_stock', 5)) ?>"><p class="hint">Barang ditandai menipis bila stok sama dengan atau di bawah angka ini.</p></div>
    <div class="field span-2"><label class="label" for="desc">Deskripsi</label><textarea class="input" id="desc" name="description" rows="2"><?= e($v('description')) ?></textarea></div>
    <div class="field span-2"><label class="label" for="photo">Foto barang</label>
      <img id="pv" class="img-preview lg <?= $edit && $i['photo'] ? '' : 'hidden' ?>" src="<?= $edit && $i['photo'] ? e(url($i['photo'])) : '' ?>" alt="Pratinjau">
      <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="#pv">
      <p class="hint">JPG, PNG, atau WebP. Foto besar dari HP dikecilkan otomatis.</p></div>
  </div>
  <?php if ($edit): ?><div class="notice notice-info u-mb" style="margin-top:1rem;margin-bottom:0">Stok hanya berubah lewat pengajuan stok, barang keluar, atau penyesuaian agar riwayat tetap konsisten.</div><?php endif; ?>
  <div class="form-actions"><button class="btn btn-primary">Simpan</button><a class="btn btn-ghost" href="<?= e(url('/admin/stok')) ?>">Batal</a></div>
</form>
