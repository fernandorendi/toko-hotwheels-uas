### preview website
<img width="1280" height="720" alt="Image" src="https://github.com/user-attachments/assets/506f40d1-a852-4fb8-9a73-c7f47db302d3" />

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

### 5. Ya, proyek tersebut masih memiliki beberapa **bug dan warning**, meskipun tidak ditemukan kesalahan sintaks PHP.

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

## 🛠️ Rencana Perbaikan Bug & Warning (Bug Fixing Roadmap)
perbaikan bug yang sudah dikerjakan sebagai berikut:

### 🚨 I. Perbaikan Bug Utama (High Priority)

| No | Masalah (Bug) | Tindakan Perbaikan (Solusi) | Status |
| :---: | :--- | :--- | :---: |
| **a** | Rute admin belum terlindungi (bisa diakses tanpa login). | Bungkus rute (`routes`) untuk halaman produk, kategori, dan admin menggunakan middleware `auth` dan middleware kustom `isAdmin`. | ⬜ sudah |
| **b** | Dashboard error jika diakses saat belum login. | Tambahkan middleware `auth` pada rute `/dashboard` agar pengguna yang belum login diarahkan otomatis ke halaman login. | ⬜ sudah |
| **c** | Stok langsung berkurang sebelum pembayaran berhasil. | Ubah logika pengurangan stok agar hanya terjadi setelah status pembayaran sukses (`settlement` dari Midtrans), atau gunakan sistem *booking* stok sementara dengan batas waktu. | ⬜ sudah |
| **d** | Status pembayaran Midtrans tidak diperbarui. | Buat *endpoint* Webhook (`Notification Handler`) untuk menerima notifikasi status transaksi dari server Midtrans dan memperbarui status di database secara otomatis. | ⬜ sudah |
| **e** | Filter kategori tidak berfungsi. | Selaraskan nama atribut `name` pada elemen `<select>` di form HTML dengan nama parameter yang dibaca oleh Controller (misal: sama-sama menggunakan `category_id`). | ⬜ sudah |
| **f** | Pencarian produk menampilkan data yang salah/habis. | Perbaiki kueri database (Eloquent/SQL) dengan mengelompokkan kondisi pencarian kata kunci di dalam fungsi penutupan (*closure* `where`), contoh: `$query->where(function($q) { $q->where('name', 'like', ...)->orWhere(...); })`. | ⬜ sudah |
| **g** | Error `View not found` pada detail produk. | Buat file view yang hilang tersebut di dalam folder `resources/views/` sesuai dengan nama yang dipanggil oleh Controller. | ⬜ sudah |
| **h** | JavaScript modal rusak & berpotensi XSS karena tanda petik. | Gunakan fungsi *escaping* (seperti `e()` di Laravel atau `json_encode()`) saat mengoper data string dari PHP ke dalam atribut data HTML atau variabel JavaScript. | ⬜ sudah |

---

### ⚠️ II. Perbaikan Warning & Refactoring (Medium Priority)

- [ ] **Koreksi Typo Relasi:** Mengubah nama fungsi relasi di dalam Model dari `transctions` menjadi `transactions` secara konsisten di seluruh proyek.
- [ ] **Kelengkapan Seeder:** Menambahkan panggilan `$this->call([UserSeeder::class, ProductSeeder::class]);` di dalam file `DatabaseSeeder.php` utama.
- [ ] **Fleksibilitas Role:** Mengganti *hardcoded* `role_id == 1` dengan sistem konfigurasi, *helper*, atau enum (misal: `Role::ADMIN->value`) agar lebih dinamis.
- [ ] **Perbaikan Migrasi:** Melengkapi fungsi `down()` pada file migrasi tabel *user* dengan perintah `Schema::dropIfExists('users');` agar proses *rollback* berjalan lancar.
- [ ] **Kelengkapan Repositori:** Membuat file `.env.example` yang berisi struktur variabel lingkungan tanpa kredensial asli, serta menyiapkan *file* database SQLite kosong jika diperlukan untuk kemudahan instalasi awal.
- [ ] **Integritas Data (Data Integrity):** Mengubah relasi *foreign key* saat penghapusan produk. Jangan gunakan `cascade on delete` pada detail transaksi. Gunakan `restrict` atau simpan riwayat produk dalam bentuk teks di tabel transaksi agar data keuangan masa lalu tidak hilang.
- [ ] **Automated Testing:** Membuat unit atau *feature test* menggunakan PHPUnit/Pest khusus untuk menguji alur login, proses transaksi, pengurangan stok, dan simulasi pembayaran.

