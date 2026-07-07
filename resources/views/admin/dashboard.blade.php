@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    body {
        background-color: #050505 !important;
        /* Grid background berwarna kemerahan khas Admin */
        background-image: 
            linear-gradient(rgba(255, 0, 60, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 0, 60, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
        color: #e0e0e0;
        font-family: 'Rajdhani', sans-serif;
    }

    /* Animasi Pop-Up Berurutan */
    @keyframes hudPop {
        0% { opacity: 0; transform: translateY(20px) scale(0.98); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    .anim-1 { animation: hudPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    .anim-2 { animation: hudPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.15s forwards; opacity: 0; }
    .anim-3 { animation: hudPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.3s forwards; opacity: 0; }

    /* Panel Kaca Cyberpunk */
    .cyber-panel {
        background: rgba(10, 10, 12, 0.85);
        backdrop-filter: blur(5px);
        border: 1px solid #333;
        border-top: 3px solid #ff003c;
        padding: 25px;
        position: relative;
        height: 100%;
    }
    .cyber-panel::after {
        content: '';
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 15px;
        height: 15px;
        border-bottom: 2px solid #ff003c;
        border-right: 2px solid #ff003c;
    }

    /* Kartu Menu Kendali (Navigasi) */
    .admin-card {
        display: flex;
        align-items: center;
        background: rgba(0, 243, 255, 0.03);
        border: 1px solid #222;
        border-left: 4px solid #00f3ff;
        padding: 20px;
        color: #e0e0e0;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 15px;
        position: relative;
        overflow: hidden;
    }
    .admin-card:hover {
        background: #00f3ff;
        color: #000 !important;
        transform: translateX(10px);
        box-shadow: 0 0 15px rgba(0, 243, 255, 0.4);
    }
    .admin-card:hover .text-secondary { color: #222 !important; }
    
    .admin-card h4 {
        font-family: 'Orbitron', sans-serif;
        font-weight: bold;
        margin-bottom: 5px;
        font-size: 1.1rem;
    }

    /* Kotak Statistik Server */
    .stat-box {
        background: rgba(20, 20, 25, 0.9);
        border: 1px dashed #444;
        padding: 20px;
        text-align: center;
        transition: 0.3s;
    }
    .stat-box:hover {
        border-color: #00f3ff;
        box-shadow: inset 0 0 15px rgba(0, 243, 255, 0.1);
    }
    .stat-value {
        font-family: 'Orbitron', sans-serif;
        font-size: 24px;
        font-weight: bold;
        color: #00f3ff;
        margin: 10px 0;
        text-shadow: 0 0 10px rgba(0,243,255,0.5);
    }
    .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        color: #888;
        letter-spacing: 1px;
    }
</style>

<div class="container py-5">
    
    <!-- 1. HEADER HUD ADMIN -->
    <div class="mb-5 anim-1">
        <h2 style="font-family: 'Orbitron', sans-serif; color: #fff; letter-spacing: 2px;">
            [ <span style="color: #ff003c; text-shadow: 0 0 10px #ff003c;">ROOT_ACCESS</span> ] COMMAND_CENTER
        </h2>
        <p class="text-secondary font-monospace">
            > SYS_LOGIN_VERIFIED: Selamat datang kembali, <b class="text-white">{{ Auth::user()->name }}</b>. Otoritas diizinkan sebagai PENGELOLA TOKO.
        </p>
    </div>

    <div class="row g-4">
        
        <!-- 2. KOLOM KIRI: MENU NAVIGASI (KENDALI CEPAT) -->
        <div class="col-lg-7 anim-2">
            <div class="cyber-panel shadow-lg">
                <h5 class="mb-4 fw-bold" style="font-family: 'Orbitron', sans-serif; color: #ff003c;">
                    >> SYSTEM_NAVIGATION
                </h5>

                <!-- Menu 1: Produk -->
                <a href="{{ route('products.index') }}" class="admin-card">
                    <div class="w-100">
                        <h4 class="text-info">📦 KELOLA STOK PRODUK</h4>
                        <span class="text-secondary small font-monospace">Akses database untuk menambah, edit harga, dan hapus unit mobil.</span>
                    </div>
                    <span class="fs-4 fw-bold">»</span>
                </a>

                <!-- Menu 2: Kategori -->
                <a href="{{ route('categories.index') }}" class="admin-card" style="border-left-color: #ff003c;">
                    <div class="w-100">
                        <h4 class="text-danger">🏷️ KELOLA KATEGORI</h4>
                        <span class="text-secondary small font-monospace">Konfigurasi pemisahan tipe mainan (Mainline, Premium, dll).</span>
                    </div>
                    <span class="fs-4 fw-bold" style="color: #ff003c;">»</span>
                </a>

                <!-- Menu 3: Transaksi -->
                <a href="{{ route('transactions.index') }}" class="admin-card" style="border-left-color: #ffcc00;">
                    <div class="w-100">
                        <h4 class="text-warning">💳 LOG TRANSAKSI MASUK</h4>
                        <span class="text-secondary small font-monospace">Monitor nota pembelian masuk dan total pendapatan toko.</span>
                    </div>
                    <span class="fs-4 fw-bold" style="color: #ffcc00;">»</span>
                </a>
            </div>
        </div>

        <!-- 3. KOLOM KANAN: STATISTIK SERVER -->
        <div class="col-lg-5 anim-3">
            <div class="cyber-panel shadow-lg" style="border-top-color: #00f3ff;">
                <h5 class="mb-4 fw-bold" style="font-family: 'Orbitron', sans-serif; color: #fff;">
                    >> SERVER_STATISTICS
                </h5>
                
                <div class="d-grid gap-3">
                    <!-- Data Stat 1 -->
                    <div class="stat-box">
                        <div class="stat-label">Total Produk Terdaftar</div>
                        <div class="stat-value">{{ \App\Models\Product::count() }} ITEM</div>
                    </div>

                    <!-- Data Stat 2 -->
                    <div class="stat-box">
                        <div class="stat-label">Status Koneksi Database</div>
                        <div class="stat-value" style="color: #00ff88; text-shadow: 0 0 10px rgba(0,255,136,0.5);">ONLINE</div>
                        <span class="badge bg-dark border border-success text-success mt-1 font-monospace">STABLE_CONNECTION</span>
                    </div>

                    <!-- Data Stat 3 -->
                    <div class="stat-box">
                        <div class="stat-label">Sistem Keamanan Aktif</div>
                        <div class="stat-value" style="font-size: 18px; color: #ff003c; text-shadow: 0 0 10px rgba(255,0,60,0.5);">AUTH_SESSION</div>
                        <span class="text-secondary small font-monospace">Protokol Laravel Session Running</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection