<div id="toasts" class="toasts" role="status" aria-live="polite">
<?php foreach (flash_get() as $f):
  $c = ['success' => 't-success', 'error' => 't-error', 'warning' => 't-warning'][$f['t']] ?? 't-info'; ?>
  <div class="toast <?= $c ?>"><span class="m"><?= e($f['m']) ?></span><button type="button" aria-label="Tutup" onclick="this.parentElement.remove()">&times;</button></div>
<?php endforeach; ?>
</div>
