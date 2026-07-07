<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hot Wheels Store - Kelompok UAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #050505;"> <!-- Pastikan background body juga gelap -->
    
    <!-- Container untuk logo dengan margin-top agar tidak terlalu menempel ke atas -->
    <div class="container-fluid" style="padding-top: 30px; padding-left: 30px; padding-bottom: 20px;">
        <div class="d-flex align-items-center">
            
            <!-- Logo Hot Wheels (Pojok Kiri, Sedikit turun dari atas) -->
            <a href="{{ route('landing') }}" class="d-flex align-items-center" style="text-decoration: none;">
                <img src="{{ asset('logo hotwheels.png') }}" alt="Hot Wheels Logo" style="height: 200px; width: auto; object-fit: contain;">
            </a>

            <!-- Navigasi Menu (Tampil hanya jika bukan halaman login/register) -->
            @unless(Request::is('login') || Request::is('register'))
            <div class="ms-4 text-white">
                <h2 style="margin: 0; font-size: 1.5rem; color: #fff;">🏎️ HOT WHEELS STORE</h2>
                <p style="margin: 0; font-size: 0.9rem; color: #ccc;">
                    <a href="{{ route('landing') }}" style="color: #00f3ff; text-decoration: none;">Halaman Depan</a>
                    @auth
                        | <span style="color: #fff;"><b>{{ Auth::user()->name }}</b></span>
                        | <a href="{{ route('dashboard') }}" style="color: #00f3ff; text-decoration: none;">Dashboard</a>
                        | <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color: #ff003c; text-decoration: none;">Keluar</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                    @else
                        | <a href="{{ route('login') }}" style="color: #00f3ff; text-decoration: none;">Masuk</a>
                        | <a href="{{ route('register') }}" style="color: #00f3ff; text-decoration: none;">Daftar</a>
                    @endauth
                </p>
            </div>
            @endunless
        </div>
    </div>
    <hr style="border-color: #333;">
    @if(session('success'))
        <p style="color: green; background-color: #e6ffe6; padding: 5px; border: 1px solid green;">
            <b>Sukses:</b> {{ session('success') }}
        </p>
        <hr>
    @endif

    @if(session('error'))
        <p style="color: red; background-color: #ffe6e6; padding: 5px; border: 1px solid red;">
            <b>Gagal:</b> {{ session('error') }}
        </p>
        <hr>
    @endif

    <div style="min-height: 400px; padding: 10px 0;">
        @yield('content')
    </div>

    <hr>
    <p align="center">
        <small>&copy; {{ date('Y') }} - Kelompok UAS Pengembangan Web Toko Hot Wheels</small>
    </p>

    <script src="{{ asset('js/script.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>