<?php
declare(strict_types=1);

function env_load(string $file): void {
    if (!is_file($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim(trim($v), "\"'");
    }
}
function env(string $k, $d = null) { return $_ENV[$k] ?? $d; }
function cfg(string $k, $d = null) { return $GLOBALS['config'][$k] ?? $d; }
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function base_path(): string {
    $b = env('APP_BASE_PATH', '');
    if ($b === '' || $b === null) {
        $d = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $b = ($d === '/' || $d === '.') ? '' : $d;
    }
    return rtrim($b, '/');
}
function url(string $p = ''): string { return base_path() . '/' . ltrim($p, '/'); }
function asset(string $p): string { return url('assets/' . ltrim($p, '/')); }
function request_path(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $b = base_path();
    if ($b !== '' && str_starts_with($path, $b)) $path = substr($path, strlen($b));
    if (str_starts_with($path, '/public/')) $path = substr($path, 7);
    $path = '/' . trim($path, '/');
    return $path === '' ? '/' : $path;
}
function redirect(string $p): never {
    header('Location: ' . (preg_match('#^https?://#', $p) ? $p : url($p)));
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_ok(): bool {
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($t) && hash_equals(csrf_token(), $t);
}
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; }
function flash_get(): array { if (is_live_request()) return []; $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function with_old(array $d): void { $_SESSION['old'] = $d; }
function old(string $k, $d = '') {
    static $o = null;
    if ($o === null) { $o = $_SESSION['old'] ?? []; if (!is_live_request()) unset($_SESSION['old']); }
    return $o[$k] ?? $d;
}
function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function num($n): string { return number_format((float)$n, 0, ',', '.'); }
function dmy(?string $d): string { return $d ? date('d-m-Y', strtotime($d)) : '-'; }
function dmyhi(?string $d): string { return $d ? date('d-m-Y H:i', strtotime($d)) : '-'; }
function bulan_id(int $m): string {
    return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$m] ?? '';
}
function badge(string $status): string {
    $map = [
        'active' => ['Aktif', 'green'], 'pending' => ['Menunggu Konfirmasi', 'yellow'],
        'inactive' => ['Tidak Aktif', 'red'], 'rejected' => ['Ditolak', 'red'],
        'paid' => ['Sudah Dibayar', 'green'], 'unpaid' => ['Belum Dibayar', 'yellow'],
        'approved' => ['Disetujui', 'green'], 'waiting' => ['Menunggu Persetujuan', 'yellow'],
        'declined' => ['Ditolak', 'red'], 'draft' => ['Draft', 'gray'],
    ];
    [$t, $c] = $map[$status] ?? [$status, 'gray'];
    return '<span class="badge badge-' . $c . '">' . e($t) . '</span>';
}
function auth_user(): ?array { return Auth::user(); }
function can(string $perm): bool { return Auth::can($perm); }
function audit(string $action, string $detail = ''): void {
    try {
        DB::exec('INSERT INTO audit_logs (user_id, action, detail, ip) VALUES (?,?,?,?)',
            [Auth::user()['id'] ?? null, $action, mb_substr($detail, 0, 500), $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Throwable $e) { /* jangan menjatuhkan request karena log */ }
}
function setting(string $k, string $d = ''): string {
    static $all = null;
    if ($all === null) {
        try { $all = DB::pairs('SELECT skey, svalue FROM website_settings'); } catch (Throwable $e) { $all = []; }
    }
    return $all[$k] ?? $d;
}
function view(string $name, array $data = [], ?string $layout = 'layouts/app'): string {
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT . '/resources/views/' . $name . '.php';
    $content = ob_get_clean();
    if ($layout === null) return $content;
    ob_start();
    require ROOT . '/resources/views/' . $layout . '.php';
    return ob_get_clean();
}
function render(string $name, array $data = [], ?string $layout = 'layouts/app'): void {
    echo view($name, $data, $layout);
}
function pager(array $p, array $query = []): string {
    if ($p['pages'] <= 1) return '';
    $cur = (int)$p['page']; $last = (int)$p['pages'];
    $link = fn(int $i, string $txt, string $cls = '', string $aria = '') =>
        '<a class="' . $cls . '" href="?' . e(http_build_query(array_merge($query, ['page' => $i]))) . '"' . ($aria ? ' aria-label="' . $aria . '"' : '') . '>' . $txt . '</a>';
    $h = '<div class="pager"><div>Halaman ' . $cur . ' dari ' . $last . '</div><div class="pager-links">';
    if ($cur > 1) $h .= $link($cur - 1, '&lsaquo;', '', 'Sebelumnya');
    $shown = [];
    for ($i = 1; $i <= $last; $i++) if ($i === 1 || $i === $last || abs($i - $cur) <= 2) $shown[] = $i;
    $prev = 0;
    foreach ($shown as $i) {
        if ($prev && $i - $prev > 1) $h .= '<span class="gap">&hellip;</span>';
        $h .= $i === $cur ? '<span class="on" aria-current="page">' . $i . '</span>' : $link($i, (string)$i);
        $prev = $i;
    }
    if ($cur < $last) $h .= $link($cur + 1, '&rsaquo;', '', 'Berikutnya');
    return $h . '</div></div>';
}
function normalize_wa(string $wa): ?string {
    $wa = preg_replace('/[\s\-\.\(\)]/', '', $wa);
    if (str_starts_with($wa, '+62')) $wa = '0' . substr($wa, 3);
    elseif (str_starts_with($wa, '62')) $wa = '0' . substr($wa, 2);
    return preg_match('/^0[0-9]{8,14}$/', $wa) ? $wa : null;   // standar nomor telepon Indonesia: 9–15 digit (awalan 0 / 62 / +62)
}
function post(string $k, string $d = ''): string { return trim((string)($_POST[$k] ?? $d)); }
function upload_error_message(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas server (' . ini_get('upload_max_filesize') . '). Kecilkan ukuran foto lalu coba lagi.',
        UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian. Periksa koneksi lalu coba lagi.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Server tidak dapat menyimpan file sementara. Hubungi administrator hosting.',
        default => 'Upload gagal (kode ' . $code . ').',
    };
}
function save_image(array $file, string $subdir, int $maxBytes = 5242880): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, upload_error_message((int)$file['error'])];
    if ($file['size'] > $maxBytes) return [null, 'Ukuran gambar maksimal ' . rtrim(rtrim(number_format($maxBytes / 1048576, 1, ',', ''), '0'), ',') . ' MB.'];
    $info = @getimagesize($file['tmp_name']);
    $mime = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : ($info['mime'] ?? '');
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !$info) return [null, 'Format gambar harus JPG, PNG, atau WebP.'];
    $dir = ROOT . '/public/uploads/' . $subdir;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) return [null, 'Folder penyimpanan foto tidak bisa dibuat. Atur izin folder public/uploads (755/775) di hosting.'];
    if (!is_writable($dir)) return [null, 'Folder penyimpanan foto tidak bisa ditulis. Atur izin folder public/uploads (755/775) di hosting.'];
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return [null, 'Gagal menyimpan file.'];
    return ['uploads/' . $subdir . '/' . $name, null];
}
function delete_upload(?string $path): void {
    if (!$path || !str_starts_with($path, 'uploads/') || str_contains($path, '..')) return;
    $f = ROOT . '/public/' . $path;
    if (is_file($f)) @unlink($f);
}

function abort(int $code, string $msg): never {
    http_response_code($code);
    render('errors/error', ['code' => $code, 'msg' => $msg, 'title' => "Error $code"], Auth::user() ? 'layouts/app' : 'layouts/plain');
    exit;
}
function back(string $fallback = '/'): never {
    $r = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($r && parse_url($r, PHP_URL_HOST) === explode(':', $host)[0]) redirect($r);
    redirect($fallback);
}
function csv_download(string $filename, array $header, array $rows): never {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, $header);
    foreach ($rows as $r) {
        $r = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r);
        fputcsv($o, $r);
    }
    fclose($o); exit;
}
function bar_chart(array $data, string $color = 'var(--c-primary)', bool $money = false): string {
    if (!$data) return '<p class="empty-note">Belum ada data untuk ditampilkan.</p>';
    $max = max(1, max(array_map('floatval', $data)));
    $h = '<div class="bars">';
    foreach ($data as $l => $v) {
        $w = (float)$v > 0 ? max(2, round(((float)$v / $max) * 100)) : 0;
        $h .= '<div class="bar-row"><div class="bar-head"><span>' . e($l) . '</span><b>' . e($money ? rupiah($v) : num($v)) . '</b></div>'
            . '<div class="bar-track"><div class="bar-fill" style="width:' . $w . '%;background:' . e($color) . '"></div></div></div>';
    }
    return $h . '</div>';
}
function stat_card(string $icon, string $label, string $value, string $color = '#2563eb', bool $tone = false): string {
    return '<div class="metric"' . ($tone ? ' data-tone style="--tone:' . e($color) . '"' : '') . '><div class="metric-label">' . e($label) . '</div>'
        . '<div class="metric-value" title="' . e($value) . '">' . e($value) . '</div></div>';
}
function delivery_states(): array {
    return ['preparing' => 'Dalam Pembuatan', 'in_transit' => 'Dalam Pengantaran', 'arrived' => 'Sampai di Sekolah', 'received' => 'Makanan Diterima'];
}
function delivery_badge(string $status): string {
    $c = ['preparing' => 'yellow', 'in_transit' => 'blue', 'arrived' => 'green', 'received' => 'green'][$status] ?? 'gray';
    return '<span class="badge badge-' . $c . '">' . e(delivery_states()[$status] ?? $status) . '</span>';
}
function menu_status_label(string $status): string {
    return ['available' => 'Terjadwal', 'preparing' => 'Sedang disiapkan', 'completed' => 'Selesai'][$status] ?? $status;
}
function delivery_day_summary(array $statuses): string {
    if (!$statuses) return '<span class="badge badge-gray">Belum ada pengiriman</span>';
    $order = array_keys(delivery_states());
    $min = 3; $done = 0;
    foreach ($statuses as $st) {
        $i = array_search($st, $order, true);
        if ($i === false) continue;
        $min = min($min, $i);
        if ($i >= 2) $done++;
    }
    $n = count($statuses);
    return delivery_badge($order[$min]) . '<span class="sub">' . ($n > 1 ? $done . ' dari ' . $n . ' pengiriman sudah sampai' : ($done ? 'Pengiriman sudah sampai' : 'Pengiriman belum sampai')) . '</span>';
}
function hari_id(string $d): string {
    return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int)date('w', strtotime($d))];
}
function tgl_panjang(string $d): string {
    $t = strtotime($d);
    return hari_id($d) . ', ' . (int)date('j', $t) . ' ' . bulan_id((int)date('n', $t)) . ' ' . date('Y', $t);
}

function stock_type_badge(string $type): string {
    return $type === 'out' ? '<span class="badge badge-blue">Keluar</span>' : '<span class="badge badge-gray">Tambah</span>';
}
function parse_money(string $v): float {
    $v = preg_replace('/[^\d.,]/', '', $v) ?? '';
    if ($v === '') return 0.0;
    $lc = strrpos($v, ','); $ld = strrpos($v, '.');
    if ($lc !== false && $ld !== false) {            
        $dec = $lc > $ld ? ',' : '.';
        $th = $dec === ',' ? '.' : ',';
        $v = str_replace($th, '', $v);
        $v = str_replace($dec, '.', $v);
    } elseif ($lc !== false) {              
        $v = substr_count($v, ',') > 1 ? str_replace(',', '', $v) : str_replace(',', '.', $v);
    } elseif ($ld !== false) {          
        if (substr_count($v, '.') > 1 || preg_match('/^\d{1,3}\.\d{3}$/', $v)) $v = str_replace('.', '', $v); // 15.000 = ribuan
    }
    return round((float)$v, 2);
}
function registration_open(): bool { return setting('registration_open', '1') === '1'; }
/** Tombol Masuk di landing page (hanya untuk pengunjung). Halaman /login tetap bisa dibuka lewat URL. Bawaan: tampil. */
function login_button_visible(): bool { return setting('show_login_button', '1') === '1'; }
function maintenance_guard(): void {
    if (setting('maintenance') === '1' && !Auth::user()) {
        http_response_code(503);
        render('errors/error', ['code' => 503, 'msg' => 'Website sedang dalam pemeliharaan. Silakan kembali beberapa saat lagi.', 'title' => 'Pemeliharaan'], 'layouts/plain');
        exit;
    }
}
/** @return array<int,array{0:string,1:string,2:string,3:?string,4:string}> */
function nav_items(): array {
    $menu = [
        ['Dashboard', '/admin', 'fa-gauge-high', 'dashboard.view', '', null],
        ['Dashboard', '/dashboard', 'fa-house-user', 'dashboard_saya.view', '', 'dashboard.view'],
        ['Sekolah', '/admin/sekolah', 'fa-school', 'sekolah.view', 'Distribusi', null],
        ['Menu Sekolah', '/admin/menu', 'fa-utensils', 'menu.view', 'Distribusi', null],
        ['Pengantaran', '/admin/pengantaran', 'fa-truck', 'delivery.view', 'Distribusi', null],
        ['Tugas Kurir', '/kurir', 'fa-truck-fast', 'kurir.view', 'Distribusi', null],
        ['Karyawan', '/admin/karyawan', 'fa-users', 'karyawan.view', 'Karyawan', null],
        ['Konfirmasi Akun', '/admin/karyawan/konfirmasi', 'fa-user-check', 'konfirmasi.view', 'Karyawan', null],
        ['Gaji Karyawan', '/admin/gaji', 'fa-money-bill-wave', 'gaji.view', 'Penggajian', null],
        ['Kelola Gaji', '/admin/gaji/kelola', 'fa-calendar-check', 'kelola_gaji.view', 'Penggajian', null],
        ['Anggaran', '/admin/anggaran', 'fa-wallet', 'anggaran.view', 'Keuangan', null],
        ['Stok Barang', '/admin/stok', 'fa-boxes-stacked', 'stok.view', 'Inventaris', null],
        ['Tambah Stok', '/admin/stok/tambah', 'fa-cart-plus', 'tambah_stok.view', 'Inventaris', null],
        ['Barang Keluar', '/admin/stok/keluar', 'fa-box-open', 'keluar_stok.view', 'Inventaris', null],
        ['Persetujuan Stok', '/admin/stok/persetujuan', 'fa-clipboard-check', 'persetujuan_stok.view', 'Inventaris', null],
        ['Role', '/admin/role', 'fa-user-shield', 'role.view', 'Sistem', null],
        ['Setting Website', '/admin/pengaturan', 'fa-gear', 'pengaturan.view', 'Sistem', null],
        ['Profile', '/profile', 'fa-id-card', 'profile.view', 'Akun', null],
    ];
    $out = [];
    foreach ($menu as [$label, $path, $icon, $perm, $group, $hideIf]) {
        if (!can($perm) || ($hideIf !== null && can($hideIf))) continue;
        $out[] = [$label, $path, $icon, $perm, $group];
    }
    return $out;
}
function home_path(): string {
    $items = nav_items();
    return $items ? $items[0][1] : '/';
}
function valid_date(string $d): bool {
    return (bool)preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}
function valid_datetime_local(string $v): bool {
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}):(\d{2})$/', $v, $m)) return false;
    return valid_date($m[1]) && (int)$m[2] <= 23 && (int)$m[3] <= 59;
}

// ---------------------------------------------------------------- Live / notifikasi / kurir
function app_log(string $msg): void { @file_put_contents(ROOT . '/storage/logs/app.log', '[' . date('c') . '] ' . $msg . "\n", FILE_APPEND); }
/** Permintaan refresh otomatis dari live.js: jangan menghabiskan flash/old milik halaman berikutnya. */
function is_live_request(): bool { return !empty($_SERVER['HTTP_X_LIVE']); }
function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
/** URL absolut (untuk cron). Pakai APP_URL di .env bila ada. */
function abs_url(string $p = ''): string {
    $b = rtrim((string)env('APP_URL', ''), '/');
    if ($b === '' && !empty($_SERVER['HTTP_HOST'])) $b = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . base_path();
    return $b === '' ? '' : $b . '/' . ltrim($p, '/');
}
/** "14:32", "Kemarin 09:10", atau "6 Nov 09:10" (+ tahun bila bukan tahun ini). */
function clock_label(int $ts): string {
    $hm = date('H:i', $ts); $d = date('Y-m-d', $ts);
    if ($d === date('Y-m-d')) return $hm;
    if ($d === date('Y-m-d', strtotime('-1 day'))) return 'Kemarin ' . $hm;
    $m = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][(int)date('n', $ts)];
    return (int)date('j', $ts) . ' ' . $m . (date('Y', $ts) !== date('Y') ? ' ' . date('Y', $ts) : '') . ' ' . $hm;
}
/** Halaman admin yang boleh diperbarui otomatis (live). Halaman formulir panjang sengaja tidak ikut. */
function live_page(string $path): bool {
    foreach (['#^/admin$#', '#^/dashboard$#', '#^/notifikasi$#', '#^/kurir$#', '#^/admin/karyawan$#', '#^/admin/karyawan/konfirmasi$#', '#^/admin/karyawan/\d+$#',
        '#^/admin/gaji$#', '#^/admin/anggaran$#', '#^/admin/stok$#', '#^/admin/stok/persetujuan$#', '#^/admin/stok/barang/\d+$#', '#^/admin/stok/tambah$#', '#^/admin/stok/keluar$#',
        '#^/admin/sekolah$#', '#^/admin/sekolah/\d+$#', '#^/admin/menu$#', '#^/admin/pengantaran$#', '#^/admin/role$#'] as $re)
        if (preg_match($re, $path)) return true;
    return false;
}
/** Tautan Google Maps (rute) ke sekolah: pakai koordinat bila ada, jika tidak pakai alamat. */
function maps_link(?string $lat, ?string $lng, string $address): string {
    $dest = ($lat !== null && $lng !== null && $lat !== '') ? $lat . ',' . $lng : $address;
    return 'https://www.google.com/maps/dir/?api=1&travelmode=driving&destination=' . rawurlencode($dest);
}
function rel_time(int $ts): string {
    $s = max(0, time() - $ts);
    if ($s < 45) return 'baru saja';
    if ($s < 3600) return max(1, (int)round($s / 60)) . ' mnt lalu';
    if ($s < 86400) return intdiv($s, 3600) . ' jam lalu';
    return intdiv($s, 86400) . ' hari lalu';
}
function wa_link(string $wa): string { return 'https://wa.me/62' . ltrim(substr($wa, 0, 1) === '0' ? substr($wa, 1) : $wa, '0'); }
/** "lat, lng" (salinan Google Maps) -> [lat, lng] atau null bila tidak valid. */
function parse_coords(string $v): ?array {
    if (!preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $v, $m)) return null;
    $lat = (float)$m[1]; $lng = (float)$m[2];
    return ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) ? null : [round($lat, 7), round($lng, 7)];
}
