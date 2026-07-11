### 1. Bahasa Pemrograman yang Digunakan
**a. PHP:** Digunakan sebagai bahasa pemrograman utama di sisi backend (server-side) untuk menangani logika bisnis, pengolahan data, dan perutean (routing).

**b. JavaScript:** Digunakan di sisi frontend untuk menambahkan interaktivitas pada antarmuka pengguna.

**c. HTML5 & CSS3:** Digunakan sebagai fondasi struktur halaman dan styling tampilan (terintegrasi melalui Blade templating).

### 2. Framework, Library, API, dkk yang Digunakan

**a. Framework Backend:** Laravel (Menggunakan arsitektur MVC - Model, View, Controller).

**b. Template Engine:** Laravel Blade (untuk menyusun komponen tampilan frontend secara dinamis dan modular).

**c. Asset Bundler / Build Tool:** Vite (untuk manajemen kompilsi aset CSS dan JavaScript yang cepat).

**d. Database ORM:** Eloquent ORM (bawaan Laravel untuk mempermudah manipulasi dan query data tanpa menulis SQL manual).

**e. Dependency Managers:** **Composer:** Untuk mengelola library/package PHP dependencies.

**f. NPM (Node Package Manager):** Untuk mengelola library/package JavaScript frontend.

### 3.Fungsi Dan Fitur Proyek Yang Dibangun

**Fungsi Proyek**
Menampilkan katalog produk Hot Wheels kepada pelanggan.
Mengelola data produk dan kategori.
Melakukan proses transaksi pembelian.
Mengelola akun pengguna (login dan registrasi).
Menyediakan dashboard berdasarkan hak akses pengguna.

**Fitur Proyek**

**a. Landing Page:** Menampilkan informasi dan daftar produk Hot Wheels yang tersedia kepada pengunjung.

**b. Autentikasi Pengguna:** Memungkinkan pengguna melakukan registrasi, login, dan logout dengan aman.

**c. Dashboard:** Menyediakan halaman utama yang menampilkan menu sesuai hak akses pengguna.

**d. Manajemen Kategori (CRUD):** Mengelola data kategori produk melalui fitur tambah, lihat, ubah, dan hapus.

**e. Manajemen Produk (CRUD):** Mengelola data produk beserta informasi stok, harga, dan kategorinya.

**f. Manajemen Transaksi:** Mencatat dan mengelola proses pembelian serta riwayat transaksi pelanggan.

**g. Database Terintegrasi:** Menyimpan dan menghubungkan seluruh data aplikasi secara terstruktur dalam database.

**h. Framework Laravel:** Menyediakan struktur pengembangan aplikasi berbasis MVC agar sistem lebih terorganisir dan mudah dikembangkan.

### 4.Kelebihan Proyek Yang Di Bangun
Berikut beberapa kelebihan proyek tersebut.

**a. Menggunakan Framework Laravel**
Laravel memiliki struktur kode yang rapi sehingga mudah dikembangkan dan dipelihara.

**b. Menggunakan Konsep MVC**
Pemisahan antara Model, View, dan Controller membuat pengembangan lebih terstruktur dan memudahkan proses debugging.

**c. Memiliki Sistem Autentikasi**
Pengguna dapat melakukan registrasi, login, dan logout sehingga data transaksi lebih terkontrol.

**d. Mendukung Manajemen Produk**
Admin dapat mengelola data produk melalui fitur Create, Read, Update, dan Delete (CRUD).

**e. Mendukung Manajemen Kategori**
Produk dapat dikelompokkan berdasarkan kategori sehingga memudahkan pencarian dan pengelolaan data.

**f. Mendukung Proses Transaksi**
Sistem dapat mencatat transaksi pembelian beserta detail barang yang dibeli.

**g. Menggunakan Database Relasional**
Seluruh data saling terhubung melalui relasi database sehingga mengurangi redundansi data dan meningkatkan konsistensi.

**h. Antarmuka Mudah Digunakan**
Tampilan berbasis web memudahkan pengguna maupun admin dalam mengoperasikan sistem.

**i. Mudah Dikembangkan**
Karena menggunakan Laravel, proyek masih dapat dikembangkan dengan fitur tambahan seperti:

Keranjang belanja (shopping cart).
Wishlist.
Laporan penjualan.
Dashboard statistik.
Sistem diskon.
Notifikasi email.
Integrasi pembayaran online yang lebih lengkap.

**j. Mendukung Pengembangan Skala Besar**
Struktur Laravel memungkinkan penambahan modul baru tanpa harus mengubah keseluruhan sistem, sehingga aplikasi lebih mudah dikembangkan di masa depan.

### 6. Ya, proyek tersebut masih memiliki beberapa **bug dan warning**, meskipun tidak ditemukan kesalahan sintaks PHP.

### Bug utama

a. **Rute admin belum terlindungi**
   Halaman produk dan kategori dapat diakses tanpa login atau oleh pengguna biasa. Ini berisiko karena data dapat ditambah, diubah, atau dihapus sembarang pengguna.

b. **Dashboard dapat error**
   Jika pengguna belum login membuka `/dashboard`, sistem dapat mengalami error karena data pengguna belum tersedia.

c. **Stok langsung berkurang sebelum pembayaran berhasil**
   Saat transaksi dibuat, stok sudah dikurangi meskipun pembayaran masih pending atau gagal.

d. **Status pembayaran Midtrans tidak diperbarui**
   Belum ada webhook atau notifikasi server dari Midtrans, sehingga transaksi dapat tetap berstatus `pending` walaupun sudah dibayar.

e. **Filter kategori tidak berfungsi**
   Nama parameter pada form berbeda dengan parameter yang dibaca controller.

f. **Pencarian produk kurang tepat**
   Penggunaan `orWhere` yang tidak dikelompokkan dapat menampilkan produk dari kategori lain atau produk yang stoknya habis.

g. **Halaman detail produk tidak tersedia**
   Controller memanggil file view detail produk yang belum dibuat, sehingga dapat muncul error `View not found`.

h. **JavaScript modal produk berpotensi error**
   Nama produk yang mengandung tanda petik dapat merusak fungsi JavaScript dan berisiko menimbulkan XSS.

### Warning tambahan

* Relasi `transactions` salah penulisan menjadi `transctions`.
* Seeder utama tidak menjalankan seeder produk dan akun.
* Pemeriksaan admin masih memakai `role_id == 1`, sehingga tidak fleksibel.
* Migrasi tabel user tidak dapat di-rollback.
* File `.env.example` dan database SQLite belum tersedia.
* Penghapusan produk dapat ikut menghapus riwayat detail transaksi.
* Belum tersedia pengujian otomatis untuk login, transaksi, stok, dan pembayaran.

**Kesimpulan:** proyek dapat digunakan untuk demonstrasi, tetapi belum aman untuk penggunaan nyata sebelum masalah autentikasi, stok, dan pembayaran diperbaiki.
