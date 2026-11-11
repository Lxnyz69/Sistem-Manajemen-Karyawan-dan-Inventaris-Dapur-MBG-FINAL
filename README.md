# Sistem Manajemen Dapur SPPG (MBG)

Aplikasi web **PHP Native (tanpa framework besar) + MySQL/MariaDB** untuk mengelola karyawan, penggajian, stok, sekolah, menu, pengantaran makanan, dan anggaran dapur. Halaman depan (landing page) menampilkan ringkasan transparan yang diambil langsung dari database.

---

## A. Persyaratan

| Kebutuhan | Keterangan |
|---|---|
| **XAMPP** | Apache + MySQL/MariaDB. Pilih versi dengan **PHP 8.0 atau lebih baru**. |
| PHP | `>= 8.0` (ekstensi `pdo_mysql`, `mbstring`, `gd` aktif; bawaan XAMPP sudah cukup). |
| MySQL/MariaDB | Sudah termasuk di XAMPP. |
| Apache `mod_rewrite` | Aktif secara bawaan di XAMPP (project memakai `.htaccess`). |
| Composer | **Opsional**, hanya untuk pustaka `dompdf` (ekspor PDF slip/invoice gaji). |
| Node.js/NPM | **Opsional**, hanya jika ingin membangun ulang `app.css` dengan Tailwind. File `public/assets/css/app.css` sudah tersedia. |

## B. Instalasi

1. **Install XAMPP** dari https://www.apachefriends.org lalu buka *XAMPP Control Panel*.
2. **Jalankan Apache dan MySQL** (klik *Start* pada keduanya sampai berwarna hijau).
3. **Letakkan project di `htdocs`**, mis. `C:\xampp\htdocs\dapur-sppg\` (ekstrak ZIP lalu ganti nama foldernya tanpa spasi agar URL rapi).
4. **Import database**: buka http://localhost/phpmyadmin → halaman utama → tab *Import* → pilih `database/database.sql` → *Import/Go*. File SQL akan membuat dan memilih database `belumkep_sppg` secara otomatis.
5. **Konfigurasi `.env`** (di folder utama project). Salin dari `.env.example` bila belum ada. Isi bawaan sudah cocok dengan XAMPP standar (lihat bagian D).
6. **Install dependency (opsional)** — hanya untuk ekspor PDF:
   ```bash
   cd C:\xampp\htdocs\dapur-sppg
   composer install
   ```
   Fitur lain tetap berjalan tanpa langkah ini.
7. **Migration/seed**: project **tidak** memakai migration terpisah; seluruh struktur dan data awal sudah ada di file SQL pada langkah 4.
8. **Jalankan**: buka URL pada bagian C.

> Opsional (mengubah gaya Tailwind): `npm install` lalu `npm run build:css`.

## C. Cara Mengakses Website

Jika project berada di `htdocs/`:

- Landing page: `http://localhost/`
- Login: `http://localhost/login`
- Dashboard Admin: `http://localhost/admin`
- Dashboard Karyawan: `http://localhost/dashboard`

Root `.htaccess` mengarahkan semua permintaan ke folder `public/`, sehingga document root tidak perlu diubah. Jika URL/aset bermasalah, isi `APP_BASE_PATH` di `.env` (mis. `/dapur-sppg`).

## D. Konfigurasi Database (`.env`)

```
APP_NAME="Manajemen Perusahaan"
APP_DEBUG=false
APP_BASE_PATH=
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=belumkep_sppg
DB_USER=root
DB_PASS=
```

| Item | Nilai bawaan project |
|---|---|
| Nama database | `belumkep_sppg` |
| Host | `127.0.0.1` |
| Port | `3306` |
| Username | `root` |
| Password | *(kosong)* |

Ubah sesuai kondisi MySQL Anda. Setelah mengubah `.env`, muat ulang halaman. Aktifkan `APP_DEBUG=true` hanya saat pengembangan.

## E. Fitur Website

- **Landing page publik** — responsif (menu HP, tabel sekolah menjadi kartu di layar kecil) dengan animasi halus (muncul saat digulir, angka naik, bar grafik tumbuh); otomatis nonaktif bila perangkat memakai "kurangi gerakan". Isi: hero, statistik, transparansi anggaran, ringkasan pengantaran hari ini, peta pelacakan kurir, daftar sekolah, informasi dapur, dan footer. Semua angka dibaca dari database dan diperbarui otomatis (indikator *Live*).
- **Login & registrasi** — login memakai username atau kode karyawan, dengan pembatasan percobaan login. Pendaftaran mandiri menunggu konfirmasi admin (bisa dimatikan lewat pengaturan).
- **Dashboard Admin** (`/admin`) dan **Dashboard Karyawan** (`/dashboard`).
- **Manajemen karyawan** — tambah, ubah, aktif/nonaktif, hapus, ekspor, serta konfirmasi pendaftar baru.
- **Role & hak akses** — role dan izin dapat dibuat/diubah (`/admin/role`).
- **Penggajian** — kelola kehadiran, proses gaji, pembayaran, koreksi, invoice dan slip gaji (PDF).
- **Stok** — data barang, pergerakan stok, pengajuan tambah stok, stok keluar, persetujuan, ekspor, dan peringatan stok menipis.
- **Anggaran** — pencatatan dana masuk/keluar, koreksi, ekspor, dan ringkasan mingguan.
- **Sekolah & menu** — data sekolah (termasuk koordinat), menu per tanggal.
- **Pengantaran/distribusi** — pembuatan pengiriman, estimasi waktu, perubahan status bertahap, riwayat status.
- **Tugas Kurir** (`/kurir`) — halaman mobile untuk petugas: ubah status dan bagikan lokasi GPS.
- **Notifikasi, profil, ganti password, pengaturan situs** (nama, logo, warna, footer, kontak, mode perawatan, status pendaftaran, dan **tampil/sembunyikan tombol Masuk di beranda** pada *Pengaturan → Akses publik*). Bila tombol disembunyikan, halaman `/login` tetap bisa dibuka langsung lewat alamat.

## F. Role dan Hak Akses

Role disimpan di tabel `roles` dan izinnya di `role_permissions`; seluruhnya bisa diubah dari menu **Role**. Role yang ada pada data awal:

| Role | Ringkasan akses |
|---|---|
| **Admin** (super) | Akses penuh ke seluruh fitur. |
| **Kepala Dapur** | Dashboard, karyawan (lihat/tambah/ubah/hapus/ekspor), konfirmasi karyawan, stok (lihat), tambah & persetujuan stok, stok keluar (lihat), sekolah & menu (lihat), pengantaran (lihat), peringatan stok, serta dashboard/slip/profil sendiri. |
| **Admin Dapur** | Dashboard, karyawan, konfirmasi (lihat), kelola role, sekolah, menu, dan pengantaran (buat/ubah), stok (lihat), persetujuan stok, serta dashboard/slip/profil sendiri. |
| **Bendahara** | Dashboard, gaji dan kelola gaji, anggaran (lihat & kelola), peringatan stok, serta dashboard/slip/profil sendiri. |
| **Gudang** | Stok (lihat/tambah/ubah/hapus/ekspor), tambah stok, stok keluar, persetujuan stok (lihat), serta dashboard/slip/profil sendiri. |
| **Kurir** | Halaman Tugas Kurir (lihat & ubah), pengantaran (lihat & ubah status), serta dashboard/slip/profil sendiri. |

Rincian akses mengikuti data pada tabel `role_permissions`, sehingga dapat berbeda bila admin mengubahnya.

## G. Alur Pengantaran

1. Admin/petugas berwenang membuka **Pengantaran** (`/admin/pengantaran`) dan memilih sekolah aktif, menu, tanggal, dan porsi.
2. **Petugas distribusi hanya dapat dipilih dari karyawan aktif dengan role `Kurir` atau `Pengantaran`.** Role lain (Admin, Admin Dapur, Kepala Dapur, Gudang, Bendahara, dan lainnya) tidak muncul di dropdown.
3. Validasi dilakukan juga di **backend** (`Delivery::findCourier`): ID karyawan yang dikirim dicek ulang terhadap role dan status aktif. Jika tidak sesuai, penyimpanan ditolak dengan pesan galat dan data tidak dibuat. Perbedaan huruf besar/kecil atau spasi pada nama role dinormalisasi saat pencocokan, tanpa mengubah data asli.
4. Status maju satu tahap: *Dalam Pembuatan → Dalam Pengantaran → Sampai di Sekolah → Makanan Diterima*. Kurir memperbaruinya dari `/kurir`; lokasi GPS dibagikan selama *Dalam Pengantaran*.

## H. Troubleshooting

| Masalah | Solusi |
|---|---|
| **Apache tidak berjalan** | Biasanya port 80/443 dipakai aplikasi lain (Skype, IIS, VMware). Hentikan aplikasi tersebut atau ubah port di *Config → httpd.conf*. Cek juga *Logs* di XAMPP. |
| **MySQL tidak berjalan** | Port 3306 bentrok dengan MySQL lain. Hentikan service MySQL lain atau ubah port di `my.ini`, lalu samakan `DB_PORT` di `.env`. |
| **Database gagal terkoneksi** | Pastikan MySQL aktif, lalu import `database/database.sql` dan pastikan `DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS` di `.env` benar. Jika import sebelumnya terhenti atau tabel sudah ada, jangan impor ulang begitu saja; backup data lalu siapkan database `belumkep_sppg` yang kosong. File SQL ini bukan untuk di-import berulang ke tabel yang sama. |
| **Error 1049 / tabel tidak ditemukan (500)** | Pastikan nama database di `.env` adalah `belumkep_sppg` dan impor `database/database.sql` sampai selesai dari phpMyAdmin. |
| **Halaman localhost error / 404** | Pastikan folder ada di `htdocs`, `mod_rewrite` aktif (`AllowOverride All` di `httpd.conf`), dan akses lewat `http://localhost/<nama-folder>/`. Isi `APP_BASE_PATH` bila perlu. |
| **Tampil "Terjadi kesalahan pada server"** | Lihat `storage/logs/app.log`, atau set `APP_DEBUG=true` sementara. |
| **Dependency belum terinstall / PDF gagal** | Jalankan `composer install` di folder project (folder `vendor/` dibutuhkan untuk dompdf). |
| **Error permission / PHP** | Pastikan versi PHP ≥ 8.0, folder `storage/` dan `public/uploads/` dapat ditulis, lalu restart Apache setelah mengubah `php.ini`. |
| **CSS/JS tidak muncul** | Periksa `public/assets/css/app.css` ada, `APP_BASE_PATH` benar, dan koneksi internet aktif (Font Awesome, Google Fonts, dan peta memakai CDN). Bersihkan cache browser (Ctrl+F5). |

## Detail Akun

| ID | Role | Password |
|----|------|----------|
| admin | Admin | belumkepikiran |
| KRY-0001 | Kepala Dapur | belumkepikiran |
| KRY-0002 | Admin Dapur | belumkepikiran |
| KRY-0003 | Bendahara | belumkepikiran |
| KRY-0004 | Gudang | belumkepikiran |
| KRY-0005 | Kurir | belumkepikiran |
| KRY-0006 | Kurir | belumkepikiran |
