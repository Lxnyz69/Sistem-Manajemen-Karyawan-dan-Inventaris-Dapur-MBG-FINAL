<?php
class BudgetController {
    private function superOnly(): void {
        // Lapisan kedua di backend: izin route saja tidak cukup untuk operasi yang mengubah uang.
        if (!Auth::isSuper()) abort(403, 'Hanya Admin Utama yang dapat mengatur anggaran.');
    }

    /** Membangun WHERE dari filter (semua nilai lewat prepared statement). */
    private function filters(): array {
        $w = []; $p = [];
        $q = trim($_GET['q'] ?? ''); $tp = $_GET['type'] ?? ''; $cat = trim($_GET['category'] ?? '');
        $from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? ''; $month = $_GET['month'] ?? ''; $week = $_GET['week'] ?? ''; $year = $_GET['year'] ?? '';
        if ($q !== '') { $w[] = '(b.description LIKE ? OR b.period_label LIKE ? OR pr.invoice_no LIKE ? OR sr.request_no LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
        if (in_array($tp, ['in', 'out'], true)) { $w[] = 'b.type=?'; $p[] = $tp; }
        if ($cat !== '') { $w[] = 'b.category=?'; $p[] = $cat; }
        if (valid_date($from)) { $w[] = 'b.transaction_date >= ?'; $p[] = $from; }
        if (valid_date($to)) { $w[] = 'b.transaction_date <= ?'; $p[] = $to; }
        if (preg_match('/^(\d{4})-(\d{2})$/', $month, $m) && (int)$m[2] >= 1 && (int)$m[2] <= 12) { [$a, $b] = Budget::monthRange("$month-01"); $w[] = 'b.transaction_date BETWEEN ? AND ?'; array_push($p, $a, $b); }
        if (preg_match('/^(\d{4})-W(\d{2})$/', $week, $m)) {   // <input type="week">: minggu ISO = Senin–Minggu
            $mon = (new DateTime())->setISODate((int)$m[1], (int)$m[2])->format('Y-m-d');
            [$a, $b] = Budget::weekRange($mon); $w[] = 'b.transaction_date BETWEEN ? AND ?'; array_push($p, $a, $b);
        }
        if (preg_match('/^\d{4}$/', $year)) { $w[] = 'b.transaction_date BETWEEN ? AND ?'; array_push($p, "$year-01-01", "$year-12-31"); }
        return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
    }

    function index(): void {
        [$where, $p] = $this->filters();
        $pg = DB::paginate(
            'b.*, u.full_name creator, pr.invoice_no payroll_invoice, sr.request_no, it.name item_name, rv.id reversed_by',
            'budget_transactions b
             LEFT JOIN users u ON u.id=b.created_by
             LEFT JOIN payroll pr ON b.source_type=\'payroll\' AND pr.id=b.source_id
             LEFT JOIN stock_requests sr ON b.source_type=\'stock_request\' AND sr.id=b.source_id
             LEFT JOIN inventory_items it ON it.id=sr.item_id
             LEFT JOIN budget_transactions rv ON rv.source_type=\'reversal\' AND rv.source_id=b.id',
            $where, $p, 'b.transaction_date DESC, b.id DESC', (int)($_GET['page'] ?? 1));
        $cats = array_column(DB::all('SELECT DISTINCT category FROM budget_transactions ORDER BY category'), 'category');
        $years = array_column(DB::all('SELECT DISTINCT YEAR(transaction_date) y FROM budget_transactions ORDER BY y DESC'), 'y');
        render('budget/index', [
            'b' => Budget::summary(), 'weeks' => array_reverse(Budget::weeks(8)), 'months' => array_reverse(Budget::months(6)),
            'pg' => $pg, 'cats' => $cats, 'years' => $years,
            'canManage' => Auth::isSuper(), 'token' => bin2hex(random_bytes(16)),
            'title' => 'Anggaran',
        ]);
    }

    function store(): void {
        $this->superOnly();
        $raw = post('amount');
        // Format ketat: hanya angka dengan pemisah ribuan/desimal (opsional awalan "Rp"). Tanda minus/huruf ditolak,
        // karena parse_money() membuang karakter non-angka sehingga "-5" akan terbaca 5.
        $amount = preg_match('/^(?:Rp\.?\s*)?\d[\d.,\s]*$/i', $raw) ? parse_money($raw) : 0.0;
        $date = post('date'); $label = post('period'); $desc = post('description'); $token = post('token');
        $err = [];
        if ($amount <= 0) $err[] = 'Nominal harus berupa angka lebih dari 0.';
        elseif (Budget::cents($amount) > Budget::MAX_AMOUNT_CENTS) $err[] = 'Nominal terlalu besar.';
        if (!valid_date($date)) $err[] = 'Tanggal tidak valid.';
        if (mb_strlen($desc) > 150 || mb_strlen($label) > 60) $err[] = 'Keterangan/periode terlalu panjang.';
        if ($err) { with_old($_POST); flash('error', implode(' ', $err)); redirect('/admin/anggaran'); }
        try {
            $r = Budget::addFunds($amount, $date, $label, $desc, $token);
            if ($r === 'created') flash('success', 'Anggaran ' . rupiah($amount) . ' berhasil ditambahkan. Saldo kini ' . rupiah(Budget::balance()) . '.');
            else flash('warning', 'Formulir ini sudah pernah dikirim — tidak ada transaksi ganda yang dibuat.');
        } catch (BudgetException $e) {
            with_old($_POST); flash('error', $e->getMessage());
        } catch (Throwable $e) {
            app_log('anggaran tambah gagal: ' . $e->getMessage());
            with_old($_POST); flash('error', 'Anggaran gagal disimpan. Tidak ada perubahan yang tersimpan.');
        }
        redirect('/admin/anggaran');   // POST/Redirect/GET: refresh tidak mengirim ulang formulir
    }

    function reverse(string $id): void {
        $this->superOnly();
        try {
            Budget::reverse((int)$id, post('note'));
            flash('success', 'Koreksi dicatat sebagai transaksi baru. Transaksi asal tetap tersimpan.');
        } catch (BudgetException $e) {
            flash('error', $e->getMessage());
        } catch (Throwable $e) {
            app_log('anggaran koreksi gagal: ' . $e->getMessage());
            flash('error', 'Koreksi gagal diproses. Tidak ada perubahan yang tersimpan.');
        }
        redirect('/admin/anggaran');
    }

    function export(): void {
        [$where, $p] = $this->filters();
        $rows = DB::all('SELECT b.*, u.full_name creator, pr.invoice_no payroll_invoice, sr.request_no, it.name item_name
            FROM budget_transactions b LEFT JOIN users u ON u.id=b.created_by
            LEFT JOIN payroll pr ON b.source_type=\'payroll\' AND pr.id=b.source_id
            LEFT JOIN stock_requests sr ON b.source_type=\'stock_request\' AND sr.id=b.source_id
            LEFT JOIN inventory_items it ON it.id=sr.item_id ' . $where . ' ORDER BY b.transaction_date DESC, b.id DESC', $p);
        csv_download('anggaran-' . date('Ymd') . '.csv', ['Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Nominal', 'Sumber', 'Dibuat oleh', 'Waktu transaksi'],
            array_map(fn($r) => [$r['transaction_date'], $r['type'] === 'in' ? 'MASUK' : 'KELUAR', $r['category'], $r['description'], $r['amount'], Budget::sourceLabel($r), $r['creator'] ?? 'Sistem', $r['created_at']], $rows));
    }
}
