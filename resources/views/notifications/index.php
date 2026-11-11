<?php
$qs = fn(array $x = []) => '?' . http_build_query(array_filter(array_merge(['m' => !empty($field) ? 1 : null], $x)));
?>
<section class="panel panel-flush">
  <div class="panel-head">
    <div class="u-grow"><h2 class="panel-title">Semua notifikasi</h2><div class="panel-sub"><?= num($pg['total']) ?> notifikasi · <?= num($unread) ?> belum dibaca</div></div>
    <a class="btn btn-ghost btn-sm" href="<?= e($qs($unreadOnly ? [] : ['unread' => 1])) ?>"><?= $unreadOnly ? 'Tampilkan semua' : 'Hanya belum dibaca' ?></a>
    <?php if ($unread > 0): ?>
    <form method="post" action="<?= e(url('/notifikasi/baca')) ?>"><?= csrf_field() ?><input type="hidden" name="all" value="1"><button class="btn btn-ghost btn-sm">Tandai semua dibaca</button></form>
    <?php endif; ?>
  </div>
  <ul class="notif-page">
    <?php foreach ($pg['rows'] as $row): $n = Notify::format($row); ?>
    <li><a class="notif-row<?= $n['read'] ? '' : ' is-unread' ?>" href="<?= e($n['link'] ?: '#') ?>" data-nid="<?= $n['id'] ?>">
      <span class="notif-ico" aria-hidden="true">🔔</span>
      <span class="notif-txt"><b><?= e($n['title']) ?></b><?php if ($n['body']): ?><span class="notif-body"><?= e($n['body']) ?></span><?php endif; ?>
        <time data-ts="<?= $n['ts'] ?>"><span class="t-clock"><?= e($n['label']) ?></span> · <span class="t-rel"><?= e(rel_time($n['ts'])) ?></span></time></span>
      <?php if (!$n['read']): ?><i class="notif-unread" aria-label="Belum dibaca"></i><?php endif; ?>
    </a></li>
    <?php endforeach; if (!$pg['rows']): ?><li><p class="empty-note"><?= $unreadOnly ? 'Tidak ada notifikasi yang belum dibaca.' : 'Belum ada notifikasi.' ?></p></li><?php endif; ?>
  </ul>
  <?= pager($pg, array_filter(['unread' => $unreadOnly ? 1 : null, 'm' => !empty($field) ? 1 : null])) ?>
</section>
