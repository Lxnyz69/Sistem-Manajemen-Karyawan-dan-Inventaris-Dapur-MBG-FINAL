<?php
class RoleController {
    const MODULES = [
        'dashboard' => 'Dashboard', 'karyawan' => 'Karyawan', 'konfirmasi' => 'Konfirmasi Akun', 'gaji' => 'Gaji Karyawan',
        'kelola_gaji' => 'Kelola Gaji', 'stok' => 'Stok Barang', 'tambah_stok' => 'Tambah Stok', 'keluar_stok' => 'Barang Keluar', 'persetujuan_stok' => 'Persetujuan Stok',
        'sekolah' => 'Data Sekolah', 'menu' => 'Menu Sekolah', 'delivery' => 'Status Pengantaran',
        'role' => 'Role', 'pengaturan' => 'Setting Website', 'profile' => 'Profile',
        'anggaran' => 'Anggaran (lihat saja)', 'stok_alert' => 'Peringatan Stok Menipis', 'kurir' => 'Tugas Kurir',
        'dashboard_saya' => 'Dashboard Karyawan', 'slip' => 'Slip Gaji Saya', 'password' => 'Ganti Password',
    ];
    const DEFAULT_NEW = ['dashboard_saya.view', 'slip.view', 'slip.export', 'profile.view', 'password.edit'];
    const ACTIONS = ['view' => 'Lihat', 'create' => 'Tambah', 'edit' => 'Edit', 'delete' => 'Hapus', 'approve' => 'Setujui', 'export' => 'Cetak/Unduh'];

    private function role(int $id): array {
        $r = DB::one('SELECT * FROM roles WHERE id=?', [$id]);
        if (!$r) abort(404, 'Role tidak ditemukan.');
        return $r;
    }
    function index(): void {
        $roles = DB::all('SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id=r.id) AS users_count, (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id=r.id) AS perm_count FROM roles r ORDER BY r.id');
        render('roles/index', ['roles' => $roles, 'title' => 'Role & Hak Akses']);
    }
    private function formData(?array $r): array {
        $perms = DB::all('SELECT * FROM permissions ORDER BY id');
        $sel = $r ? array_column(DB::all('SELECT permission_id FROM role_permissions WHERE role_id=?', [$r['id']]), 'permission_id') : [];
        if (!$r) {   // role baru: centang hak akses dasar
            foreach ($perms as $p) if (in_array($p['code'], self::DEFAULT_NEW, true)) $sel[] = $p['id'];
        }
        return ['r' => $r, 'perms' => $perms, 'sel' => array_map('intval', $sel)];
    }
    function form(): void { render('roles/form', $this->formData(null) + ['title' => 'Tambah Role']); }
    function editForm(string $id): void {
        $r = $this->role((int)$id);
        if ($r['is_super']) { flash('warning', 'Role Admin Utama memiliki akses penuh dan tidak dapat diubah.'); redirect('/admin/role'); }
        render('roles/form', $this->formData($r) + ['title' => 'Edit Role']);
    }
    private function savePerms(int $roleId): void {
        $valid = array_column(DB::all('SELECT id FROM permissions'), 'id');
        $chosen = array_values(array_intersect(array_map('intval', (array)($_POST['perm'] ?? [])), array_map('intval', $valid)));
        DB::exec('DELETE FROM role_permissions WHERE role_id=?', [$roleId]);
        foreach ($chosen as $pid) DB::exec('INSERT INTO role_permissions (role_id, permission_id) VALUES (?,?)', [$roleId, $pid]);
    }
    function store(): void {
        $name = post('name');
        if (mb_strlen($name) < 3 || DB::val('SELECT id FROM roles WHERE name=?', [$name])) { with_old($_POST); flash('error', 'Nama role minimal 3 karakter dan harus unik.'); redirect('/admin/role/baru'); }
        $id = DB::tx(function () use ($name) {
            DB::exec('INSERT INTO roles (name, description, is_super) VALUES (?,?,0)', [$name, mb_substr(post('description'), 0, 255)]);
            $id = DB::id(); $this->savePerms($id); return $id;
        });
        audit('role_tambah', $name);
        flash('success', 'Role ditambahkan.');
        redirect('/admin/role');
    }
    function update(string $id): void {
        $r = $this->role((int)$id);
        if ($r['is_super']) abort(403, 'Role Admin Utama tidak dapat diubah.');
        $name = post('name');
        if (mb_strlen($name) < 3 || DB::val('SELECT id FROM roles WHERE name=? AND id<>?', [$name, $r['id']])) { with_old($_POST); flash('error', 'Nama role minimal 3 karakter dan harus unik.'); redirect("/admin/role/$id/edit"); }
        DB::tx(function () use ($r, $name) {
            DB::exec('UPDATE roles SET name=?, description=? WHERE id=?', [$name, mb_substr(post('description'), 0, 255), $r['id']]);
            $this->savePerms((int)$r['id']);
        });
        audit('role_edit', $name);
        flash('success', 'Role diperbarui.');
        redirect('/admin/role');
    }
    function delete(string $id): void {
        $r = $this->role((int)$id);
        if ($r['is_super']) abort(403, 'Role Admin Utama tidak dapat dihapus.');
        if ((int)DB::val('SELECT COUNT(*) FROM users WHERE role_id=?', [$r['id']]) > 0) { flash('error', 'Role masih dipakai pengguna. Pindahkan penggunanya dulu.'); back('/admin/role'); }
        DB::exec('DELETE FROM roles WHERE id=?', [$r['id']]);
        audit('role_hapus', $r['name']);
        flash('success', 'Role dihapus.');
        back('/admin/role');
    }
}