<?php
class AdminController {
    function dashboard(): void { $this->show(); }
    function userDashboard(): void { $this->show(); }

    private function show(): void {
        $u = Auth::user();
        $showAdmin = can('dashboard.view');
        $showUser = can('dashboard_saya.view');
        $showSlips = can('slip.view');
        $showRequests = $showUser && (can('tambah_stok.view') || can('keluar_stok.view'));
        $data = compact('showAdmin', 'showUser', 'showSlips', 'showRequests');
        if ($showAdmin) $data += $this->adminData();
        $data['slips'] = $showSlips ? DB::all('SELECT * FROM payroll WHERE user_id = ? ORDER BY period_year DESC, period_month DESC LIMIT 12', [$u['id']]) : [];
        $data['myReq'] = $showRequests ? DB::all('SELECT r.*, i.name item_name FROM stock_requests r JOIN inventory_items i ON i.id=r.item_id WHERE r.requested_by=? ORDER BY r.id DESC LIMIT 5', [$u['id']]) : [];
        $data['title'] = $showAdmin ? ($showUser ? 'Dashboard' : 'Dashboard Admin') : 'Dashboard';
        render('dashboard/index', $data);
    }

    private function adminData(): array {
        $s = [
            'accounts'   => (int)DB::val('SELECT COUNT(*) FROM users'),
            'active'     => (int)DB::val("SELECT COUNT(*) FROM users WHERE status='active'"),
            'pending'    => (int)DB::val("SELECT COUNT(*) FROM users WHERE status='pending'"),
            'inactive'   => (int)DB::val("SELECT COUNT(*) FROM users WHERE status='inactive'"),
            'payroll'    => (float)DB::val('SELECT COALESCE(SUM(net),0) FROM payroll'),
            'unpaid'     => (int)DB::val("SELECT COUNT(*) FROM payroll WHERE status='unpaid'"),
            'items'      => (int)DB::val('SELECT COUNT(*) FROM inventory_items'),
            'stock'      => (int)DB::val('SELECT COALESCE(SUM(stock),0) FROM inventory_items'),
            'value'      => (float)DB::val('SELECT COALESCE(SUM(stock*price),0) FROM inventory_items'),
            'req_wait'   => (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='waiting'"),
            'req_ok'     => (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='approved'"),
            'req_no'     => (int)DB::val("SELECT COUNT(*) FROM stock_requests WHERE status='declined'"),
            'low'        => (int)DB::val('SELECT COUNT(*) FROM inventory_items WHERE stock <= min_stock'),
        ];
        $trend = array_reverse(DB::all('SELECT period_year y, period_month m, SUM(net) total FROM payroll GROUP BY period_year, period_month ORDER BY y DESC, m DESC LIMIT 6'));
        $att = DB::pairs("SELECT status, COUNT(*) FROM attendance WHERE work_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') GROUP BY status");
        $moves = DB::all("SELECT DATE(created_at) d, SUM(CASE WHEN type='in' THEN qty ELSE 0 END) masuk, SUM(CASE WHEN type='out' THEN qty ELSE 0 END) keluar FROM stock_movements WHERE created_at >= (CURDATE() - INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY d");
        $logs = DB::all('SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 8');
        $budget = can('anggaran.view') ? Budget::summary() : null;   // sumber yang sama dengan halaman anggaran & landing page
        return compact('s', 'trend', 'att', 'moves', 'logs', 'budget');
    }
}