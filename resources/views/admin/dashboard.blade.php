@extends('layouts.app')

@section('content')
    <h2>📊 PANEL UTAMA DASHBOARD ADMIN</h2>
    <p>Selamat datang kembali, <b>{{ Auth::user()->name }}</b>! Anda login sebagai pengelola toko.</p>
    
    <hr>
    
    <h3>Menu Kendali Cepat (Navigasi Sistem):</h3>
    <ul>
        <li><a href="{{ route('products.index') }}"><b> Kelola Stok Produk Hot Wheels</b></a> — Tempat menambah, mengedit harga, dan menghapus unit mobil.</li>
        <li><a href="{{ route('categories.index') }}"><b> Kelola Kategori Mainan</b></a> — Tempat memisahkan tipe mainan (Mainline, Premium, dll).</li>
        <li><a href="{{ route('transactions.index') }}"><b> Lihat Riwayat Transaksi Masuk</b></a> — Tempat memantau nota pembelian dan total pendapatan toko.</li>
    </ul>

    <hr>

    <h3>Statistik Singkat Toko:</h3>
    <table border="1" cellpadding="10" cellspacing="0">
        <tr bgcolor="#eeeeee">
            <td><b>Total Produk Terdaftar</b></td>
            <td><b>Status Server Database</b></td>
            <td><b>Sistem Keamanan</b></td>
        </tr>
        <tr>
            <td align="center">{{ \App\Models\Product::count() }} Item Mobil</td>
            <td align="center" style="color: green;"><b>ONLINE (Terhubung)</b></td>
            <td align="center">Laravel Auth Session Active</td>
        </tr>
    </table>
@endsection