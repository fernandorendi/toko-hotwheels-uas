@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    body {
        background-color: #050505 !important;
        background-image: 
            linear-gradient(rgba(0, 243, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 243, 255, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
        color: #e0e0e0;
        font-family: 'Rajdhani', sans-serif;
    }

    /* Animasi Pop-Up HUD */
    @keyframes hudPop {
        0% { opacity: 0; transform: translateY(20px) scale(0.98); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    .hud-anim-1 { animation: hudPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    .hud-anim-2 { animation: hudPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s forwards; opacity: 0; }
    .hud-anim-3 { animation: hudPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.4s forwards; opacity: 0; }

    /* Panel Profil / Dashboard Box */
    .cyber-panel {
        background: rgba(10, 10, 12, 0.85);
        backdrop-filter: blur(5px);
        border: 1px solid #222;
        border-top: 3px solid #00f3ff;
        padding: 30px;
        position: relative;
        transition: all 0.3s ease;
    }
    .cyber-panel:hover {
        box-shadow: 0 0 20px rgba(0, 243, 255, 0.15);
        border-color: #00f3ff;
    }
    .cyber-panel::after {
        content: '';
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 20px;
        height: 20px;
        border-bottom: 3px solid #00f3ff;
        border-right: 3px solid #00f3ff;
    }

    /* Judul HUD */
    .hud-title {
        font-family: 'Orbitron', sans-serif;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 5px;
    }
    .hud-title span { color: #00f3ff; }
    
    /* Tabel Data Profil */
    .profile-table { width: 100%; color: #ccc; font-size: 16px; }
    .profile-table td {
        padding: 12px 10px;
        border-bottom: 1px dashed #333;
    }
    .profile-table td:first-child {
        font-weight: bold;
        color: #888;
        text-transform: uppercase;
        font-size: 13px;
        width: 40%;
    }
    .profile-value {
        color: #fff;
        font-weight: bold;
        font-family: 'Orbitron', sans-serif;
        font-size: 14px;
        letter-spacing: 1px;
    }

    /* Kartu Aktivitas / Tombol Interaktif */
    .action-card {
        display: block;
        background: rgba(255, 0, 60, 0.05);
        border: 1px solid #333;
        border-left: 4px solid #ff003c;
        padding: 25px 20px;
        color: #e0e0e0;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .action-card:hover {
        background: #ff003c;
        color: #fff;
        transform: translateX(10px);
        box-shadow: 0 0 20px rgba(255, 0, 60, 0.4);
    }
    .action-card h4 {
        font-family: 'Orbitron', sans-serif;
        font-weight: bold;
        margin-bottom: 5px;
        transition: color 0.3s ease;
    }
    .action-card:hover h4 { color: #fff; }
    
    
    .cyber-badge {
        background: rgba(0, 243, 255, 0.1);
        color: #00f3ff;
        border: 1px solid #00f3ff;
        padding: 4px 10px;
        font-size: 11px;
        font-family: 'Orbitron', sans-serif;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>

<div class="container py-5">
    <div class="row mb-5 hud-anim-1">
        <div class="col-12 text-center text-md-start">
            <h2 class="hud-title">HELLO <span>{{ Auth::user()->name }}</span></h2>
            <p class="text-secondary" style="font-size: 16px;">
                >Selamat datang kembali</b>!.
            </p>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-5 hud-anim-2">
            <div class="cyber-panel shadow-lg h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="m-0 fw-bold" style="font-family: 'Orbitron', sans-serif; color: #00f3ff;">
                        [ IDENTITAS_USER ]
                    </h5>
                    <span class="cyber-badge">STATUS: ONLINE</span>
                </div>

                <table class="profile-table">
                    <tr>
                        <td>Nama Lengkap</td>
                        <td class="profile-value">{{ Auth::user()->name }}</td>
                    </tr>
                    <tr>
                        <td>Alamat Email</td>
                        <td class="profile-value">{{ Auth::user()->email }}</td>
                    </tr>
                    <tr>
                        <td>Hak Akses</td>
                        <td>
                            <span style="color: #ff003c; font-weight: bold; font-family: 'Orbitron', sans-serif; font-size: 14px;">
                                {{ Auth::user()->role->name ?? 'User' }} / PEMBELI AKTIF
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none;">Tanggal Registrasi</td>
                        <td class="profile-value" style="border: none; color: #aaa;">
                            {{ Auth::user()->created_at ? Auth::user()->created_at->format('d M Y') : date('d M Y') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="col-lg-7 hud-anim-3">
            <div class="cyber-panel shadow-lg h-100" style="border-top-color: #ff003c;">
                <h5 class="mb-4 fw-bold" style="font-family: 'Orbitron', sans-serif; color: #ff003c;">
                    [ KENDALI_AKTIVITAS ]
                </h5>

                <div class="d-grid gap-3">
                    <a href="{{ route('landing') }}" class="action-card rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="text-danger">🛒 PERGI KE ETALASE TOKO</h4>
                                <span class="small" style="opacity: 0.8;">Cari dan tambah koleksi mobil Hot Wheels impian terbaru Anda hari ini.</span>
                            </div>
                            <span class="fs-4">»</span>
                        </div>
                    </a>

                    <a href="{{ route('transactions.index') }}" class="action-card rounded" style="border-left-color: #00f3ff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="text-info">📦 RIWAYAT NOTA PEMBELIAN</h4>
                                <span class="small" style="opacity: 0.8;">Buka log arsip untuk mengecek daftar unit yang telah berhasil Anda amankan.</span>
                            </div>
                            <span class="fs-4">»</span>
                        </div>
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection