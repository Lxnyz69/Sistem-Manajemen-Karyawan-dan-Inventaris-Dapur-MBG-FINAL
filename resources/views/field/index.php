<?php
$states = delivery_states(); $steps = array_keys($states);
$next = ['preparing' => ['in_transit', 'Mulai antar', 'fa-truck-fast'], 'in_transit' => ['arrived', 'Sudah sampai di sekolah', 'fa-location-dot'], 'arrived' => ['received', 'Makanan sudah diterima sekolah', 'fa-circle-check']];
$active = count(array_filter($rows, fn($r) => $r['status'] !== 'received'));
$first = explode(' ', trim((string)auth_user()['full_name']))[0];
?>
<div class="f-hello">
  <span class="ico"><i class="fa-solid fa-truck-fast"></i></span>
  <div><h2>Halo, <?= e($first) ?></h2><p><?= e(tgl_panjang(date('Y-m-d'))) ?> · <?= $active ? $active . ' tugas aktif' : 'tidak ada tugas aktif' ?></p></div>
</div>

<div class="gps" id="gpsBar" data-state="wait" data-live-ignore><span id="gpsText">Menunggu GPS…</span><small class="gps-sync" id="fSync"></small></div>

<div class="adv-modal" id="advModal" data-live-ignore hidden role="dialog" aria-modal="true" aria-labelledby="advTitle">
  <div class="adv-box">
    <div class="adv-ico"><i class="fa-solid fa-circle-question"></i></div>
    <h3 id="advTitle">Konfirmasi</h3>
    <p id="advText"></p>
    <div class="adv-actions">
      <button type="button" class="btn btn-ghost" id="advNo">Batal</button>
      <button type="button" class="btn btn-primary" id="advYes">Ya, lanjutkan</button>
    </div>
  </div>
</div>

<div class="gps-gate" id="gpsGate" data-mode="off" data-live-ignore role="alertdialog" aria-modal="true" aria-labelledby="gateTitle">
  <div class="gps-gate-card">
    <div class="gps-gate-ico"><i class="fa-solid fa-location-crosshairs"></i></div>
    <h2 id="gateTitle">Aktifkan GPS untuk melanjutkan</h2>
    <p id="gateMsg">Halaman Tugas Kurir hanya dapat dipakai saat lokasi (GPS) perangkat aktif.</p>
    <ol id="gateSteps" hidden></ol>
    <button type="button" class="btn btn-primary" id="gateBtn">Aktifkan GPS</button>
  </div>
</div>

<?php if ($rows): ?>
<div class="f-search" data-live-ignore><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" id="jobFilter" placeholder="Cari nama sekolah…" autocomplete="off" aria-label="Cari tugas berdasarkan nama sekolah"><button type="button" id="jobFilterClr" aria-label="Hapus pencarian" hidden>&times;</button></div>
<div class="f-empty" id="jobNone" data-live-ignore hidden><i class="fa-solid fa-magnifying-glass"></i>Tidak ada tugas untuk sekolah tersebut.</div>
<?php endif; ?>
<?php foreach ($rows as $d): $cur = (int)array_search($d['status'], $steps, true); $n = $next[$d['status']] ?? null; $done = $d['status'] === 'received'; ?>
<article data-live-key="d<?= (int)$d['id'] ?>" data-name="<?= e(mb_strtolower($d['school_name'])) ?>" class="job<?= $done ? ' done' : '' ?><?= $d['status'] === 'in_transit' ? ' is-transit' : '' ?>" data-delivery="<?= (int)$d['id'] ?>" data-status="<?= e($d['status']) ?>">
  <div class="job-top">
    <div><h3><?= e($d['school_name']) ?></h3><p class="job-sub"><?= e($d['menu_name']) ?> · <?= num($d['portions']) ?> porsi</p></div>
    <?= delivery_badge($d['status']) ?>
  </div>
  <ol class="steps"><?php foreach ($steps as $i => $k): ?><li class="<?= $i < $cur ? 'done' : ($i === $cur ? 'now' : '') ?>"><?= e($states[$k]) ?></li><?php endforeach; ?></ol>
  <div class="job-addr"><i class="fa-solid fa-location-dot"></i><?= e($d['school_address']) ?></div>
  <div class="job-info">
    <div>Tanggal<b><?= e(dmy($d['delivery_date'])) ?></b></div>
    <div>Estimasi tiba<b><?= $d['eta_arrival'] ? e(dmyhi($d['eta_arrival'])) : '-' ?></b></div>
  </div>
  <?php if ($n): ?>
  <div class="job-act">
    <details><summary>Tambah catatan</summary><textarea name="notes" maxlength="500" placeholder="Kendala atau catatan (opsional)"><?= e($d['notes']) ?></textarea></details>
    <button type="button" class="job-btn" data-act="advance" data-school="<?= e($d['school_name']) ?>" data-portions="<?= (int)$d['portions'] ?>" data-id="<?= (int)$d['id'] ?>" data-next="<?= $n[0] ?>"><i class="fa-solid <?= $n[2] ?>"></i> <?= e($n[1]) ?></button>
    <div class="job-links">
      <a href="<?= e(maps_link($d['s_lat'], $d['s_lng'], $d['school_address'])) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-route"></i> Buka rute</a>
      <a href="<?= e(wa_link($d['school_wa'])) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
    </div>
  </div>
  <?php else: ?><div class="job-act"><i class="fa-solid fa-circle-check"></i> Selesai<?= $d['received_at'] ? ' · diterima ' . e(dmyhi($d['received_at'])) : '' ?></div><?php endif; ?>
</article>
<?php endforeach; if (!$rows): ?>
<div class="f-empty"><i class="fa-regular fa-clipboard"></i>Belum ada tugas pengantaran untuk Anda.<br>Tugas baru akan muncul di sini otomatis.</div>
<?php endif; ?>
