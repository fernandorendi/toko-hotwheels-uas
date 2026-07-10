## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
## Kesimpulan

**Ya, proyek ini masih memiliki sejumlah bug dan warning.** Sintaks PHP-nya valid—saya memeriksa seluruh file PHP dan tidak menemukan syntax error—tetapi terdapat masalah pada **keamanan akses, transaksi Midtrans, stok, filter produk, JavaScript, migrasi, dan konfigurasi proyek**.

> Pemeriksaan runtime penuh belum dapat dijalankan karena arsip tidak menyertakan folder `vendor`, `.env.example`, dan executable Composer di lingkungan pemeriksaan. Temuan berikut berasal dari pemeriksaan statis seluruh source code serta PHP lint.

---

# A. Masalah Kritis

## 1. CRUD produk dan kategori dapat diakses tanpa autentikasi

**Lokasi:** `routes/web.php:14–17` dan `routes/web.php:43–46`

```php
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
```

Rute tersebut berada di luar middleware `auth`. Akibatnya, pengguna yang belum login dapat membuka URL seperti:

```text
/products
/products/create
/categories
/categories/create
```

Bahkan pengguna umum dapat mengirim request untuk menambahkan, mengubah, atau menghapus data. CSRF token tidak menggantikan autentikasi atau otorisasi.

### Dampak

* Produk dapat dimodifikasi oleh pengunjung.
* Stok dan harga dapat diubah.
* Produk dan kategori dapat dihapus.
* User biasa dapat mengakses fungsi admin.

### Perbaikan

Pisahkan rute berdasarkan hak akses:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::post('/logout', ...);

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'show', 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
});
```

Middleware `admin` atau policy juga perlu dibuat. Middleware `auth` saja belum cukup karena user biasa tetap dapat mengakses CRUD admin.

---

## 2. `/dashboard` menyebabkan error saat dibuka oleh guest

**Lokasi:** `routes/web.php:29–39`

```php
if (Auth::user()->role_id == 1) {
```

Rute dashboard tidak berada di dalam middleware `auth`. Ketika guest membuka `/dashboard`, `Auth::user()` bernilai `null`.

Kemungkinan error:

```text
Attempt to read property "role_id" on null
```

### Perbaikan

Masukkan dashboard ke grup middleware:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        ...
    })->name('dashboard');
});
```

---

## 3. Stok dikurangi sebelum pembayaran berhasil

**Lokasi:** `TransactionController.php:53–55`

```php
$product->stock = $product->stock - $request->quantity;
$product->save();
```

Stok langsung berkurang sebelum token Midtrans berhasil dibuat dan sebelum pembayaran dilakukan.

Jika:

* Midtrans gagal dihubungi;
* user menutup halaman pembayaran;
* user tidak pernah membayar;
* pembayaran kedaluwarsa;

stok tetap berkurang.

### Dampak serius

Pengguna dapat membuat banyak transaksi pending tanpa membayar hingga stok terlihat habis.

### Perbaikan

* Gunakan database transaction.
* Gunakan `lockForUpdate()` untuk menghindari race condition.
* Tentukan konsep reservasi stok.
* Kembalikan stok ketika transaksi `expire`, `cancel`, atau `deny`.
* Kurangi stok permanen setelah notifikasi pembayaran terverifikasi.

Contoh konsep:

```php
DB::transaction(function () use ($request) {
    $product = Product::lockForUpdate()->findOrFail($request->product_id);

    if ($product->stock < $request->quantity) {
        throw ValidationException::withMessages([
            'quantity' => 'Stok tidak mencukupi.',
        ]);
    }

    // Buat transaksi/reservasi secara atomik.
});
```

---

## 4. Tidak ada webhook/notifikasi Midtrans

Tidak ditemukan endpoint callback atau notification handler Midtrans pada `routes/web.php` maupun controller.

Pada halaman pembayaran, callback sukses hanya melakukan:

```javascript
window.location.reload();
```

**Lokasi:** `admin/transactions/show.blade.php:110–123`

Callback JavaScript dari browser bukan bukti pembayaran yang dapat dipercaya. Selain itu, `payment_status` di database tidak pernah diperbarui menjadi `settlement`.

### Dampak

* Transaksi tetap berstatus `pending` walaupun sudah dibayar.
* Status pembayaran bergantung pada browser user.
* Pembayaran dari metode yang selesai belakangan tidak tercatat.
* Sistem tidak dapat menangani `expire`, `deny`, `cancel`, atau refund.

### Perbaikan

Buat endpoint notifikasi server-to-server:

```php
Route::post('/midtrans/notification', [
    MidtransNotificationController::class,
    'handle',
]);
```

Handler harus:

1. Memverifikasi signature Midtrans.
2. Mencocokkan `order_id`.
3. Memperbarui `payment_status`.
4. Menangani status settlement, pending, expire, cancel, dan deny.
5. Melindungi proses dari notifikasi duplikat.

---

# B. Bug Fungsional Tinggi

## 5. Filter kategori tidak bekerja

Form mengirim parameter:

**Lokasi:** `landing.blade.php:345`

```html
<select name="category_id">
```

Namun controller memeriksa:

**Lokasi:** `LandingController.php`

```php
if ($request->has('category')) {
    ...
    $query->where('slug', $request->category);
}
```

Nama parameter berbeda:

```text
View       : category_id
Controller : category
```

Akibatnya, pemilihan kategori tidak memfilter produk.

### Perbaikan

Gunakan ID:

```php
if ($request->filled('category_id')) {
    $productQuery->where('category_id', $request->integer('category_id'));
}
```

Atau ubah form agar mengirim slug dengan nama `category`.

---

## 6. Pencarian dapat melewati filter stok dan kategori

**Lokasi:** `LandingController.php`

```php
$productQuery
    ->where('name', 'like', '%' . $request->search . '%')
    ->orWhere('series', 'like', '%' . $request->search . '%');
```

`orWhere()` tidak dikelompokkan. Secara logika query dapat menjadi:

```sql
(stock > 0 AND category cocok AND name cocok)
OR series cocok
```

Produk dengan stok kosong atau kategori berbeda dapat ikut tampil apabila `series` cocok.

### Perbaikan

```php
$productQuery->where(function ($query) use ($request) {
    $search = $request->search;

    $query->where('name', 'like', "%{$search}%")
          ->orWhere('series', 'like', "%{$search}%");
});
```

---

## 7. Halaman detail produk tidak tersedia

**Lokasi:** `ProductController.php:70–73`

```php
public function show(Product $product)
{
    return view('admin.products.show', compact('product'));
}
```

File berikut tidak ditemukan:

```text
resources/views/admin/products/show.blade.php
```

Karena `Route::resource('products', ...)` mengaktifkan rute `show`, membuka:

```text
/products/{product}
```

akan menghasilkan error:

```text
View [admin.products.show] not found
```

### Perbaikan

Buat view tersebut atau nonaktifkan rute `show`:

```php
Route::resource('products', ProductController::class)
    ->except('show');
```

---

## 8. Field `series` nullable di controller, tetapi wajib di database

Controller mengizinkan nilai kosong:

```php
'series' => 'nullable|string|max:255',
```

Namun migrasi produk mendefinisikan:

```php
$table->string('series');
```

Kolom tersebut tidak nullable.

### Dampak

Produk tanpa seri dapat gagal disimpan dengan error database seperti:

```text
NOT NULL constraint failed: products.series
```

### Perbaikan

Pilih salah satu:

```php
$table->string('series')->nullable();
```

atau ubah validasinya menjadi:

```php
'series' => 'required|string|max:255',
```

---

## 9. JavaScript modal produk dapat rusak dan berpotensi XSS

**Lokasi:** `landing.blade.php:392`

```php
onclick="showProductModal(
    '...',
    '{{ $product->name }}',
    ...
)"
```

Nama produk dimasukkan langsung ke string JavaScript. Produk seeder bahkan memiliki nama:

```text
'67 Chevy Camaro
```

Karakter apostrof dapat memutus string JavaScript pada atribut `onclick`.

Data lain yang mengandung tanda kutip atau kode tertentu juga berpotensi menjadi injection/XSS.

### Perbaikan

Gunakan `@js`, `Js::from()`, atau data attributes:

```php
onclick='showProductModal(
    @js(asset("uploads/products/".$product->image)),
    @js($product->name),
    @js(number_format($product->price, 0, ",", ".")),
    @js($product->stock)
)'
```

Pendekatan yang lebih bersih adalah menggunakan `data-*` dan event listener JavaScript.

---

## 10. JavaScript eksternal praktis tidak bekerja

**Lokasi:** `public/js/script.js`

### Selector form salah

```javascript
document.querySelectorAll("form[action*='transactions.store']");
```

Pada HTML hasil render, atribut `action` berisi URL `/transactions`, bukan nama route `transactions.store`. Karena itu, form tidak ditemukan.

### Elemen nama produk tidak ditemukan

```javascript
row.querySelector("td b").innerText;
```

Pada tabel landing tidak ada elemen `<b>` untuk nama produk. Jika kode dijalankan, akan terjadi error karena hasil selector `null`.

### Kolom harga salah

```javascript
row.querySelectorAll("td")[3]
```

Indeks `3` adalah kolom seri, sedangkan harga berada pada indeks `4`.

### Selector stok salah

```javascript
document.querySelectorAll("table border tr td");
```

Selector ini mencari elemen `<border>` di dalam `<table>`, bukan tabel dengan atribut atau class tertentu. Hasilnya kosong.

### Perbaikan

Gunakan class khusus:

```html
<form class="buy-form">
```

```javascript
document.querySelectorAll('.buy-form');
document.querySelectorAll('.stock-cell');
```

---

# C. Masalah Role dan Seeder

## 11. Pemeriksaan role tidak konsisten

Login memeriksa nama role:

```php
Auth::user()->role->name === 'Admin'
```

Dashboard memeriksa ID:

```php
Auth::user()->role_id == 1
```

Role ID tidak dijamin selalu `1`.

### Contoh masalah

`DatabaseSeeder` membuat role Admin dan User. Jika `HotWheelsSeeder` dijalankan setelahnya, seeder tersebut membuat role Admin dan User lagi. Admin baru dapat memiliki `role_id = 3`.

Login mengenalinya sebagai Admin berdasarkan nama, tetapi dashboard menganggapnya user karena ID bukan `1`.

### Perbaikan

Jangan menggunakan ID hardcoded:

```php
if (Auth::user()->role?->name === 'Admin') {
```

Lebih baik gunakan method:

```php
public function isAdmin(): bool
{
    return $this->role?->name === 'Admin';
}
```

---

## 12. `DatabaseSeeder` tidak menjalankan `HotWheelsSeeder`

`DatabaseSeeder.php` hanya membuat role:

```php
Role::create(['name' => 'Admin']);
Role::create(['name' => 'User']);
```

Seeder admin, user, kategori, dan produk berada pada `HotWheelsSeeder`, tetapi tidak dipanggil.

Akibatnya, perintah standar:

```bash
php artisan db:seed
```

tidak menghasilkan akun admin atau data produk.

### Perbaikan

```php
public function run(): void
{
    $this->call(HotWheelsSeeder::class);
}
```

Kemudian di `HotWheelsSeeder`, gunakan:

```php
Role::updateOrCreate(
    ['name' => 'Admin'],
    []
);
```

Tambahkan unique constraint pada nama role agar tidak muncul duplikasi.

---

## 13. Relasi user salah eja

**Lokasi:** `User.php:37`

```php
public function transctions()
```

Seharusnya:

```php
public function transactions()
```

Saat kode lain mencoba memanggil:

```php
$user->transactions
```

Laravel tidak menemukan relasinya.

---

# D. Masalah Migrasi dan Instalasi

## 14. Rollback migrasi users tidak bekerja

**Lokasi:** `2026_06_30_071442_create_users_table.php`

```php
public function down(): void
{
    //
}
```

Perintah:

```bash
php artisan migrate:rollback
```

tidak akan menghapus tabel `users`. Ini juga dapat menghambat penghapusan tabel `roles` karena foreign key masih aktif.

### Perbaikan

```php
public function down(): void
{
    Schema::dropIfExists('users');
}
```

---

## 15. `.env.example` tidak tersedia

`composer.json` menjalankan:

```php
file_exists('.env') || copy('.env.example', '.env');
```

Namun `.env.example` tidak terdapat di dalam arsip.

### Dampak

Proses setup otomatis dapat gagal saat menyalin konfigurasi.

File tersebut minimal perlu memuat:

```env
APP_NAME="Hot Wheels Store"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## 16. Database SQLite tidak tersedia

Konfigurasi default memakai SQLite:

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Tetapi tidak ditemukan file:

```text
database/database.sqlite
```

Dengan konfigurasi default, migrasi dapat gagal karena file database tidak ada.

### Perbaikan

```bash
touch database/database.sqlite
```

Atau konfigurasi MySQL secara jelas pada `.env.example`.

---

## 17. Tabel queue dan cache tidak memiliki migrasi

Default konfigurasi:

```php
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Namun tidak ada migrasi untuk tabel:

```text
cache
cache_locks
jobs
job_batches
failed_jobs
```

Sementara perintah development menjalankan:

```bash
php artisan queue:listen
```

Dengan default tersebut, queue worker berpotensi gagal karena tabel `jobs` tidak ada.

### Perbaikan

Tambahkan migrasi queue/cache atau gunakan:

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

# E. Warning Transaksi dan Integritas Data

## 18. Tidak menggunakan transaksi database dan row locking

Proses berikut dijalankan terpisah:

1. Mengecek stok.
2. Mengurangi stok.
3. Membuat transaksi.
4. Membuat detail.
5. Memanggil Midtrans.

Jika salah satu langkah gagal, data dapat menjadi tidak konsisten.

Dua request bersamaan juga bisa membaca jumlah stok yang sama dan menyebabkan overselling.

### Perbaikan

Gunakan:

```php
DB::transaction()
Product::lockForUpdate()
```

Selain itu, panggilan API eksternal perlu dirancang agar rollback dan retry aman.

---

## 19. Error internal Midtrans ditampilkan kepada pengguna

**Lokasi:** `TransactionController.php:105–107`

```php
'Gagal terhubung ke server pembayaran: ' . $e->getMessage()
```

Pesan exception dapat mengungkap konfigurasi server, alamat endpoint, atau detail teknis internal.

### Perbaikan

```php
report($e);

return redirect()
    ->route('transactions.index')
    ->with('error', 'Layanan pembayaran sedang tidak tersedia.');
```

---

## 20. Midtrans selalu menggunakan sandbox pada frontend

**Lokasi:** `admin/transactions/show.blade.php:105`

```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js">
```

Walaupun konfigurasi server memiliki:

```php
MIDTRANS_IS_PRODUCTION=true
```

frontend tetap menggunakan sandbox. Token production dan script sandbox tidak kompatibel.

Gunakan URL berdasarkan konfigurasi:

```php
config('services.midtrans.is_production')
```

Jangan mengakses `env()` langsung dari controller dan Blade karena dapat bermasalah setelah `config:cache`.

---

## 21. Penghapusan produk merusak histori transaksi

Migrasi detail transaksi menggunakan:

```php
$table->foreignId('product_id')
    ->constrained('products')
    ->onDelete('cascade');
```

Ketika admin menghapus produk, seluruh `transaction_details` terkait juga terhapus.

### Dampak

* Detail invoice lama hilang.
* Laporan penjualan berubah.
* Audit transaksi menjadi tidak lengkap.

### Perbaikan

Gunakan salah satu:

* `restrictOnDelete()`;
* soft delete pada produk;
* simpan nama produk dan data snapshot di detail transaksi;
* nullable FK dengan `nullOnDelete()`.

Untuk sistem penjualan, soft delete biasanya lebih aman.

---

# F. Warning Tambahan

## 22. Nama file upload hanya menggunakan timestamp

```php
$fileName = time() . '.' . $file->getClientOriginalExtension();
```

Dua upload pada detik yang sama dapat memakai nama identik dan saling menimpa.

Gunakan:

```php
$fileName = Str::uuid().'.'.$file->extension();
```

atau:

```php
$path = $request->file('image')->store('products', 'public');
```

---

## 23. File lama dihapus sebelum file baru dipastikan berhasil tersimpan

Pada update produk:

1. File lama dihapus.
2. File baru dipindahkan.
3. Database diperbarui.

Jika pemindahan file baru gagal, foto lama sudah hilang.

Simpan file baru terlebih dahulu, perbarui database, lalu hapus file lama setelah proses berhasil.

---

## 24. Penggunaan `$request->all()`

Contoh:

```php
$data = $request->all();
```

Walaupun `$fillable` memberi perlindungan tertentu, praktik yang lebih aman adalah hanya menggunakan data tervalidasi:

```php
$data = $request->validated();
```

Karena validasi saat ini dilakukan langsung melalui `$request->validate()`, hasilnya dapat disimpan:

```php
$data = $request->validate([...]);
```

---

## 25. Pengujian otomatis belum memadai

Test yang tersedia hanya:

```php
$this->get('/')->assertStatus(200);
$this->assertTrue(true);
```

Belum ada pengujian untuk:

* guest tidak boleh mengakses CRUD;
* user biasa tidak boleh menjadi admin;
* stok tidak boleh minus;
* pembelian melebihi stok;
* callback pembayaran;
* transaksi milik user lain;
* filter kategori;
* pencarian produk;
* upload gambar;
* rollback transaksi saat Midtrans gagal.

---

# Prioritas Perbaikan

Urutan paling mendesak:

1. **Lindungi rute CRUD dengan `auth` dan middleware admin.**
2. **Perbaiki transaksi stok menggunakan DB transaction dan locking.**
3. **Tambahkan webhook Midtrans dan verifikasi signature.**
4. **Hilangkan role ID hardcoded dan rapikan seeder.**
5. **Perbaiki filter kategori dan grouping pencarian.**
6. **Perbaiki JavaScript modal serta potensi XSS.**
7. **Samakan validasi `series` dengan struktur database.**
8. **Perbaiki migrasi rollback dan konfigurasi `.env.example`.**
9. **Tambahkan feature test untuk seluruh alur kritis.**

Secara keseluruhan, tampilan dan struktur dasar Laravel sudah terbentuk, tetapi proyek ini **belum aman digunakan sebagai aplikasi transaksi nyata** sebelum bagian otorisasi, stok, dan Midtrans diperbaiki.
