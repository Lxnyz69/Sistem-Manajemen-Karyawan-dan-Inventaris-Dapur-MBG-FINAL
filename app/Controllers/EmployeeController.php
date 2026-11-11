<?php
class EmployeeController {
    private function filters(): array {
        $w = []; $p = [];
        $q = trim($_GET['q'] ?? ''); $role = (int)($_GET['role'] ?? 0);
        $st = $_GET['status'] ?? ''; $from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? '';
        if ($q !== '') { $w[] = '(u.full_name LIKE ? OR u.employee_code LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
        if ($role) { $w[] = 'u.role_id = ?'; $p[] = $role; }
        if (in_array($st, ['active', 'pending', 'inactive', 'rejected'], true)) { $w[] = 'u.status = ?'; $p[] = $st; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $w[] = 'u.joined_at >= ?'; $p[] = $from; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $w[] = 'u.joined_at <= ?'; $p[] = $to; }
        return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
    }
    private function target(int $id): array {
        $u = DB::one('SELECT u.*, r.name role_name, r.is_super FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE u.id=?', [$id]);
        if (!$u) abort(404, 'Data karyawan tidak ditemukan.');
        if ((int)$u['is_super'] === 1 && !Auth::isSuper()) abort(403, 'Akun Admin Utama hanya dapat dikelola oleh Admin Utama.');
        return $u;
    }
    private function roles(): array {
        return Auth::isSuper() ? DB::all('SELECT * FROM roles ORDER BY name') : DB::all('SELECT * FROM roles WHERE is_super=0 ORDER BY name');
    }
    private function nextCode(): string {
        $prefix = setting('employee_code_prefix', 'KRY');
        $max = (int)DB::val('SELECT COALESCE(MAX(CAST(SUBSTRING(employee_code, ?) AS UNSIGNED)),0) FROM users WHERE employee_code LIKE ?', [strlen($prefix) + 2, $prefix . '-%']);
        return sprintf('%s-%04d', $prefix, $max + 1);
    }

    function index(): void {
        [$where, $params] = $this->filters();
        $pg = DB::paginate('u.*, r.name role_name', 'users u LEFT JOIN roles r ON r.id=u.role_id', $where, $params, 'u.id DESC', (int)($_GET['page'] ?? 1));
        render('employees/index', ['pg' => $pg, 'roles' => DB::all('SELECT id,name FROM roles ORDER BY name'), 'title' => 'Kelola Karyawan']);
    }
    function export(): void {
        [$where, $params] = $this->filters();
        $rows = DB::all("SELECT u.employee_code, u.full_name, u.whatsapp, u.address, r.name role_name, u.status, u.joined_at FROM users u LEFT JOIN roles r ON r.id=u.role_id $where ORDER BY u.id", $params);
        audit('export_karyawan', count($rows) . ' baris');
        csv_download('karyawan-' . date('Ymd') . '.csv', ['ID', 'Nama', 'WhatsApp', 'Alamat', 'Role', 'Status', 'Bergabung'], array_map('array_values', $rows));
    }
    function createForm(): void {
        render('employees/form', ['u' => null, 'roles' => $this->roles(), 'title' => 'Tambah Karyawan']);
    }
    private function validate(?int $id): array {
        $name = post('full_name'); $wa = normalize_wa(post('whatsapp')); $addr = post('address');
        $err = [];
        if (mb_strlen($name) < 3) $err[] = 'Nama minimal 3 karakter.';
        if (!$wa) $err[] = 'Nomor WhatsApp tidak valid. Gunakan 9–15 digit, contoh: 081234567890.';
        if (mb_strlen($addr) < 5) $err[] = 'Alamat wajib diisi.';
        if ($wa && DB::val('SELECT id FROM users WHERE whatsapp=? AND id<>?', [$wa, $id ?? 0])) $err[] = 'Nomor WhatsApp sudah dipakai akun lain.';
        $role = (int)post('role_id');
        $r = DB::one('SELECT * FROM roles WHERE id=?', [$role]);
        if (!$r) $err[] = 'Role wajib dipilih.';
        elseif ($r['is_super'] && !Auth::isSuper()) $err[] = 'Anda tidak dapat memberikan role Admin Utama.';
        $joined = post('joined_at');
        if (!valid_date($joined)) $joined = date('Y-m-d');
        return [$err, compact('name', 'wa', 'addr', 'role', 'joined')];
    }
    function store(): void {
        [$err, $d] = $this->validate(null);
        $pw = (string)($_POST['password'] ?? '');
        if (strlen($pw) < 8) $err[] = 'Password awal minimal 8 karakter.';
        if ($err) { with_old($_POST); flash('error', implode(' ', $err)); redirect('/admin/karyawan/tambah'); }
        $code = DB::tx(function () use ($d, $pw) {
            $code = $this->nextCode();
            DB::exec("INSERT INTO users (employee_code, username, full_name, whatsapp, address, password_hash, role_id, status, joined_at, must_change_password, approved_by, approved_at) VALUES (?,?,?,?,?,?,?,'active',?,1,?,NOW())",
                [$code, $code, $d['name'], $d['wa'], $d['addr'], password_hash($pw, PASSWORD_DEFAULT), $d['role'], $d['joined'], Auth::user()['id']]);
            return $code;
        });
        audit('karyawan_tambah', "$code {$d['name']}");
        flash('success', "Karyawan ditambahkan dengan ID $code. Mereka wajib ganti password saat login pertama.");
        redirect('/admin/karyawan');
    }
    function show(string $id): void {
        $u = $this->target((int)$id);
        $payrolls = DB::all('SELECT * FROM payroll WHERE user_id=? ORDER BY period_year DESC, period_month DESC LIMIT 12', [$u['id']]);
        render('employees/show', ['u' => $u, 'payrolls' => $payrolls, 'title' => 'Detail Karyawan']);
    }
    function editForm(string $id): void {
        $u = $this->target((int)$id);
        render('employees/form', ['u' => $u, 'roles' => $this->roles(), 'title' => 'Edit Karyawan']);
    }
    function update(string $id): void {
        $u = $this->target((int)$id);
        [$err, $d] = $this->validate((int)$id);
        if ((int)$id === (int)Auth::user()['id']) $d['role'] = (int)$u['role_id']; // tidak boleh mengubah role sendiri
        if ($err) { with_old($_POST); flash('error', implode(' ', $err)); redirect("/admin/karyawan/$id/edit"); }
        DB::exec('UPDATE users SET full_name=?, whatsapp=?, address=?, role_id=?, joined_at=? WHERE id=?',
            [$d['name'], $d['wa'], $d['addr'], $d['role'], $d['joined'], $u['id']]);
        audit('karyawan_edit', "{$u['employee_code']} {$d['name']}");
        flash('success', 'Data karyawan diperbarui.');
        redirect('/admin/karyawan');
    }
    function toggle(string $id): void {
        $u = $this->target((int)$id);
        if ((int)$u['is_super'] === 1) { flash('error', 'Akun Admin Utama tidak dapat dinonaktifkan.'); back('/admin/karyawan'); }
        if ((int)$id === (int)Auth::user()['id']) { flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.'); back('/admin/karyawan'); }
        if (!in_array($u['status'], ['active', 'inactive'], true)) { flash('error', 'Hanya akun aktif/tidak aktif yang bisa diubah.'); back('/admin/karyawan'); }
        $new = $u['status'] === 'active' ? 'inactive' : 'active';
        DB::exec('UPDATE users SET status=? WHERE id=?', [$new, $u['id']]);
        audit('karyawan_status', "{$u['employee_code']} -> $new");
        flash('success', 'Status akun diubah menjadi ' . ($new === 'active' ? 'Aktif' : 'Tidak Aktif') . '.');
        back('/admin/karyawan');
    }
    function delete(string $id): void {
        $u = $this->target((int)$id);
        if ((int)$u['is_super'] === 1 || (int)$id === (int)Auth::user()['id']) { flash('error', 'Akun ini tidak dapat dihapus.'); back('/admin/karyawan'); }
        $used = DB::val('SELECT (SELECT COUNT(*) FROM payroll WHERE user_id=?) + (SELECT COUNT(*) FROM attendance WHERE user_id=?) + (SELECT COUNT(*) FROM stock_requests WHERE requested_by=?)', [$u['id'], $u['id'], $u['id']]);
        if ((int)$used > 0) { flash('error', 'Karyawan punya riwayat gaji/kehadiran/pengajuan. Nonaktifkan saja agar riwayat tetap utuh.'); back('/admin/karyawan'); }
        DB::exec('DELETE FROM users WHERE id=?', [$u['id']]);
        audit('karyawan_hapus', "{$u['employee_code']} {$u['full_name']}");
        flash('success', 'Karyawan dihapus.');
        back('/admin/karyawan');
    }
    function pending(): void {
        $pg = DB::paginate('*', 'users', "WHERE status='pending'", [], 'id ASC', (int)($_GET['page'] ?? 1));
        render('employees/pending', ['pg' => $pg, 'roles' => $this->roles(), 'title' => 'Konfirmasi Akun']);
    }
    function approve(string $id): void {
        $role = (int)post('role_id');
        $r = DB::one('SELECT * FROM roles WHERE id=?', [$role]);
        if (!$r || ($r['is_super'] && !Auth::isSuper())) { flash('error', 'Pilih role yang valid.'); back('/admin/karyawan/konfirmasi'); }
        $ok = DB::tx(function () use ($id, $role) {
            $u = DB::one("SELECT * FROM users WHERE id=? AND status='pending' FOR UPDATE", [(int)$id]);
            if (!$u) return false;
            $code = $this->nextCode();
            DB::exec("UPDATE users SET employee_code=?, username=?, role_id=?, status='active', joined_at=CURDATE(), approved_by=?, approved_at=NOW(), reject_reason=NULL WHERE id=?",
                [$code, $code, $role, Auth::user()['id'], $u['id']]);
            audit('konfirmasi_setuju', "{$u['full_name']} -> $code");
            return $code;
        });
        $ok ? flash('success', "Akun disetujui. ID karyawan: $ok") : flash('error', 'Pendaftaran tidak ditemukan atau sudah diproses.');
        back('/admin/karyawan/konfirmasi');
    }
    function reject(string $id): void {
        $reason = post('reason');
        if (mb_strlen($reason) < 3) { flash('error', 'Alasan penolakan wajib diisi.'); back('/admin/karyawan/konfirmasi'); }
        $n = DB::exec("UPDATE users SET status='rejected', reject_reason=?, approved_by=?, approved_at=NOW() WHERE id=? AND status='pending'",
            [mb_substr($reason, 0, 255), Auth::user()['id'], (int)$id]);
        if ($n) { audit('konfirmasi_tolak', "user #$id: $reason"); flash('success', 'Pendaftaran ditolak.'); }
        else flash('error', 'Pendaftaran tidak ditemukan atau sudah diproses.');
        back('/admin/karyawan/konfirmasi');
    }
}