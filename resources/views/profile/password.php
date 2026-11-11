<form method="post" action="<?= e(url('/profile/password')) ?>" class="panel narrow-sm"><?= csrf_field() ?>
  <?php if (auth_user()['must_change_password']): ?><div class="notice notice-warn">Anda masih memakai password bawaan. Ganti sekarang untuk melanjutkan.</div><?php endif; ?>
  <div class="stack" style="gap:.9rem">
    <div class="field"><label class="label" for="o">Password lama</label><input class="input" id="o" type="password" name="old" required autocomplete="current-password"></div>
    <div class="field"><label class="label" for="n">Password baru</label><input class="input" id="n" type="password" name="new" minlength="8" required autocomplete="new-password"><p class="hint">Minimal 8 karakter.</p></div>
    <div class="field"><label class="label" for="n2">Ulangi password baru</label><input class="input" id="n2" type="password" name="new2" minlength="8" required autocomplete="new-password"></div>
  </div>
  <div class="form-actions"><button class="btn btn-primary">Simpan password</button></div>
</form>
