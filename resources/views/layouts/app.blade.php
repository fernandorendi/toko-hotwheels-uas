<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hot Wheels Store - Kelompok UAS</title>
</head>
<body>

    <h2>🏎️ HOT WHEELS STORE</h2>
    <p>
        <a href="{{ route('landing') }}">Halaman Depan (Etalase Toko)</a>
        
        @auth
            | <span>Halo, <b>{{ Auth::user()->name }}</b> ({{ Auth::user()->role->name }})</span>
            | <a href="{{ route('dashboard') }}">Dashboard Saya</a>
            
            @if(Auth::user()->role->name === 'Admin')
                | <a href="{{ route('products.index') }}">Kelola Stok Produk</a>
                | <a href="{{ route('categories.index') }}">Kelola Kategori</a>
                | <a href="{{ route('transactions.index') }}">Riwayat Transaksi Masuk</a>
            @else
                | <a href="{{ route('transactions.index') }}">Riwayat Belanja Saya</a>
            @endif
            
            | <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color: red;">Keluar (Logout)</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        @else
            | <a href="{{ route('login') }}">Masuk (Login)</a>
            | <a href="{{ route('register') }}">Daftar Anggota Baru</a>
        @endauth
    </p>
    <hr>

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
</body>
</html>