<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AuthController;

// 1. RUTE PUBLIK / UMUM (Bisa diakses langsung)
Route::get('/', [LandingController::class, 'index'])->name('landing');

// CRUD dikeluarkan ke sini agar langsung tampil tanpa terhalang login
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
Route::resource('transactions', TransactionController::class)->only(['index', 'show', 'store']);

// 2. RUTE UTK PENGGUNA YANG BELUM LOGIN (GUEST)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 3. RUTE UTK PENGGUNA YANG SUDAH LOGIN (AUTH)
// Jalur Dashboard Utama
    Route::get('/dashboard', function () {
        
        // Kita ubah pengecekannya menggunakan angka role_id (1 = Admin)
        if (Auth::user()->role_id == 1) {
            return view('admin.dashboard');
        }
        
        // Jika bukan 1 (misal 2), maka masuk ke user
        return view('user.dashboard');
        
    })->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // CRUD Kategori, Produk, Transaksi
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
    Route::resource('transactions', TransactionController::class)->only(['index', 'show', 'store']);

    Route::middleware(['auth'])->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
});