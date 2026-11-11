<?php $states = delivery_states(); $steps = array_keys($states); ?>
<header class="pub-head">
  <div class="pub-head-in">
    <?php if (setting('logo')): ?><img src="<?= e(url(setting('logo'))) ?>" alt=""><?php else: ?><span class="mark"><i class="fa-solid fa-building"></i></span><?php endif; ?>
    <div class="u-grow" style="font-weight:600"><?= e(setting('site_name')) ?></div>
    <span class="live-pill" id="livePill" data-state="on"><i></i>Live</span>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/#daftar-sekolah')) ?>"><i class="fa-solid fa-arrow-left"></i> Daftar sekolah</a>
  </div>
</header>
<main class="pub-main" id="liveRoot" data-live="1">
  <h1><?= e($sc['name']) ?></h1>
  <p class="lead"><?= e($sc['address']) ?> · Jumlah siswa: <?= num($sc['daily_portions']) ?></p>

  <form class="pub-datebar" method="get" action="<?= e(url('/sekolah/' . $sc['id'])) ?>">
    <a class="btn btn-ghost btn-sm" href="?date=<?= e($prev) ?>" aria-label="Hari sebelumnya"><i class="fa-solid fa-chevron-left"></i></a>
    <input class="input input-sm input-auto" type="date" name="date" value="<?= e($date) ?>" aria-label="Pilih tanggal">
    <button class="btn btn-ghost btn-sm">Lihat</button>
    <a class="btn btn-ghost btn-sm" href="?date=<?= e($next) ?>" aria-label="Hari berikutnya"><i class="fa-solid fa-chevron-right"></i></a>
    <?php if (!$isToday): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/sekolah/' . $sc['id'])) ?>">Hari ini</a><?php endif; ?>
    <strong class="pub-datebar-label"><?= e(tgl_panjang($date)) ?><?= $isToday ? ' · Hari ini' : '' ?></strong>
  </form>

  <section class="panel panel-flush u-mb">
    <div class="panel-head"><h2 class="panel-title"><?= $isToday ? 'Ringkasan hari ini' : 'Ringkasan tanggal ini' ?></h2></div>
    <div class="metrics plain">
      <?= stat_card('', 'Menu', num(count($menus))) ?>
      <?= stat_card('', 'Porsi direncanakan', num($day['planned'])) ?>
      <?= stat_card('', 'Pengiriman', num($day['deliveries'])) ?>
      <?= stat_card('', 'Porsi sudah dikirim', num($day['sent'])) ?>
      <?= stat_card('', 'Sampai / diterima', num($day['completed']) . ' dari ' . num($day['deliveries'])) ?>
      <?= stat_card('', 'Terlambat', num($day['late']), '#b3261e', $day['late'] > 0) ?>
      <?= stat_card('', 'Pengiriman selesai', $day['percent'] . '%') ?>
    </div>
  </section>

  <?php if ($isToday): $trackSchool = (int)$sc['id']; require ROOT . '/resources/views/partials/track_map.php'; endif; ?>

  <section class="panel panel-flush u-mb">
    <div class="panel-head"><h2 class="panel-title">Menu</h2></div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Menu</th><th class="num">Porsi</th><th>Status menu</th></tr></thead><tbody>
      <?php foreach ($menus as $mn): ?>
      <tr><td><strong><?= e($mn['name']) ?></strong><?php if ($mn['description']): ?><span class="sub"><?= e($mn['description']) ?></span><?php endif; ?></td><td class="num"><?= num($mn['portions']) ?></td><td><?= e(menu_status_label($mn['status'])) ?></td></tr>
      <?php endforeach; if (!$menus): ?><tr><td class="empty" colspan="3">Belum ada menu untuk tanggal ini.</td></tr><?php endif; ?>
    </tbody></table></div>
  </section>

  <section class="panel panel-flush u-mb">
    <div class="panel-head"><h2 class="panel-title">Status pengantaran</h2></div>
    <div class="panel-body pub-deliveries">
      <?php foreach ($deliveries as $d): $cur = (int)array_search($d['status'], $steps, true); ?>
      <article class="pub-delivery">
        <div class="pub-delivery-head">
          <div><strong><?= e($d['menu_name']) ?></strong><span class="u-muted u-small"> · <?= num($d['portions']) ?> porsi</span></div>
          <div><?= delivery_badge($d['status']) ?><?php if ($d['late']): ?> <span class="badge badge-red">Terlambat</span><?php endif; ?></div>
        </div>
        <ol class="pub-steps">
          <?php foreach ($steps as $i => $key): ?><li class="<?= $i < $cur ? 'done' : ($i === $cur ? 'now' : '') ?>"><span><?= e($states[$key]) ?></span></li><?php endforeach; ?>
        </ol>
        <div class="pub-times">
          <div><span class="u-muted">Estimasi tiba</span><b><?= $d['eta_arrival'] ? e(dmyhi($d['eta_arrival'])) : 'Estimasi belum tersedia' ?></b></div>
          <div><span class="u-muted">Aktual tiba</span><b><?= $d['arrived_at'] ? e(dmyhi($d['arrived_at'])) : 'Belum tiba' ?></b></div>
          <div><span class="u-muted">Aktual diterima</span><b><?= $d['received_at'] ? e(dmyhi($d['received_at'])) : 'Belum diterima' ?></b></div>
        </div>
        <?php if ($d['history']): ?>
        <div class="dist-timeline">
          <?php foreach ($d['history'] as $h): ?><div class="dist-timeline-item"><b><?= e($states[$h['status']] ?? $h['status']) ?></b><span><?= e(dmyhi($h['changed_at'])) ?></span><?php if ($h['note']): ?><small><?= e($h['note']) ?></small><?php endif; ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($d['notes']): ?><p class="u-small u-muted" style="margin:.6rem 0 0">Catatan: <?= e($d['notes']) ?></p><?php endif; ?>
      </article>
      <?php endforeach; if (!$deliveries): ?><p class="empty-note">Belum ada pengiriman untuk tanggal ini.</p><?php endif; ?>
    </div>
  </section>

  <section class="panel panel-flush">
    <div class="panel-head"><h2 class="panel-title">Riwayat per hari</h2><span class="panel-sub">14 hari tercatat terakhir</span></div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Tanggal</th><th class="num">Menu</th><th>Status pengantaran</th></tr></thead><tbody>
      <?php foreach ($days as $r): ?>
      <tr<?= $r['date'] === $date ? ' class="pub-row-on"' : '' ?>><td><a class="school-name" href="?date=<?= e($r['date']) ?>"><?= e(tgl_panjang($r['date'])) ?></a></td><td class="num"><?= num($r['menus']) ?></td><td><?= delivery_day_summary($r['statuses']) ?></td></tr>
      <?php endforeach; if (!$days): ?><tr><td class="empty" colspan="3">Belum ada data menu atau pengantaran.</td></tr><?php endif; ?>
    </tbody></table></div>
  </section>
</main>
<footer class="pub-foot"><?= e(setting('footer_text')) ?></footer>
