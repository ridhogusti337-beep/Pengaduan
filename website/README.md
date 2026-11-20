# Layanan Pengaduan - PDAM Kabupaten Bungo

Aplikasi Web CRUD modern untuk pengelolaan pengaduan pelanggan pada **Perumda Air Minum Tirta Muaro (PDAM) Kabupaten Bungo**, dibangun menggunakan PHP Native, Tailwind CSS, FontAwesome, Chart.js, dan MySQL (PDO).

---

## 🚀 Fitur Unggulan

### 1. Autentikasi & Otorisasi Pengguna
- **Login User** dengan enkripsi `password_hash` & verifikasi session.
- **Role-Based Access Control**:
  - `admin`: Akses penuh ke seluruh menu (Dashboard, Pengaduan, Data Pelanggan, Manajemen User, Laporan Rekap).
  - `petugas`: Akses Dashboard, Penanganan Pengaduan, dan Data Pelanggan.

### 2. Dashboard Interaktif Modern
- **Kartu Statistik**: Total Pengaduan, Pengaduan Baru, Sedang Diproses, dan Selesai.
- **Grafik Tren Pengaduan**: Line Chart interaktif (Chart.js) 6 bulan terakhir.
- **Distribusi Kategori**: Doughnut Chart komposisi jenis keluhan pelanggan.
- **Widget Prioritas Tinggi & Antrian Pengaduan Terbaru**.

### 3. Modul CRUD Pengaduan Pelanggan
- **Daftar & Filter**: Filter status (Semua, Baru, Proses, Selesai), filter prioritas (Tinggi, Sedang, Rendah), dan pencarian instan.
- **Tambah Pengaduan**: Relasi dinamis ke data pelanggan & kategori, upload foto dokumentasi keluhan.
- **Detail & Tracking**: Histori status lengkap dengan catatan petugas & penanggung jawab.
- **Update Status**: Kemudahan mengubah status pengaduan serta penugasan petugas secara real-time.
- **Edit & Hapus Pengaduan**: Dukungan pembaruan data dan penghapusan aman dengan modal konfirmasi.

### 4. Modul CRUD Data Pelanggan
- Pengelolaan master data pelanggan: No. Pelanggan, Nama, Alamat, Kelurahan, Kecamatan, Kontak, dan Golongan Tarif (`R1`, `R2`, `R3`, `S1`, `S2`, `B1`, `B2`, `I1`).
- Fitur pencarian dan filter per golongan.

### 5. Modul CRUD Manajemen Pengguna
- Pengelolaan akun administrator dan petugas teknis lapangan.
- Pengaturan status aktif/nonaktif akun.

### 6. Laporan Rekapitulasi & Cetak PDF
- Filter laporan berdasarkan rentang tanggal (`tgl_awal` s/d `tgl_akhir`), status, dan kategori.
- Format cetak ramah printer dengan Kop Resmi PDAM Kabupaten Bungo dan kolom tanda tangan pimpinan.

---

## 🛠️ Persyaratan Sistem
- Web Server: **Apache / Nginx** (Laragon atau XAMPP direkomendasikan)
- PHP: **Versi 7.4 ke atas / PHP 8.x**
- Database: **MySQL 5.7+ / MariaDB 10+**
- Koneksi Internet untuk CDN Tailwind CSS & FontAwesome

---

## 📦 Panduan Instalasi & Menjalankan

1. **Pastikan Laragon atau XAMPP Aktif**
   - Jalankan service **Apache** dan **MySQL**.

2. **Setup Database Otomatis**
   - Buka browser dan akses skrip inisialisasi otomatis:
     ```
     http://localhost/website/setup.php
     ```
   - Klik tombol **"Mulai Inisialisasi Database"**. Database `pdam_bungo` beserta tabel dan data dummy akan terbuat otomatis.
   - *Alternatif manual*: Import file `database.sql` melalui phpMyAdmin (`http://localhost/phpmyadmin`).

3. **Akses Aplikasi**
   - Buka URL:
     ```
     http://localhost/website/
     ```

---

## 🔑 Akun Demo Login

| Role | Username | Password | Keterangan |
|---|---|---|---|
| **Administrator** | `admin` | `password` | Akses penuh dashboard, data master, kelola user & cetak laporan |
| **Apk Pelanggan** | `pelanggan` | `password` | Portal pelanggan PDAM: cek ID langganan, buat keluhan & lacak status |

*(Tersedia tombol satu klik "Akun Demo" di halaman login untuk kemudahan testing)*

---

## 📂 Struktur Direktori Proyek

```
c:\laragon\www\website/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── header.php       # HTML Head & styling global
│   ├── sidebar.php      # Navigasi sidebar dinamis
│   ├── topbar.php       # Bar notifikasi & profil akun
│   └── footer.php       # Penutup template
├── pengaduan/
│   ├── index.php        # Daftar & filter pengaduan
│   ├── tambah.php       # Form pengaduan baru
│   ├── detail.php       # Detail, riwayat, dan update status
│   ├── edit.php         # Edit data pengaduan
│   └── hapus.php        # Proses hapus pengaduan
├── pelanggan/
│   ├── index.php        # Master data pelanggan
│   ├── tambah.php       # Tambah pelanggan
│   ├── edit.php         # Edit pelanggan
│   └── hapus.php        # Hapus pelanggan
├── users/
│   ├── index.php        # Manajemen akun user
│   ├── tambah.php       # Tambah user
│   ├── edit.php         # Edit user
│   └── hapus.php        # Hapus user
├── laporan/
│   └── index.php        # Rekap laporan & cetak
├── uploads/
│   ├── pengaduan/       # Foto pengaduan keluhan
│   └── users/           # Foto profil
├── config.php           # Koneksi PDO & helper functions
├── database.sql         # Struktur skema SQL & seed data
├── setup.php            # Skrip auto-installer database
├── login.php            # Halaman otentikasi
├── logout.php           # Proses logout
├── dashboard.php        # Halaman dashboard utama
├── profil.php           # Edit profil & ganti kata sandi
└── index.php            # Entry point (redirect ke login)
```
