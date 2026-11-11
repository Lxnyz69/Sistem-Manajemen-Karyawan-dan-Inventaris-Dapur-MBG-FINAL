<?php
/* Lonceng notifikasi: dropdown di samping profil, maks. 10 terbaru (terbaru di atas, lengkap dengan jam),
   plus tombol "Lihat semua notifikasi". Daftar dirender oleh live.js dari data di bawah dan diperbarui otomatis. */
$nUid = (int)(auth_user()['id'] ?? 0);
$nUnread = Notify::unread($nUid);
$nBoot = ['items' => array_map([Notify::class, 'format'], Notify::recent($nUid)), 'key' => Notify::key($nUid), 'unread' => $nUnread, 'now' => time()];
$nAll = url('/notifikasi');
?>
<div class="notif" id="notif" data-read-url="<?= e(url('/notifikasi/baca')) ?>">
  <button type="button" class="icon-btn notif-btn" id="notifBtn" aria-haspopup="dialog" aria-expanded="false" aria-controls="notifPanel" aria-label="Notifikasi<?= $nUnread ? ', ' . $nUnread . ' belum dibaca' : '' ?>" title="Notifikasi">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
    <span class="dot" id="notifDot"<?= $nUnread > 0 ? '' : ' hidden' ?>><?= $nUnread > 99 ? '99+' : $nUnread ?></span>
  </button>
  <div class="notif-panel" id="notifPanel" role="dialog" aria-label="Notifikasi" hidden>
    <div class="notif-head"><b>Notifikasi</b><button type="button" class="notif-link" id="notifReadAll"<?= $nUnread > 0 ? '' : ' hidden' ?>>Tandai semua dibaca</button></div>
    <ul class="notif-list" id="notifList"></ul>
    <p class="notif-empty" id="notifEmpty" hidden>Belum ada notifikasi.</p>
    <a class="notif-all" href="<?= e($nAll) ?>">Lihat semua notifikasi</a>
  </div>
  <script type="application/json" id="notifBoot"><?= json_encode($nBoot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</div>
