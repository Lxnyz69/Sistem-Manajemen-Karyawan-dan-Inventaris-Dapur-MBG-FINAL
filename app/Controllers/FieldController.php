<?php
/** Halaman petugas lapangan / kurir (mobile-first): tugas pengantaran, ubah status, dan bagikan lokasi GPS. */
class FieldController {
    private function wantsJson(): bool { return str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json'); }

    function index(): void {
        $u = Auth::user();
        $rows = DB::all("SELECT d.*, s.name school_name, s.address school_address, s.whatsapp school_wa, s.latitude s_lat, s.longitude s_lng, m.name menu_name
            FROM deliveries d JOIN schools s ON s.id=d.school_id JOIN school_menus m ON m.id=d.menu_id
            WHERE d.distributor_id=? AND (d.status<>'received' OR d.delivery_date=CURDATE())
            ORDER BY FIELD(d.status,'preparing','in_transit','arrived','received'), d.delivery_date, d.id LIMIT 40", [$u['id']]);
        render('field/index', ['rows' => $rows, 'title' => 'Tugas Pengantaran'], 'layouts/field');
    }

    /** Majukan status satu tahap. Hanya untuk pengiriman milik kurir yang login. */
    function status(string $id): void {
        $u = Auth::user();
        $d = DB::one('SELECT * FROM deliveries WHERE id=? AND distributor_id=?', [(int)$id, $u['id']]);
        $next = post('status');
        $err = $d ? Delivery::advance($d, $next, mb_substr(post('notes'), 0, 500) ?: (string)($d['notes'] ?? ''), (int)$u['id']) : 'Pengiriman tidak ditemukan atau bukan tugas Anda.';
        if (!$err) audit('kurir_status', "#{$d['id']} → $next");
        if ($this->wantsJson()) json_out(['ok' => !$err, 'msg' => $err ?: 'Status diperbarui.', 'status' => $next], $err ? 422 : 200);
        $err ? flash('error', $err) : flash('success', 'Status pengiriman diperbarui.');
        redirect('/kurir');
    }

    /** Terima titik GPS dari browser kurir; diterapkan ke semua pengiriman "Dalam Pengantaran" miliknya. */
    function location(): void {
        $uid = (int)Auth::user()['id'];
        session_write_close();
        $lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($_POST['lng'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) json_out(['ok' => false, 'msg' => 'Koordinat tidak valid.'], 422);
        $acc = max(0, min(100000, (int)round((float)($_POST['acc'] ?? 0))));
        // updated_at=updated_at: pembaruan posisi tidak boleh memicu "data berubah" (refresh halaman) bagi semua pengunjung.
        $n = DB::exec("UPDATE deliveries SET courier_lat=?, courier_lng=?, courier_accuracy=?, courier_updated_at=NOW(), updated_at=updated_at WHERE distributor_id=? AND status='in_transit'",
            [round($lat, 7), round($lng, 7), $acc, $uid]);
        json_out(['ok' => true, 'n' => $n, 'active' => (int)DB::val("SELECT COUNT(*) FROM deliveries WHERE distributor_id=? AND status='in_transit'", [$uid])]);
    }
}
