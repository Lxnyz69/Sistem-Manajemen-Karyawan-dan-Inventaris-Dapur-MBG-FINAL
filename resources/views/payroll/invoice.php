<style>@media print{.no-print{display:none!important}body{background:#fff!important}.sheet{box-shadow:none!important;margin:0!important;width:auto!important;min-height:0!important}}</style>
<div class="no-print doc-bar">
  <button type="button" class="btn btn-ghost" onclick="history.length>1?history.back():location.href='<?= e(url('/')) ?>'">&larr; Kembali</button>
  <span class="u-grow"></span>
  <button type="button" class="btn btn-ghost" data-keep="1" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak</button>
  <?php if ($canPdf): ?><a class="btn btn-primary" href="<?= e(url($pdfUrl)) ?>"><i class="fa-solid fa-file-pdf"></i> Unduh PDF</a><?php endif; ?>
</div>
<div class="sheet"><?php require __DIR__ . '/doc.php'; ?></div>
