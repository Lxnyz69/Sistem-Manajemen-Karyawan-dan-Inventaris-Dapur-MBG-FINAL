<?php
class Auth {
    private static $user = false;
    private static ?array $perms = null;

    static function user(): ?array {
        if (self::$user !== false) return self::$user;
        self::$user = null;
        if (!empty($_SESSION['uid'])) {
            $u = DB::one('SELECT u.*, r.name AS role_name, r.is_super FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [$_SESSION['uid']]);
            if ($u && $u['status'] === 'active') self::$user = $u; else self::logout();
        }
        return self::$user;
    }
    static function can(string $perm): bool {
        $u = self::user();
        if (!$u) return false;
        if ((int)$u['is_super'] === 1) return true;
        if (self::$perms === null) {
            self::$perms = array_column(DB::all(
                'SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?',
                [$u['role_id']]), 'code');
        }
        return in_array($perm, self::$perms, true);
    }
    static function isSuper(): bool { $u = self::user(); return $u && (int)$u['is_super'] === 1; }
    static function attempt(string $login, string $pass): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $win = (int)cfg('login_window_minutes', 15);
        $fails = (int)DB::val('SELECT COUNT(*) FROM login_attempts WHERE (ip = ? OR username = ?) AND attempted_at > (NOW() - INTERVAL ' . $win . ' MINUTE)', [$ip, $login]);
        if ($fails >= (int)cfg('login_max_attempts', 5)) return [false, "Terlalu banyak percobaan login. Coba lagi dalam $win menit."];

        $u = DB::one('SELECT * FROM users WHERE username = ? OR employee_code = ? LIMIT 1', [$login, $login]);
        $hash = $u['password_hash'] ?? password_hash('dummy-password', PASSWORD_DEFAULT);
        $ok = password_verify($pass, $hash) && $u;
        if (!$ok) {
            DB::exec('INSERT INTO login_attempts (ip, username) VALUES (?,?)', [$ip, mb_substr($login, 0, 100)]);
            return [false, 'ID/username atau password salah.'];
        }
        if ($u['status'] === 'pending') return [false, 'Akun Anda masih menunggu konfirmasi administrator.'];
        if ($u['status'] !== 'active') return [false, 'Akun tidak dapat digunakan. Hubungi administrator.'];

        DB::exec('DELETE FROM login_attempts WHERE ip = ? OR username = ?', [$ip, $login]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        self::$user = false; self::$perms = null;
        audit('login', 'Login berhasil');
        return [true, ''];
    }
    static function logout(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
        self::$user = null; self::$perms = null;
    }
}