# 🍽️ Sistem Kasir Restoran

Sistem manajemen restoran berbasis web yang memungkinkan pengelolaan pesanan, pembayaran, dan pelaporan secara efisien.

## 📋 Fitur Utama

### 👨‍💼 Administrator
- Login & Logout sistem
- Manajemen Meja
  - Tambah meja baru
  - Edit informasi meja
  - Hapus meja
  - Lihat status meja
- Manajemen Menu/Barang
  - Tambah menu baru
  - Edit detail menu
  - Hapus menu
  - Lihat daftar menu
- Manajemen Pengguna
  - Tambah pengguna baru
  - Edit hak akses
  - Hapus pengguna
  - Mengatur role (Admin/Waiter/Kasir/Owner)
- Akses laporan transaksi

### 🍽️ Waiter
- Login & Logout sistem
- Melihat daftar meja yang tersedia
- Manajemen Order
  - Membuat pesanan baru
  - Mencatat pesanan pelanggan
  - Memantau status pesanan

### 💰 Kasir
- Login & Logout sistem
- Melihat daftar pesanan aktif
- Proses Pembayaran
  - Menerima pembayaran (Cash/Debit/Credit)
  - Mencetak struk pembayaran

### 👔 Owner
- Login & Logout sistem
- Akses Laporan Keuangan
  - Laporan penjualan harian
  - Laporan penjualan bulanan
  - Laporan penjualan tahunan
  - Ringkasan omzet dan profit

## 🛠️ Teknologi yang Digunakan
- PHP Native
- MySQL Database
- Bootstrap CSS
- JavaScript
- SweetAlert2

## 💻 Persyaratan Sistem
- PHP 8.2.4 atau lebih tinggi
- MySQL/MariaDB 10.4.28 atau lebih tinggi
- Web Server (Apache/Nginx)

## 🚀 Cara Instalasi
1. Download dan install XAMPP dari https://www.apachefriends.org/download.html
2. Jalankan XAMPP Control Panel dan aktifkan Apache & MySQL
3. Clone repository ini ke folder `C:/xampp/htdocs/kasir_restoran`
4. Import file database `kasir.sql` ke MySQL melalui phpMyAdmin (http://localhost/phpmyadmin)
5. Sesuaikan konfigurasi database di `config/database.php`
6. Akses aplikasi melalui browser di http://localhost/kasir_restoran

## 👥 Akun Default