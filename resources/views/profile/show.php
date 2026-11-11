<div class="grid-2">
  <section class="panel"><h2 class="panel-title u-mb">Identitas</h2>
  <?php if (can('profile.edit')): ?>
    <form method="post" action="<?= e(url('/profile/identitas')) ?>" class="stack" style="gap:.9rem"><?= csrf_field() ?>
      <div class="stat-inline">ID <b><?= e($u['employee_code'] ?? '-') ?></b> · Role <b><?= e($u['role_name'] ?? '-') ?></b> · <?= badge($u['status']) ?></div>
      <div class="field"><label class="label" for="pn">Nama lengkap</label><input class="input" id="pn" name="full_name" value="<?= e($u['full_name']) ?>" required></div>
      <div class="field"><label class="label" for="pu">Username</label><input class="input" id="pu" name="username" value="<?= e($u['username']) ?>" required></div>
      <div class="field"><label class="label" for="pw">WhatsApp</label><input class="input" id="pw" name="whatsapp" value="<?= e($u['whatsapp']) ?>" required></div>
      <div class="field"><label class="label" for="pa">Alamat</label><textarea class="input" id="pa" name="address" rows="2" required><?= e($u['address']) ?></textarea></div>
      <div><button class="btn btn-primary">Simpan perubahan</button></div></form>
  <?php else: ?>
    <dl class="kv stacked">
      <?php foreach (['ID Karyawan' => $u['employee_code'] ?? '-', 'Nama lengkap' => $u['full_name'], 'WhatsApp' => $u['whatsapp'], 'Alamat' => $u['address'], 'Role' => $u['role_name'] ?? '-', 'Tanggal bergabung' => dmy($u['joined_at'])] as $k => $val): ?>
        <div><dt class="u-small"><?= $k ?></dt><dd><?= e($val) ?></dd></div><?php endforeach; ?>
      <div><dt class="u-small">Status</dt><dd><?= badge($u['status']) ?></dd></div>
    </dl><p class="hint" style="margin-top:.75rem">Perubahan identitas hanya dapat dilakukan administrator.</p>
  <?php endif; ?></section>
  <?php if (can('password.edit')): ?>
  <section class="panel" style="align-self:start"><h2 class="panel-title">Keamanan</h2><p class="u-muted" style="margin:.35rem 0 1rem">Ganti password secara berkala.</p>
    <a class="btn btn-primary" href="<?= e(url('/profile/password')) ?>">Ganti password</a></section>
  <?php endif; ?>
</div>
