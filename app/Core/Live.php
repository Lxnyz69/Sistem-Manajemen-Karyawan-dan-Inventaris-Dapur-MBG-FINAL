<?php
/** Sidik jari data untuk fitur "live": klien cukup membandingkan token ini untuk tahu ada data baru. */
class Live {
    static function token(): string {
        $f = ROOT . '/storage/cache/live_token.txt';
        if (is_file($f) && time() - (int)@filemtime($f) < 1) { $t = (string)@file_get_contents($f); if ($t !== '') return $t; }
        $crc = fn(string $cols) => "COALESCE(SUM(CRC32(CONCAT_WS('|',$cols))),0)";
        // Posisi GPS kurir sengaja TIDAK ikut (dialirkan terpisah lewat /live/lokasi) supaya halaman tidak dimuat ulang tiap detik.
        $row = DB::one("SELECT
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,school_id,menu_id,portions,delivery_date,status,distributor,distributor_id,notes,eta_start,eta_ready,eta_departure,eta_arrival,started_at,ready_at,departed_at,arrived_at,received_at') . ") FROM deliveries) a,
            (SELECT CONCAT(COUNT(*),'/',COALESCE(MAX(id),0)) FROM delivery_status_history) b,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,school_id,name,description,delivery_date,portions,status') . ") FROM school_menus) c,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,name,address,whatsapp,daily_portions,status,latitude,longitude') . ") FROM schools) d,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,status,role_id,full_name,whatsapp,address,employee_code,username') . ") FROM users) e,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,code,name,description,unit,price,stock,min_stock,photo') . ") FROM inventory_items) f,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,status,quantity,decided_at,decision_note') . ") FROM stock_requests) g,
            (SELECT CONCAT(COUNT(*),'/',COALESCE(MAX(id),0)) FROM stock_movements) h,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,status,net,is_final,deduction') . ") FROM payroll) i,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,status') . ") FROM attendance) j,
            (SELECT CONCAT(COUNT(*),'/',COALESCE(MAX(id),0)) FROM audit_logs) l,
            (SELECT CONCAT(COUNT(*),'/',(SELECT COUNT(*) FROM role_permissions)) FROM roles) m,
            (SELECT CONCAT(COUNT(*),'/'," . $crc('id,type,amount,transaction_date,category') . ") FROM budget_transactions) n,
            (SELECT COUNT(*) FROM deliveries WHERE eta_arrival IS NOT NULL AND eta_arrival<NOW() AND status NOT IN ('arrived','received')) late,
            CURDATE() today") ?: [];
        $t = substr(md5(json_encode($row)), 0, 16);
        if (!is_dir(dirname($f))) @mkdir(dirname($f), 0775, true);
        @file_put_contents($f, $t, LOCK_EX);
        return $t;
    }
    /** Sidik jari posisi GPS kurir yang sedang mengantar (berubah tiap kurir bergerak). */
    static function locKey(): string {
        return substr(md5((string)DB::val("SELECT COALESCE(SUM(CRC32(CONCAT_WS('|',id,courier_lat,courier_lng,courier_updated_at))),0) FROM deliveries WHERE status='in_transit'")), 0, 12);
    }
    /** Penanda angka di menu samping (sama dengan logika layouts/app.php), dikunci per path URL. */
    static function navCounts(): array {
        $c = [];
        if (can('konfirmasi.view')) $c[url('/admin/karyawan/konfirmasi')] = (int)DB::val("SELECT COUNT(*) FROM users WHERE status='pending'");
        if (can('persetujuan_stok.view')) $c[url('/admin/stok/persetujuan')] = (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='waiting'");
        return $c;
    }
}
