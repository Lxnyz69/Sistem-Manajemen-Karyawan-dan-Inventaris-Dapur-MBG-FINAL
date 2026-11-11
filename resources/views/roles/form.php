<?php $edit = (bool)$r; $byMod = []; foreach ($perms as $p) $byMod[$p['module']][$p['action']] = $p['id'];
$modules = RoleController::MODULES;
foreach (array_keys($byMod) as $m) if (!isset($modules[$m])) $modules[$m] = ucwords(str_replace('_', ' ', $m)); // modul baru tetap tampil ?>
<form method="post" class="panel panel-flush wide" action="<?= e(url($edit ? '/admin/role/' . $r['id'] . '/edit' : '/admin/role/baru')) ?>"><?= csrf_field() ?>
  <div class="panel-body">
    <div class="form-grid cols-2">
      <div class="field"><label class="label" for="rn">Nama role</label><input class="input" id="rn" name="name" value="<?= e(old('name', $r['name'] ?? '')) ?>" required></div>
      <div class="field"><label class="label" for="rd">Deskripsi</label><input class="input" id="rd" name="description" value="<?= e(old('description', $r['description'] ?? '')) ?>"></div>
    </div>
  </div>
  <div class="panel-head" style="border-top:1px solid var(--line-soft)"><h2 class="panel-title">Hak akses</h2><span class="panel-sub">Centang aksi yang boleh dilakukan role ini.</span></div>
  <div class="table-wrap"><table class="tbl perm-table"><thead><tr><th>Modul</th><?php foreach (RoleController::ACTIONS as $l): ?><th><?= $l ?></th><?php endforeach; ?></tr></thead><tbody>
  <?php foreach ($modules as $m => $label): if (empty($byMod[$m])) continue; ?>
    <tr><td class="strong"><?= e($label) ?></td>
    <?php foreach (RoleController::ACTIONS as $a => $l): ?><td><?php if (isset($byMod[$m][$a])): $pid = (int)$byMod[$m][$a]; ?>
      <input type="checkbox" name="perm[]" value="<?= $pid ?>" aria-label="<?= e($label . ' — ' . $l) ?>" <?= in_array($pid, $sel, true) ? 'checked' : '' ?>><?php else: ?><span class="u-muted" style="opacity:.4">–</span><?php endif; ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?></tbody></table></div>
  <div class="panel-body"><div class="form-actions" style="margin-top:0;border-top:0;padding-top:0"><button class="btn btn-primary">Simpan</button><a class="btn btn-ghost" href="<?= e(url('/admin/role')) ?>">Batal</a></div></div>
</form>
