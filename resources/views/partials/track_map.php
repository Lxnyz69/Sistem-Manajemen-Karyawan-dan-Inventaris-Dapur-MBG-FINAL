<?php
/* Panel peta pelacakan kurir (live). Variabel opsional: $trackSchool (ID sekolah; 0 = semua). */
$GLOBALS['__track'] = true;
?>
<section class="panel panel-flush u-mb track-panel" aria-label="Peta pengantaran live">
  <div class="panel-head"><h2 class="panel-title u-grow">Peta Pengantaran Live</h2><span class="panel-sub" id="trackSub" data-live-ignore>Memuat peta…</span></div>
  <div class="track-map" data-track-map data-live-ignore data-school="<?= (int)($trackSchool ?? 0) ?>" role="region" aria-label="Posisi kurir pada peta"></div>
  <ul class="track-list" id="trackList" data-live-ignore></ul>
  <div class="panel-foot">Posisi kurir dibagikan dari HP petugas selama status “Dalam Pengantaran” dan berhenti otomatis setelah sampai di sekolah. Diperbarui otomatis.</div>
</section>
