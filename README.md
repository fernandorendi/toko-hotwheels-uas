### 1. Bahasa Pemrograman yang Digunakan
**a. PHP:** Digunakan sebagai bahasa pemrograman utama di sisi backend (server-side) untuk menangani logika bisnis, pengolahan data, dan perutean (routing).

**b. JavaScript:** Digunakan di sisi frontend untuk menambahkan interaktivitas pada antarmuka pengguna.

**c. HTML5 & CSS3:** Digunakan sebagai fondasi struktur halaman dan styling tampilan (terintegrasi melalui Blade templating).

### 2. Framework, Library, API, dkk yang Digunakan

**a. Framework Backend:** Laravel (Menggunakan arsitektur MVC - Model, View, Controller).

**b. Template Engine:** Laravel Blade (untuk menyusun komponen tampilan frontend secara dinamis dan modular).

**c. Asset Bundler / Build Tool:** Vite (untuk manajemen kompilsi aset CSS dan JavaScript yang cepat).

**d. Database ORM:** Eloquent ORM (bawaan Laravel untuk mempermudah manipulasi dan query data tanpa menulis SQL manual).

**e. Dependency Managers:** * **Composer:** Untuk mengelola library/package PHP dependencies.

**f. NPM (Node Package Manager):** Untuk mengelola library/package JavaScript frontend.

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
