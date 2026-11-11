<?php
/**
 * Notifikasi dalam aplikasi (lonceng) + peringatan stok menipis.
 * Semua method aman dipanggil dari alur bisnis: kegagalan hanya dicatat ke log, tidak menjatuhkan request.
 */
class Notify {
    const LIST_MAX = 10;

    private static function permWhere(): string {
        return "u.status='active' AND (r.is_super=1 OR EXISTS (SELECT 1 FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=u.role_id AND p.code=?))";
    }

    static function toUser(int $uid, string $type, string $title, string $body = '', string $link = ''): void {
        try {
            DB::exec('INSERT INTO notifications (user_id,type,title,body,link) VALUES (?,?,?,?,?)',
                [$uid, $type, mb_substr($title, 0, 150), mb_substr($body, 0, 500), mb_substr($link, 0, 255)]);
        } catch (Throwable $e) { app_log('Notify::toUser ' . $e->getMessage()); }
    }
    /** Kirim ke semua pengguna aktif yang punya hak akses $perm (Admin utama selalu ikut). */
    static function toPerm(string $perm, string $type, string $title, string $body = '', string $link = '', ?int $except = null): int {
        try {
            $sql = 'INSERT INTO notifications (user_id,type,title,body,link) SELECT u.id,?,?,?,? FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE ' . self::permWhere() . ($except ? ' AND u.id<>?' : '');
            $p = [$type, mb_substr($title, 0, 150), mb_substr($body, 0, 500), mb_substr($link, 0, 255), $perm];
            if ($except) $p[] = $except;
            return DB::exec($sql, $p);
        } catch (Throwable $e) { app_log('Notify::toPerm ' . $e->getMessage()); return 0; }
    }

    static function recent(int $uid, int $limit = self::LIST_MAX): array {
        try { return DB::all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT ' . max(1, $limit), [$uid]); }
        catch (Throwable $e) { return []; }
    }
    static function unread(int $uid): int {
        try { return (int)DB::val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$uid]); }
        catch (Throwable $e) { return 0; }
    }
    /** Sidik jari singkat (id terbaru : jumlah belum dibaca) agar klien tahu kapan daftar berubah. */
    static function key(int $uid): string {
        try { return (int)DB::val('SELECT COALESCE(MAX(id),0) FROM notifications WHERE user_id=?', [$uid]) . ':' . self::unread($uid); }
        catch (Throwable $e) { return '0:0'; }
    }
    static function markRead(int $uid, ?int $id = null, bool $all = false): void {
        try {
            if ($all) DB::exec('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0', [$uid]);
            elseif ($id) DB::exec('UPDATE notifications SET is_read=1 WHERE user_id=? AND id=? AND is_read=0', [$uid, $id]);
        } catch (Throwable $e) { app_log('Notify::markRead ' . $e->getMessage()); }
    }
    static function format(array $n): array {
        $ts = strtotime((string)$n['created_at']) ?: time();
        return ['id' => (int)$n['id'], 'type' => $n['type'], 'title' => $n['title'], 'body' => (string)$n['body'],
            'link' => $n['link'] ? url($n['link']) : '', 'read' => (bool)$n['is_read'], 'ts' => $ts, 'label' => clock_label($ts)];
    }

    // ------------------------------------------------------------------ peringatan stok menipis
    /** Dipanggil setelah stok / batas minimum berubah. Peringatan hanya dikirim sekali per "turun ke bawah batas". */
    static function checkLowStock(int $itemId): void {
        try {
            $i = DB::one('SELECT * FROM inventory_items WHERE id=?', [$itemId]);
            if (!$i) return;
            if ((int)$i['stock'] <= (int)$i['min_stock']) {
                if (DB::exec('UPDATE inventory_items SET low_alerted=1, updated_at=updated_at WHERE id=? AND low_alerted=0', [$itemId]) === 1) self::lowStockAlert([$i]);
            } elseif ((int)$i['low_alerted'] === 1) {
                DB::exec('UPDATE inventory_items SET low_alerted=0, updated_at=updated_at WHERE id=?', [$itemId]);
            }
        } catch (Throwable $e) { app_log('Notify::checkLowStock ' . $e->getMessage()); }
    }
    /** Menyapu seluruh barang (data lama / perubahan lewat jalur lain). Dibatasi 1x per 5 menit kecuali $force (cron). */
    static function sweepLowStock(bool $force = false): int {
        try {
            $f = ROOT . '/storage/cache/low_stock_sweep.txt';
            if (!$force && is_file($f) && time() - (int)@filemtime($f) < 300) return 0;
            if (!is_dir(dirname($f))) @mkdir(dirname($f), 0775, true);
            @touch($f);
            $fire = [];
            foreach (DB::all('SELECT * FROM inventory_items WHERE stock<=min_stock AND low_alerted=0') as $i) {
                if (DB::exec('UPDATE inventory_items SET low_alerted=1, updated_at=updated_at WHERE id=? AND low_alerted=0', [$i['id']]) === 1) $fire[] = $i;
            }
            if ($fire) self::lowStockAlert($fire);
            DB::exec('UPDATE inventory_items SET low_alerted=0, updated_at=updated_at WHERE stock>min_stock AND low_alerted=1');
            if (random_int(1, 50) === 1) DB::exec('DELETE FROM notifications WHERE created_at < (NOW() - INTERVAL 90 DAY)');
            return count($fire);
        } catch (Throwable $e) { app_log('Notify::sweepLowStock ' . $e->getMessage()); return 0; }
    }
    private static function lowStockAlert(array $items): void {
        foreach ($items as $i) {
            $habis = (int)$i['stock'] <= 0;
            self::toPerm('stok_alert.view', 'low_stock', ($habis ? 'Stok habis: ' : 'Stok menipis: ') . $i['name'],
                'Sisa ' . num($i['stock']) . ' ' . $i['unit'] . ' (batas minimum ' . num($i['min_stock']) . '). Segera ajukan tambah stok.',
                '/admin/stok/barang/' . (int)$i['id']);
        }
    }
}
