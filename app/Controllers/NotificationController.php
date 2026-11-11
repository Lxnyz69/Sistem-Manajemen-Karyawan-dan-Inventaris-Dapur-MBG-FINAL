<?php
class NotificationController {
    /** Halaman "Lihat semua notifikasi" (terbaru di atas). */
    function index(): void {
        $u = Auth::user(); $uid = (int)$u['id'];
        $unreadOnly = ($_GET['unread'] ?? '') === '1';
        $pg = DB::paginate('*', 'notifications', 'WHERE user_id=?' . ($unreadOnly ? ' AND is_read=0' : ''), [$uid], 'id DESC', (int)($_GET['page'] ?? 1), 20);
        render('notifications/index', ['pg' => $pg, 'unreadOnly' => $unreadOnly, 'unread' => Notify::unread($uid), 'field' => false, 'title' => 'Notifikasi'], 'layouts/app');
    }
    /** Tandai dibaca: satu (id) atau semua (all=1). Mengembalikan JSON bila dipanggil via fetch. */
    function read(): void {
        $uid = (int)Auth::user()['id'];
        Notify::markRead($uid, (int)($_POST['id'] ?? 0) ?: null, ($_POST['all'] ?? '') === '1');
        if (is_live_request() || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json'))
            json_out(['ok' => true, 'unread' => Notify::unread($uid), 'key' => Notify::key($uid)]);
        back('/notifikasi');
    }
}
