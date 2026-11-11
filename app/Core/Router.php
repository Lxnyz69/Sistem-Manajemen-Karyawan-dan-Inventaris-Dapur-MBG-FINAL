<?php
class Router {
    private array $routes = [];
    function add(string $m, string $path, array $h, ?string $perm = null, bool $public = false): void {
        $re = '#^' . preg_replace('#\{(\w+)\}#', '([^/]+)', $path) . '$#';
        $this->routes[] = compact('m', 're', 'h', 'perm', 'public');
    }
    function get(string $p, array $h, ?string $perm = null, bool $public = false): void { $this->add('GET', $p, $h, $perm, $public); }
    function post(string $p, array $h, ?string $perm = null, bool $public = false): void { $this->add('POST', $p, $h, $perm, $public); }

    function dispatch(string $method, string $path): void {
        $pathMatched = false;
        foreach ($this->routes as $r) {
            if (!preg_match($r['re'], $path, $mt)) continue;
            $pathMatched = true;
            if ($r['m'] !== $method) continue;
            array_shift($mt);
            $mt = array_map('urldecode', $mt);

            if (!$r['public']) {
                $u = Auth::user();
                if (!$u) { flash('error', 'Silakan login terlebih dahulu.'); redirect('/login'); }
                if ($u['must_change_password'] && $path !== '/profile/password' && $path !== '/logout') {
                    flash('warning', 'Demi keamanan, ganti password bawaan Anda dahulu.');
                    redirect('/profile/password');
                }
                $forcedPw = $u['must_change_password'] && $path === '/profile/password';
                if ($r['perm'] && !$forcedPw && !Auth::can($r['perm'])) $this->fail(403, 'Anda tidak memiliki izin untuk halaman ini.');
            }
            if ($method === 'POST' && !$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                flash('error', 'Data yang dikirim terlalu besar (batas server ' . ini_get('post_max_size') . '). Kecilkan ukuran foto lalu coba lagi.');
                back($path);
            }
            if ($method === 'POST' && !csrf_ok()) $this->fail(419, 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');

            [$cls, $fn] = $r['h'];
            (new $cls())->$fn(...$mt);
            return;
        }
        $pathMatched ? $this->fail(405, 'Metode tidak diizinkan.') : $this->fail(404, 'Halaman tidak ditemukan.');
    }
    private function fail(int $code, string $msg): never {
        http_response_code($code);
        $layout = Auth::user() ? 'layouts/app' : 'layouts/plain';
        render('errors/error', ['code' => $code, 'msg' => $msg, 'title' => "Error $code"], $layout);
        exit;
    }
}