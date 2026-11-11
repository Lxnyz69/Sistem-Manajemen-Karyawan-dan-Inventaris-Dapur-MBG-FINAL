<?php
/** Logika bersama perubahan status pengantaran (dipakai halaman admin dan halaman kurir). */
class Delivery {
    /** Nama role (huruf kecil, tanpa spasi tepi) yang sah menjadi petugas pengantaran. Data role asli tidak diubah. */
    const COURIER_ROLES = ['kurir', 'pengantaran'];

    /** Daftar karyawan AKTIF berrole Kurir/Pengantaran (untuk dropdown). */
    static function couriers(): array {
        $in = implode(',', array_fill(0, count(self::COURIER_ROLES), '?'));
        return DB::all("SELECT u.id, u.employee_code, u.full_name FROM users u JOIN roles r ON r.id = u.role_id
            WHERE u.status = 'active' AND LOWER(TRIM(r.name)) IN ($in) ORDER BY u.full_name", self::COURIER_ROLES);
    }

    /** Verifikasi di backend: ID harus karyawan aktif berrole Kurir/Pengantaran. Mengembalikan baris user atau null. */
    static function findCourier(int $id): ?array {
        if ($id <= 0) return null;
        $in = implode(',', array_fill(0, count(self::COURIER_ROLES), '?'));
        return DB::one("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id = u.role_id
            WHERE u.id = ? AND u.status = 'active' AND LOWER(TRIM(r.name)) IN ($in)", array_merge([$id], self::COURIER_ROLES));
    }

    const ORDER = ['preparing', 'in_transit', 'arrived', 'received'];
    const FIELD = ['preparing' => 'started_at', 'in_transit' => 'departed_at', 'arrived' => 'arrived_at', 'received' => 'received_at'];

    /** Status hanya boleh maju satu tahap. Mengembalikan pesan galat, atau null bila berhasil. */
    static function advance(array $d, string $next, string $note, ?int $actorId): ?string {
        $old = array_search($d['status'], self::ORDER, true);
        $new = array_search($next, self::ORDER, true);
        if ($new === false || $new !== $old + 1) return 'Status hanya bisa dimajukan satu tahap berikutnya (Dalam Pembuatan → Dalam Pengantaran → Sampai di Sekolah → Makanan Diterima).';
        DB::tx(function () use ($d, $next, $note, $actorId) {
            $field = self::FIELD[$next];
            $set = ['status=?', 'notes=?', "$field=COALESCE($field,NOW())"];
            if ($next === 'in_transit') $set[] = 'ready_at=COALESCE(ready_at,NOW())';
            // Setelah sampai, posisi GPS tidak lagi dibagikan.
            if ($next === 'arrived' || $next === 'received') array_push($set, 'courier_lat=NULL', 'courier_lng=NULL', 'courier_accuracy=NULL', 'courier_updated_at=NULL');
            DB::exec('UPDATE deliveries SET ' . implode(',', $set) . ' WHERE id=?', [$next, $note, $d['id']]);
            DB::exec('INSERT INTO delivery_status_history(delivery_id,status,note,changed_by) VALUES(?,?,?,?)', [$d['id'], $next, $note, $actorId]);
        });
        return null;
    }
}
