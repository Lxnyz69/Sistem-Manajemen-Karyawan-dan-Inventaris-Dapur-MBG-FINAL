<?php
/** Endpoint ringan untuk fitur live: dipoll klien (live.js) tanpa mengunci sesi. */
class LiveController {
    /** Token data + notifikasi + penanda menu. Publik (halaman beranda juga live), notifikasi hanya untuk pengguna login. */
    function ping(): void {
        $u = Auth::user();
        $nk = (string)($_GET['nk'] ?? '');
        session_write_close();
        if (!$u) maintenance_guard();
        Notify::sweepLowStock();   // pengecekan stok menipis berkala (maks. 1x/5 menit), tidak bergantung pada siapa yang membuka website
        $out = ['t' => Live::token(), 'l' => Live::locKey(), 'now' => time()];
        if ($u) {
            $uid = (int)$u['id']; $key = Notify::key($uid);
            $out['n'] = ['unread' => Notify::unread($uid), 'key' => $key];
            if ($key !== $nk) $out['n']['items'] = array_map([Notify::class, 'format'], Notify::recent($uid));
            $out['c'] = Live::navCounts();
        }
        json_out($out);
    }

    /** Aliran Server-Sent Events: data baru dikirim seketika (cek tiap 1 detik di server, bukan polling dari browser). */
    function stream(): void {
        $u = Auth::user(); $uid = $u ? (int)$u['id'] : 0;
        $nk = (string)($_GET['nk'] ?? '');
        session_write_close();
        if (!$u) maintenance_guard();
        Notify::sweepLowStock();
        @set_time_limit(40);
        header('Content-Type: text/event-stream'); header('Cache-Control: no-store'); header('X-Accel-Buffering: no');
        while (ob_get_level()) ob_end_clean();
        echo "retry: 1000\n\n"; flush();
        $last = ''; $start = time(); $beat = time();
        while (time() - $start < 25 && !connection_aborted()) {
            $p = ['t' => Live::token(), 'l' => Live::locKey(), 'now' => time()];
            if ($u) {
                $key = Notify::key($uid);
                $p['n'] = ['unread' => Notify::unread($uid), 'key' => $key];
                $p['c'] = Live::navCounts();
            }
            $sig = md5(json_encode([$p['t'], $p['l'], $p['n'] ?? null, $p['c'] ?? null]));
            if ($sig !== $last) {
                if ($u && $key !== $nk) { $p['n']['items'] = array_map([Notify::class, 'format'], Notify::recent($uid)); $nk = $key; }
                $last = $sig; $beat = time();
                echo 'data: ' . json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
            } elseif (time() - $beat >= 10) { $beat = time(); echo ": ping\n\n"; }
            flush();
            sleep(1);
        }
        exit;
    }

    /** Posisi kurir yang sedang mengantar (publik). Tanpa nama kurir; hanya sekolah tujuan, menu, dan koordinat terakhir. */
    function lokasi(): void {
        session_write_close();
        if (!Auth::user()) maintenance_guard();
        $sid = (int)($_GET['school'] ?? 0);
        $rows = DB::all("SELECT d.id, d.school_id, d.portions, d.eta_arrival, d.courier_lat lat, d.courier_lng lng, d.courier_accuracy acc,
                UNIX_TIMESTAMP(d.courier_updated_at) ts, s.name school_name, s.latitude s_lat, s.longitude s_lng, m.name menu_name
            FROM deliveries d JOIN schools s ON s.id=d.school_id JOIN school_menus m ON m.id=d.menu_id
            WHERE d.status='in_transit'" . ($sid ? ' AND d.school_id=?' : '') . ' ORDER BY d.id LIMIT 50', $sid ? [$sid] : []);
        $items = array_map(fn($r) => [
            'id' => (int)$r['id'], 'school_id' => (int)$r['school_id'], 'school' => $r['school_name'], 'menu' => $r['menu_name'], 'portions' => (int)$r['portions'],
            'lat' => $r['lat'] !== null ? (float)$r['lat'] : null, 'lng' => $r['lng'] !== null ? (float)$r['lng'] : null, 'acc' => $r['acc'] !== null ? (int)$r['acc'] : null,
            'ts' => $r['ts'] !== null ? (int)$r['ts'] : null,
            's_lat' => $r['s_lat'] !== null ? (float)$r['s_lat'] : null, 's_lng' => $r['s_lng'] !== null ? (float)$r['s_lng'] : null,
            'eta' => $r['eta_arrival'] ? clock_label(strtotime($r['eta_arrival'])) : null,
        ], $rows);
        $schools = DB::all("SELECT id, name, latitude lat, longitude lng FROM schools WHERE status='active' AND latitude IS NOT NULL" . ($sid ? ' AND id=?' : ''), $sid ? [$sid] : []);
        $olat = setting('office_lat'); $office = $olat !== '' && $olat !== null ? ['lat' => (float)$olat, 'lng' => (float)setting('office_lng')] : null;
        json_out(['now' => time(), 'office' => $office, 'items' => $items, 'schools' => array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name'], 'lat' => (float)$r['lat'], 'lng' => (float)$r['lng']], $schools)]);
    }
}
