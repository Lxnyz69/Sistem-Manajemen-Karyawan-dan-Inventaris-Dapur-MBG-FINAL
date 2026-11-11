<?php
class HomeController {
    private function maintenance(): void { maintenance_guard(); }

    function index(): void {
        $this->maintenance();
        $s = [
            'emp_total'   => (int)DB::val("SELECT COUNT(*) FROM users WHERE status IN ('active','inactive')"),
            'emp_active'  => (int)DB::val("SELECT COUNT(*) FROM users WHERE status='active'"),
            'emp_pending' => (int)DB::val("SELECT COUNT(*) FROM users WHERE status='pending'"),
            'items'       => (int)DB::val('SELECT COUNT(*) FROM inventory_items'),
            'stock'       => (int)DB::val('SELECT COALESCE(SUM(stock),0) FROM inventory_items'),
            'value'       => (float)DB::val('SELECT COALESCE(SUM(stock*price),0) FROM inventory_items'),
            'requests'    => (int)DB::val('SELECT COUNT(*) FROM stock_requests'),
            'requests_waiting' => (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='waiting'"),
            'requests_approved' => (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='approved'"),
            'requests_declined' => (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='declined'"),
            'low_stock'   => (int)DB::val('SELECT COUNT(*) FROM inventory_items WHERE stock <= min_stock'),
            'payroll_paid'=> (float)DB::val("SELECT COALESCE(SUM(net),0) FROM payroll WHERE status='paid'"),
            'payroll_unpaid'=> (float)DB::val("SELECT COALESCE(SUM(net),0) FROM payroll WHERE status='unpaid'"),
        ];
        $trend = DB::all("SELECT period_year y, period_month m, SUM(net) total FROM payroll GROUP BY period_year, period_month ORDER BY y DESC, m DESC LIMIT 6");
        $reqStat = DB::pairs('SELECT status, COUNT(*) FROM stock_requests GROUP BY status');
        $dist = [
            'schools'       => (int)DB::val('SELECT COUNT(*) FROM schools'),
            'schools_active'=> (int)DB::val("SELECT COUNT(*) FROM schools WHERE status='active'"),
            'daily_portions'=> (int)DB::val("SELECT COALESCE(SUM(daily_portions),0) FROM schools WHERE status='active'"),
            'menus'         => (int)DB::val('SELECT COUNT(*) FROM school_menus'),
            'menus_today'   => (int)DB::val('SELECT COUNT(*) FROM school_menus WHERE delivery_date=CURDATE()'),
            'planned_today' => (int)DB::val('SELECT COALESCE(SUM(portions),0) FROM school_menus WHERE delivery_date=CURDATE()'),
        ];
        $dist['schools_inactive'] = $dist['schools'] - $dist['schools_active'];
        $dist['all_deliveries'] = (int)DB::val('SELECT COUNT(*) FROM deliveries');
        $dist['all_received']   = (int)DB::val("SELECT COUNT(*) FROM deliveries WHERE status IN ('arrived','received')");
        $dist['couriers']       = count(Delivery::couriers());   // karyawan aktif berrole Kurir/Pengantaran
        $today = DB::one("SELECT COUNT(*) total,
            COALESCE(SUM(status='preparing'),0) preparing, COALESCE(SUM(status='in_transit'),0) transit,
            COALESCE(SUM(status='arrived'),0) arrived, COALESCE(SUM(status='received'),0) received,
            COALESCE(SUM(status IN ('arrived','received')),0) completed,
            COALESCE(SUM(eta_arrival IS NOT NULL AND eta_arrival<NOW() AND status NOT IN ('arrived','received')),0) late,
            COALESCE(SUM(CASE WHEN status IN ('in_transit','arrived','received') THEN portions ELSE 0 END),0) sent
            FROM deliveries WHERE delivery_date=CURDATE()") ?? [];
        $today = array_map('intval', $today + ['total' => 0, 'preparing' => 0, 'transit' => 0, 'arrived' => 0, 'received' => 0, 'completed' => 0, 'late' => 0, 'sent' => 0]);
        $today['percent'] = $today['total'] ? (int)round($today['completed'] * 100 / $today['total']) : 0;

        $schools = DB::all("SELECT id,name,address,daily_portions FROM schools WHERE status='active' ORDER BY name");
        $menusToday = []; foreach (DB::all('SELECT school_id,name FROM school_menus WHERE delivery_date=CURDATE() ORDER BY id') as $r) $menusToday[$r['school_id']][] = $r['name'];
        $statToday = []; foreach (DB::all('SELECT school_id,status FROM deliveries WHERE delivery_date=CURDATE()') as $r) $statToday[$r['school_id']][] = $r['status'];

        $budget = Budget::summary();            // selalu dibaca langsung dari ledger (tanpa cache/angka tetap)
        $budgetWeeks = Budget::weeks(6);
        render('home/index', compact('s', 'trend', 'reqStat', 'dist', 'today', 'schools', 'menusToday', 'statToday', 'budget', 'budgetWeeks') + ['title' => setting('site_title', 'Beranda')], 'layouts/plain');
    }
    function school(string $id): void {
        $this->maintenance();
        $sc = DB::one("SELECT id,name,address,daily_portions FROM schools WHERE id=? AND status='active'", [(int)$id]);
        if (!$sc) abort(404, 'Sekolah tidak ditemukan.');

        $date = (string)($_GET['date'] ?? '');
        $valid = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
        if (!$valid) $date = date('Y-m-d');
        $isToday = $date === date('Y-m-d');

        $menus = DB::all('SELECT * FROM school_menus WHERE school_id=? AND delivery_date=? ORDER BY id', [$sc['id'], $date]);
        $deliveries = DB::all('SELECT d.*, m.name menu_name FROM deliveries d JOIN school_menus m ON m.id=d.menu_id WHERE d.school_id=? AND d.delivery_date=? ORDER BY d.id', [$sc['id'], $date]);
        foreach ($deliveries as &$d) {
            $d['history'] = DB::all('SELECT status,changed_at,note FROM delivery_status_history WHERE delivery_id=? ORDER BY changed_at,id', [$d['id']]);
            $d['late'] = $d['eta_arrival'] && strtotime($d['eta_arrival']) < time() && !in_array($d['status'], ['arrived', 'received'], true);
        }
        unset($d);

        $day = ['deliveries' => count($deliveries), 'portions' => 0, 'sent' => 0, 'completed' => 0, 'late' => 0, 'planned' => 0];
        foreach ($menus as $mn) $day['planned'] += (int)$mn['portions'];
        foreach ($deliveries as $d) {
            $day['portions'] += (int)$d['portions'];
            if (in_array($d['status'], ['in_transit', 'arrived', 'received'], true)) $day['sent'] += (int)$d['portions'];
            if (in_array($d['status'], ['arrived', 'received'], true)) $day['completed']++;
            if ($d['late']) $day['late']++;
        }
        $day['percent'] = $day['deliveries'] ? (int)round($day['completed'] * 100 / $day['deliveries']) : 0;

        // Hari-hari yang tercatat (14 terakhir) beserta ringkasan statusnya.
        $dates = array_column(DB::all('SELECT dt FROM (SELECT delivery_date dt FROM school_menus WHERE school_id=? UNION SELECT delivery_date FROM deliveries WHERE school_id=?) x ORDER BY dt DESC LIMIT 14', [$sc['id'], $sc['id']]), 'dt');
        $days = [];
        if ($dates) {
            $in = implode(',', array_fill(0, count($dates), '?'));
            $mc = DB::pairs("SELECT delivery_date, COUNT(*) FROM school_menus WHERE school_id=? AND delivery_date IN ($in) GROUP BY delivery_date", [$sc['id'], ...$dates]);
            $ds = []; foreach (DB::all("SELECT delivery_date dt, status FROM deliveries WHERE school_id=? AND delivery_date IN ($in)", [$sc['id'], ...$dates]) as $r) $ds[$r['dt']][] = $r['status'];
            foreach ($dates as $dt) $days[] = ['date' => $dt, 'menus' => (int)($mc[$dt] ?? 0), 'statuses' => $ds[$dt] ?? []];
        }

        render('home/school', [
            'sc' => $sc, 'date' => $date, 'isToday' => $isToday, 'menus' => $menus, 'deliveries' => $deliveries, 'day' => $day, 'days' => $days,
            'prev' => date('Y-m-d', strtotime($date . ' -1 day')), 'next' => date('Y-m-d', strtotime($date . ' +1 day')),
            'title' => $sc['name'],
        ], 'layouts/plain');
    }
}
