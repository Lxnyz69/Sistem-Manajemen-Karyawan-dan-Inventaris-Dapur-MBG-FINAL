<?php
class AuthController {
    function loginForm(): void {
        if (Auth::user()) redirect(home_path());
        render('auth/login', ['title' => 'Login'], 'layouts/plain');
    }
    function login(): void {
        [$ok, $msg] = Auth::attempt(post('login'), (string)($_POST['password'] ?? ''));
        if (!$ok) { with_old(['login' => post('login')]); flash('error', $msg); redirect('/login'); }
        $u = Auth::user();
        if ($u && $u['must_change_password']) redirect('/profile/password');   // wajib ganti password dahulu
        if ($u && !nav_items()) {
            Auth::logout();
            flash('error', 'Akun Anda belum memiliki hak akses ke halaman mana pun. Hubungi administrator.');
            redirect('/login');
        }
        redirect(home_path());
    }
    /** Halaman pendaftaran hanya bisa dibuka bila diaktifkan di Setting Website dan tidak sedang pemeliharaan. */
    private function registrationGuard(): void {
        maintenance_guard();
        if (!registration_open()) {
            http_response_code(403);
            render('errors/error', ['code' => 403, 'msg' => 'Pendaftaran akun sedang ditutup oleh administrator.', 'title' => 'Pendaftaran Ditutup'], 'layouts/plain');
            exit;
        }
    }
    function registerForm(): void {
        $this->registrationGuard();
        render('auth/register', ['title' => 'Daftar Akun'], 'layouts/plain');
    }
    function register(): void {
        $this->registrationGuard();
        $name = post('full_name'); $wa = normalize_wa(post('whatsapp')); $addr = post('address');
        $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['password2'] ?? '');
        $err = [];
        if (mb_strlen($name) < 3) $err[] = 'Nama lengkap minimal 3 karakter.';
        if (!$wa) $err[] = 'Nomor WhatsApp tidak valid. Gunakan 9–15 digit, contoh: 081234567890.';
        if (mb_strlen($addr) < 5) $err[] = 'Alamat wajib diisi.';
        if (strlen($pw) < 8) $err[] = 'Password minimal 8 karakter.';
        if ($pw !== $pw2) $err[] = 'Konfirmasi password tidak sama.';
        $exist = !$err ? DB::one('SELECT id, status FROM users WHERE whatsapp = ? OR username = ?', [$wa, $wa]) : null;
        if ($exist && $exist['status'] === 'pending') $err[] = 'Nomor WhatsApp ini sudah mendaftar dan masih menunggu konfirmasi administrator.';
        elseif ($exist && $exist['status'] !== 'rejected') $err[] = 'Nomor WhatsApp sudah terdaftar.';
        if ($err) { with_old(['full_name' => $name, 'whatsapp' => post('whatsapp'), 'address' => $addr]); flash('error', implode(' ', $err)); redirect('/register'); }

        if ($exist) {   // pendaftaran sebelumnya ditolak: ajukan ulang dengan data terbaru
            DB::exec("UPDATE users SET full_name=?, address=?, password_hash=?, status='pending', reject_reason=NULL, approved_by=NULL, approved_at=NULL WHERE id=?",
                [$name, $addr, password_hash($pw, PASSWORD_DEFAULT), $exist['id']]);
        } else {
            DB::exec("INSERT INTO users (username, full_name, whatsapp, address, password_hash, status) VALUES (?,?,?,?,?, 'pending')",
                [$wa, $name, $wa, $addr, password_hash($pw, PASSWORD_DEFAULT)]);
        }
        audit('register', "Pendaftaran baru: $name");
        flash('success', 'Pendaftaran berhasil. Tunggu konfirmasi dari administrator.');
        redirect('/login');
    }
    function logout(): void {
        audit('logout');
        Auth::logout();
        redirect('/login');
    }
}