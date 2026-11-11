<?php
class ProfileController {
    function show(): void {
        render('profile/show', ['u' => Auth::user(), 'title' => 'Profile']);
    }
    function updateIdentity(): void {
        $u = Auth::user();
        $name = post('full_name'); $wa = normalize_wa(post('whatsapp')); $addr = post('address'); $un = post('username', $u['username']);
        $err = [];
        if (mb_strlen($name) < 3) $err[] = 'Nama minimal 3 karakter.';
        if (!$wa) $err[] = 'Nomor WhatsApp tidak valid. Gunakan 9–15 digit, contoh: 081234567890.';
        if (mb_strlen($addr) < 5) $err[] = 'Alamat wajib diisi.';
        if (!preg_match('/^[a-zA-Z0-9_.-]{4,30}$/', $un)) $err[] = 'Username 4-30 karakter.';
        if ($wa && DB::val('SELECT id FROM users WHERE whatsapp=? AND id<>?', [$wa, $u['id']])) $err[] = 'Nomor WhatsApp sudah dipakai.';
        if (DB::val('SELECT id FROM users WHERE (username=? OR employee_code=?) AND id<>?', [$un, $un, $u['id']])) $err[] = 'Username sudah dipakai.';
        if ($err) { flash('error', implode(' ', $err)); redirect('/profile'); }
        DB::exec('UPDATE users SET full_name=?, whatsapp=?, address=?, username=? WHERE id=?', [$name, $wa, $addr, $un, $u['id']]);
        audit('profil_ubah', 'Identitas diperbarui');
        flash('success', 'Profil diperbarui.');
        redirect('/profile');
    }
    function passwordForm(): void { render('profile/password', ['title' => 'Ganti Password']); }
    function password(): void {
        $u = Auth::user();
        $old = (string)($_POST['old'] ?? ''); $new = (string)($_POST['new'] ?? ''); $new2 = (string)($_POST['new2'] ?? '');
        $err = [];
        if (!password_verify($old, $u['password_hash'])) $err[] = 'Password lama salah.';
        if (strlen($new) < 8) $err[] = 'Password baru minimal 8 karakter.';
        if ($new !== $new2) $err[] = 'Konfirmasi password baru tidak sama.';
        if ($new === $old) $err[] = 'Password baru harus berbeda dari yang lama.';
        if ($err) { flash('error', implode(' ', $err)); redirect('/profile/password'); }
        DB::exec('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        session_regenerate_id(true);
        audit('password_ganti', 'Password diganti');
        if (!nav_items()) {   // role belum diberi hak akses halaman apa pun
            Auth::logout();
            flash('success', 'Password berhasil diganti. Akun Anda belum memiliki hak akses halaman; hubungi administrator.');
            redirect('/login');
        }
        flash('success', 'Password berhasil diganti.');
        redirect(home_path());
    }
}