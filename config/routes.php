<?php
/** @var Router $router */
// Publik
$router->get('/', ['HomeController', 'index'], null, true);
$router->get('/sekolah/{id}', ['HomeController', 'school'], null, true);
$router->get('/login', ['AuthController', 'loginForm'], null, true);
$router->post('/login', ['AuthController', 'login'], null, true);
$router->get('/register', ['AuthController', 'registerForm'], null, true);
$router->post('/register', ['AuthController', 'register'], null, true);
$router->post('/logout', ['AuthController', 'logout']);

// Pengguna
$router->get('/dashboard', ['AdminController', 'userDashboard'], 'dashboard_saya.view');
$router->get('/slip/{no}', ['PayrollController', 'slipOwn'], 'slip.view');
$router->get('/slip/{no}/pdf', ['PayrollController', 'slipOwnPdf'], 'slip.export');
$router->get('/profile', ['ProfileController', 'show'], 'profile.view');
$router->get('/admin/profile', ['ProfileController', 'show'], 'profile.view');
$router->post('/profile/identitas', ['ProfileController', 'updateIdentity'], 'profile.edit');
$router->get('/profile/password', ['ProfileController', 'passwordForm'], 'password.edit');
$router->post('/profile/password', ['ProfileController', 'password'], 'password.edit');

// Admin dashboard
$router->get('/admin', ['AdminController', 'dashboard'], 'dashboard.view');

// Karyawan
$router->get('/admin/karyawan', ['EmployeeController', 'index'], 'karyawan.view');
$router->get('/admin/karyawan/export', ['EmployeeController', 'export'], 'karyawan.export');
$router->get('/admin/karyawan/tambah', ['EmployeeController', 'createForm'], 'karyawan.create');
$router->post('/admin/karyawan/tambah', ['EmployeeController', 'store'], 'karyawan.create');
$router->get('/admin/karyawan/konfirmasi', ['EmployeeController', 'pending'], 'konfirmasi.view');
$router->post('/admin/karyawan/konfirmasi/{id}/setujui', ['EmployeeController', 'approve'], 'konfirmasi.approve');
$router->post('/admin/karyawan/konfirmasi/{id}/tolak', ['EmployeeController', 'reject'], 'konfirmasi.approve');
$router->get('/admin/karyawan/{id}', ['EmployeeController', 'show'], 'karyawan.view');
$router->get('/admin/karyawan/{id}/edit', ['EmployeeController', 'editForm'], 'karyawan.edit');
$router->post('/admin/karyawan/{id}/edit', ['EmployeeController', 'update'], 'karyawan.edit');
$router->post('/admin/karyawan/{id}/status', ['EmployeeController', 'toggle'], 'karyawan.edit');
$router->post('/admin/karyawan/{id}/hapus', ['EmployeeController', 'delete'], 'karyawan.delete');

// Gaji
$router->get('/admin/gaji', ['PayrollController', 'index'], 'gaji.view');
$router->get('/admin/gaji/kelola', ['PayrollController', 'manage'], 'kelola_gaji.view');
$router->post('/admin/gaji/kelola/simpan', ['PayrollController', 'saveAttendance'], 'kelola_gaji.edit');
$router->post('/admin/gaji/kelola/proses', ['PayrollController', 'generate'], 'kelola_gaji.create');
$router->get('/admin/gaji/invoice/{no}', ['PayrollController', 'invoice'], 'gaji.view');
$router->get('/admin/gaji/invoice/{no}/pdf', ['PayrollController', 'invoicePdf'], 'gaji.export');
$router->post('/admin/gaji/{id}/bayar', ['PayrollController', 'markPaid'], 'gaji.edit');
$router->post('/admin/gaji/{id}/koreksi', ['PayrollController', 'reopen'], 'gaji.approve');

// Anggaran (halaman: anggaran.view; ubah data: hanya Admin Utama — dicek lagi di controller)
$router->get('/admin/anggaran', ['BudgetController', 'index'], 'anggaran.view');
$router->get('/admin/anggaran/export', ['BudgetController', 'export'], 'anggaran.view');
$router->post('/admin/anggaran/tambah', ['BudgetController', 'store'], 'anggaran.manage');
$router->post('/admin/anggaran/{id}/koreksi', ['BudgetController', 'reverse'], 'anggaran.manage');

// Stok
$router->get('/admin/stok', ['InventoryController', 'index'], 'stok.view');
$router->get('/admin/stok/export', ['InventoryController', 'export'], 'stok.export');
$router->get('/admin/stok/barang/baru', ['InventoryController', 'createForm'], 'stok.create');
$router->post('/admin/stok/barang/baru', ['InventoryController', 'store'], 'stok.create');
$router->get('/admin/stok/barang/{id}', ['InventoryController', 'show'], 'stok.view');
$router->get('/admin/stok/barang/{id}/edit', ['InventoryController', 'editForm'], 'stok.edit');
$router->post('/admin/stok/barang/{id}/edit', ['InventoryController', 'update'], 'stok.edit');
$router->post('/admin/stok/barang/{id}/gerak', ['InventoryController', 'move'], 'stok.edit');
$router->post('/admin/stok/barang/{id}/hapus', ['InventoryController', 'delete'], 'stok.delete');
$router->get('/admin/stok/tambah', ['StockRequestController', 'form'], 'tambah_stok.view');
$router->post('/admin/stok/tambah', ['StockRequestController', 'store'], 'tambah_stok.create');
$router->get('/admin/stok/keluar', ['StockRequestController', 'outForm'], 'keluar_stok.view');
$router->post('/admin/stok/keluar', ['StockRequestController', 'outStore'], 'keluar_stok.create');
$router->get('/admin/stok/persetujuan', ['StockRequestController', 'approvals'], 'persetujuan_stok.view');
$router->post('/admin/stok/persetujuan/{id}/setujui', ['StockRequestController', 'approve'], 'persetujuan_stok.approve');
$router->post('/admin/stok/persetujuan/{id}/tolak', ['StockRequestController', 'reject'], 'persetujuan_stok.approve');

// Role
$router->get('/admin/role', ['RoleController', 'index'], 'role.view');
$router->get('/admin/role/baru', ['RoleController', 'form'], 'role.create');
$router->post('/admin/role/baru', ['RoleController', 'store'], 'role.create');
$router->get('/admin/role/{id}/edit', ['RoleController', 'editForm'], 'role.edit');
$router->post('/admin/role/{id}/edit', ['RoleController', 'update'], 'role.edit');
$router->post('/admin/role/{id}/hapus', ['RoleController', 'delete'], 'role.delete');


// Sekolah, menu, dan pengantaran
$router->get('/admin/sekolah', ['SchoolController', 'index'], 'sekolah.view');
$router->get('/admin/sekolah/baru', ['SchoolController', 'create'], 'sekolah.create');
$router->post('/admin/sekolah/baru', ['SchoolController', 'store'], 'sekolah.create');
$router->get('/admin/sekolah/{id}/edit', ['SchoolController', 'edit'], 'sekolah.edit');
$router->post('/admin/sekolah/{id}/edit', ['SchoolController', 'update'], 'sekolah.edit');
$router->get('/admin/sekolah/{id}', ['SchoolController', 'show'], 'sekolah.view');
$router->post('/admin/sekolah/{id}/status', ['SchoolController', 'toggle'], 'sekolah.edit');
$router->post('/admin/sekolah/{id}/hapus', ['SchoolController', 'delete'], 'sekolah.delete');
$router->get('/admin/menu', ['SchoolController', 'menus'], 'menu.view');
$router->post('/admin/menu/simpan', ['SchoolController', 'saveMenus'], 'menu.create');
$router->post('/admin/menu/{id}/edit', ['SchoolController', 'updateMenu'], 'menu.edit');
$router->post('/admin/menu/{id}/hapus', ['SchoolController', 'deleteMenu'], 'menu.delete');
$router->get('/admin/pengantaran', ['SchoolController', 'deliveries'], 'delivery.view');
$router->post('/admin/pengantaran/baru', ['SchoolController', 'createDelivery'], 'delivery.create');
$router->post('/admin/pengantaran/{id}/status', ['SchoolController', 'updateDelivery'], 'delivery.edit');
$router->post('/admin/pengantaran/{id}/eta', ['SchoolController', 'eta'], 'delivery.edit');

// Live (polling ringan), notifikasi, dan halaman kurir
$router->get('/live/ping', ['LiveController', 'ping'], null, true);
$router->get('/live/stream', ['LiveController', 'stream'], null, true);
$router->get('/live/lokasi', ['LiveController', 'lokasi'], null, true);
$router->get('/notifikasi', ['NotificationController', 'index']);
$router->post('/notifikasi/baca', ['NotificationController', 'read']);
$router->get('/kurir', ['FieldController', 'index'], 'kurir.view');
$router->post('/kurir/lokasi', ['FieldController', 'location'], 'kurir.edit');
$router->post('/kurir/{id}/status', ['FieldController', 'status'], 'kurir.edit');

// Pengaturan
$router->get('/admin/pengaturan', ['SettingsController', 'form'], 'pengaturan.view');
$router->post('/admin/pengaturan', ['SettingsController', 'save'], 'pengaturan.edit');
