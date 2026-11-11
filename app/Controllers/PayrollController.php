<?php
class PayrollController {
    const STATUSES = ['hadir' => 'Hadir', 'izin' => 'Izin', 'alpa' => 'Tanpa Keterangan', 'libur' => 'Libur'];

    function index(): void {
        $w = []; $p = [];
        $q = trim($_GET['q'] ?? ''); $per = $_GET['period'] ?? ''; $st = $_GET['status'] ?? ''; $uid = (int)($_GET['user_id'] ?? 0);
        if ($q !== '') { $w[] = '(u.full_name LIKE ? OR u.employee_code LIKE ? OR p.invoice_no LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
        if (preg_match('/^(\d{4})-(\d{2})$/', $per, $m)) { $w[] = 'p.period_year=? AND p.period_month=?'; array_push($p, (int)$m[1], (int)$m[2]); }
        if (in_array($st, ['paid', 'unpaid'], true)) { $w[] = 'p.status=?'; $p[] = $st; }
        if ($uid) { $w[] = 'p.user_id=?'; $p[] = $uid; }
        $where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
        $pg = DB::paginate('p.*, u.full_name, u.employee_code', 'payroll p JOIN users u ON u.id=p.user_id', $where, $p, 'p.period_year DESC, p.period_month DESC, p.id DESC', (int)($_GET['page'] ?? 1));
        $emps = DB::all("SELECT id, full_name, employee_code FROM users WHERE employee_code IS NOT NULL ORDER BY full_name");
        render('payroll/index', ['pg' => $pg, 'emps' => $emps, 'title' => 'Gaji Karyawan']);
    }
    function manage(): void {
        $emps = DB::all("SELECT u.id, u.full_name, u.employee_code FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE u.status='active' AND u.employee_code IS NOT NULL ORDER BY u.full_name");
        $uid = (int)($_GET['user_id'] ?? 0);
        $year = (int)($_GET['year'] ?? date('Y')); $month = (int)($_GET['month'] ?? date('n'));
        if ($month < 1 || $month > 12) $month = (int)date('n');
        if ($year < 2000 || $year > 2100) $year = (int)date('Y');
        $emp = $uid ? DB::one('SELECT * FROM users WHERE id=?', [$uid]) : null;
        $att = []; $payroll = null; $rate = 0;
        if ($emp) {
            $att = DB::pairs('SELECT work_date, status FROM attendance WHERE user_id=? AND YEAR(work_date)=? AND MONTH(work_date)=?', [$uid, $year, $month]);
            $payroll = DB::one('SELECT * FROM payroll WHERE user_id=? AND period_year=? AND period_month=?', [$uid, $year, $month]);
            $rate = (int)($payroll['daily_rate'] ?? DB::val('SELECT daily_rate FROM payroll WHERE user_id=? ORDER BY id DESC LIMIT 1', [$uid]) ?? 0);
        }
        render('payroll/manage', compact('emps', 'uid', 'year', 'month', 'emp', 'att', 'payroll', 'rate') + ['title' => 'Kelola Gaji']);
    }

    private function readInput(): array {
        $uid = (int)post('user_id'); $year = (int)post('year'); $month = (int)post('month');
        $emp = DB::one("SELECT * FROM users WHERE id=? AND status='active'", [$uid]);
        if (!$emp || $month < 1 || $month > 12 || $year < 2000 || $year > 2100) { flash('error', 'Data karyawan/periode tidak valid.'); redirect('/admin/gaji/kelola'); }
        return [$emp, $year, $month];
    }
    private function persistAttendance(int $uid, int $year, int $month): void {
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $in = $_POST['att'] ?? [];
        $me = Auth::user()['id'];
        for ($d = 1; $d <= $days; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $s = is_array($in) ? ($in[$date] ?? '') : '';
            if (isset(self::STATUSES[$s])) {
                DB::exec('INSERT INTO attendance (user_id, work_date, status, recorded_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), recorded_by=VALUES(recorded_by)', [$uid, $date, $s, $me]);
            } else {
                DB::exec('DELETE FROM attendance WHERE user_id=? AND work_date=?', [$uid, $date]);
            }
        }
    }
    private function lockedMessage(int $uid, int $year, int $month): ?string {
        $p = DB::one('SELECT is_final FROM payroll WHERE user_id=? AND period_year=? AND period_month=?', [$uid, $year, $month]);
        return ($p && (int)$p['is_final'] === 1) ? 'Gaji periode ini sudah final. Gunakan "Buka Koreksi" di daftar gaji (tercatat di audit log).' : null;
    }

    function saveAttendance(): void {
        [$emp, $year, $month] = $this->readInput();
        $back = "/admin/gaji/kelola?user_id={$emp['id']}&year=$year&month=$month";
        if ($m = $this->lockedMessage((int)$emp['id'], $year, $month)) { flash('error', $m); redirect($back); }
        DB::tx(fn() => $this->persistAttendance((int)$emp['id'], $year, $month));
        audit('kehadiran_simpan', "{$emp['employee_code']} " . sprintf('%02d-%d', $month, $year));
        flash('success', 'Kehadiran disimpan.');
        redirect($back);
    }

    function generate(): void {
        [$emp, $year, $month] = $this->readInput();
        $uid = (int)$emp['id'];
        $back = "/admin/gaji/kelola?user_id=$uid&year=$year&month=$month";
        $rate = (int)round(parse_money(post('rate')));
        $ded = (int)round(parse_money(post('deduction')));
        $note = mb_substr(post('deduction_note'), 0, 255);
        if ($rate <= 0) { flash('error', 'Tarif gaji per hari wajib diisi.'); redirect($back); }

        $res = DB::tx(function () use ($uid, $year, $month, $rate, $ded, $note, $emp) {
            $ex = DB::one('SELECT * FROM payroll WHERE user_id=? AND period_year=? AND period_month=? FOR UPDATE', [$uid, $year, $month]);
            if ($ex && (int)$ex['is_final'] === 1) return 'Gaji karyawan ini untuk periode tersebut sudah dibuat (tidak boleh ganda).';
            $this->persistAttendance($uid, $year, $month);
            $c = DB::pairs('SELECT status, COUNT(*) FROM attendance WHERE user_id=? AND YEAR(work_date)=? AND MONTH(work_date)=? GROUP BY status', [$uid, $year, $month]);
            $present = (int)($c['hadir'] ?? 0); $leave = (int)($c['izin'] ?? 0); $abs = (int)($c['alpa'] ?? 0);
            if ($present + $leave + $abs === 0) return 'Isi kehadiran dulu sebelum membuat gaji.';
            $gross = $present * $rate;
            if ($ded > $gross) return 'Potongan tidak boleh melebihi gaji kotor.';
            $net = $gross - $ded;
            if ($ex) { // koreksi resmi
                DB::exec('UPDATE payroll SET daily_rate=?, days_present=?, days_leave=?, days_absent=?, gross=?, deduction=?, deduction_note=?, net=?, is_final=1 WHERE id=?',
                    [$rate, $present, $leave, $abs, $gross, $ded, $note, $net, $ex['id']]);
                audit('gaji_koreksi_final', "{$ex['invoice_no']}: net {$ex['net']} -> $net");
            } else {
                DB::exec('INSERT INTO payroll (user_id, period_year, period_month, daily_rate, days_present, days_leave, days_absent, gross, deduction, deduction_note, net, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$uid, $year, $month, $rate, $present, $leave, $abs, $gross, $ded, $note, $net, Auth::user()['id']]);
                $id = DB::id();
                DB::exec('UPDATE payroll SET invoice_no=? WHERE id=?', [sprintf('INV-%04d%02d-%04d', $year, $month, $id), $id]);
                audit('gaji_buat', "{$emp['employee_code']} " . sprintf('%02d-%d', $month, $year) . " net $net");
            }
            return true;
        });
        if ($res !== true) { flash('error', $res); redirect($back); }
        flash('success', 'Gaji dihitung dan difinalisasi. Slip gaji siap dilihat di daftar gaji.');
        redirect('/admin/gaji?user_id=' . $uid);
    }

    function markPaid(string $id): void {
        // Satu transaksi database: status gaji + pengeluaran anggaran. Gagal salah satu -> ROLLBACK semuanya.
        try {
            $result = DB::tx(function () use ($id) {
                Budget::lock();
                $p = DB::one('SELECT * FROM payroll WHERE id=? FOR UPDATE', [(int)$id]);
                if (!$p || (int)$p['is_final'] !== 1 || $p['status'] !== 'unpaid') return 'Tidak dapat diubah (belum final atau sudah dibayar).';
                $net = Budget::cents($p['net']);   // nominal dari database, bukan dari input pengguna
                if ($net > 0) Budget::spend(Budget::CAT_PAYROLL, 'Pembayaran gaji ' . $p['invoice_no'], $net / 100, 'payroll', (int)$p['id'], 'anggaran_keluar_gaji');
                DB::exec("UPDATE payroll SET status='paid' WHERE id=? AND status='unpaid'", [$p['id']]);
                audit('gaji_dibayar', "payroll #{$p['id']}; anggaran -" . ($net / 100));
                return true;
            });
        } catch (BudgetException $e) {
            $result = $e->getMessage();
        } catch (Throwable $e) {
            app_log('markPaid gagal: ' . $e->getMessage());
            $result = 'Pembayaran gagal diproses. Tidak ada perubahan yang tersimpan.';
        }
        if ($result === true) flash('success', 'Gaji ditandai sudah dibayar dan anggaran dikurangi.');
        else flash('error', $result);
        back('/admin/gaji');
    }
    function reopen(string $id): void {
        $note = post('note');
        if (mb_strlen($note) < 5) { flash('error', 'Alasan koreksi wajib diisi (min. 5 karakter).'); back('/admin/gaji'); }
        $p = DB::one('SELECT * FROM payroll WHERE id=?', [(int)$id]);
        if (!$p || $p['status'] === 'paid') { flash('error', 'Gaji yang sudah dibayar tidak dapat dikoreksi.'); back('/admin/gaji'); }
        DB::exec('UPDATE payroll SET is_final=0 WHERE id=?', [$p['id']]);
        audit('gaji_buka_koreksi', "{$p['invoice_no']}: $note");
        flash('success', 'Periode dibuka untuk koreksi. Ubah kehadiran lalu finalisasi ulang.');
        redirect("/admin/gaji/kelola?user_id={$p['user_id']}&year={$p['period_year']}&month={$p['period_month']}");
    }

    /* ---------- Invoice ---------- */
    private function load(string $no, bool $own): array {
        $p = DB::one('SELECT p.*, u.full_name, u.employee_code, u.address, u.whatsapp, r.name role_name FROM payroll p JOIN users u ON u.id=p.user_id LEFT JOIN roles r ON r.id=u.role_id WHERE p.invoice_no=?', [$no]);
        if (!$p || (int)$p['is_final'] !== 1) abort(404, 'Slip gaji tidak ditemukan.');
        if ($own && (int)$p['user_id'] !== (int)Auth::user()['id']) abort(403, 'Anda tidak berhak melihat slip ini.');
        return $p;
    }
    private function logoData(): ?string {
        $l = setting('logo');
        if (!$l || !is_file(ROOT . '/public/' . $l)) return null;
        $mime = mime_content_type(ROOT . '/public/' . $l);
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(ROOT . '/public/' . $l));
    }
    private function show(string $no, bool $own): void {
        $p = $this->load($no, $own);
        $pdfUrl = $own ? "/slip/$no/pdf" : "/admin/gaji/invoice/$no/pdf";
        $canPdf = $own || can('gaji.export');
        render('payroll/invoice', ['p' => $p, 'logo' => $this->logoData(), 'pdfUrl' => $pdfUrl, 'canPdf' => $canPdf, 'title' => 'Slip ' . $no], 'layouts/plain');
    }
    private function pdf(string $no, bool $own): void {
        $p = $this->load($no, $own);
        if (!class_exists('Dompdf\\Dompdf')) {
            flash('error', 'Dompdf belum terpasang. Jalankan "composer install" di folder project (lihat README).');
            redirect($own ? "/slip/$no" : "/admin/gaji/invoice/$no");
        }
        $html = view('payroll/doc', ['p' => $p, 'logo' => $this->logoData(), 'pdf' => true], null);
        $d = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
        $d->loadHtml($html, 'UTF-8');
        $d->setPaper('A4', 'portrait');
        $d->render();
        audit('invoice_pdf', $no);
        $d->stream($no . '.pdf', ['Attachment' => true]);
        exit;
    }
    function invoice(string $no): void { $this->show($no, false); }
    function invoicePdf(string $no): void { $this->pdf($no, false); }
    function slipOwn(string $no): void { $this->show($no, true); }
    function slipOwnPdf(string $no): void { $this->pdf($no, true); }
}