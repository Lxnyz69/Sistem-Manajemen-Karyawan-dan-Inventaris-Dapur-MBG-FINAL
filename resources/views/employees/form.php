<?php $edit = (bool)$u; $v = fn($k, $d = '') => old($k, $u[$k] ?? $d); $self = $edit && (int)$u['id'] === (int)auth_user()['id']; ?>
<form method="post" class="panel narrow" action="<?= e(url($edit ? '/admin/karyawan/' . $u['id'] . '/edit' : '/admin/karyawan/tambah')) ?>">
  <?= csrf_field() ?>
  <?php if ($edit): ?><div class="stat-inline u-mb">ID Karyawan: <b><?= e($u['employee_code'] ?? '-') ?></b> <span class="u-muted">(tidak dapat diubah)</span></div><?php endif; ?>
  <div class="form-grid cols-2">
    <div class="field span-2"><label class="label" for="fn">Nama lengkap</label><input class="input" id="fn" name="full_name" value="<?= e($v('full_name')) ?>" required></div>
    <div class="field"><label class="label" for="wa">Nomor WhatsApp</label><input class="input" id="wa" name="whatsapp" value="<?= e($v('whatsapp')) ?>" required></div>
    <div class="field"><label class="label" for="ja">Tanggal bergabung</label><input class="input" id="ja" type="date" name="joined_at" value="<?= e($v('joined_at', date('Y-m-d'))) ?>"></div>
    <div class="field span-2"><label class="label" for="ad">Alamat</label><textarea class="input" id="ad" name="address" rows="2" required><?= e($v('address')) ?></textarea></div>
    <div class="field"><label class="label" for="rl">Role</label>
      <select class="input" id="rl" name="role_id" required <?= $self ? 'disabled' : '' ?>><option value="">— pilih —</option>
        <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= (int)$v('role_id') === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select>
      <?php if ($self): ?><p class="hint">Anda tidak dapat mengubah role sendiri.</p><?php endif; ?></div>
    <?php if (!$edit): ?>
      <div class="field"><label class="label" for="pw">Password awal</label><input class="input" id="pw" type="password" name="password" minlength="8" required autocomplete="new-password">
        <p class="hint">Minimal 8 karakter. Karyawan wajib menggantinya saat login pertama.</p></div>
    <?php endif; ?>
  </div>
  <div class="form-actions"><button class="btn btn-primary">Simpan</button><a class="btn btn-ghost" href="<?= e(url('/admin/karyawan')) ?>">Batal</a></div>
</form>
