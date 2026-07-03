@extends('layouts.app')

@section('content')
    <h2>🏎️ DASHBOARD PERSONAL KOLEKTOR</h2>
    <p>Halo, selamat datang <b>{{ Auth::user()->name }}</b>! Senang melihat Anda kembali di etalase kami.</p>
    
    <hr>

    <h3>Aktivitas Akun Anda:</h3>
    <ul>
        <li><a href="{{ route('landing') }}"><b>🛒 Pergi ke Etalase Toko (Belanja Lagi)</b></a> — Cari dan koleksi mobil Hot Wheels impian terbaru Anda hari ini.</li>
        <li><a href="{{ route('transactions.index') }}"><b>📦 Riwayat Nota Pembelian Anda</b></a> — Cek kembali daftar mobil apa saja yang sudah pernah Anda beli di toko kami.</li>
    </ul>

    <hr>

    <h3>Informasi Profil Anggota:</h3>
    <table cellpadding="5" style="border: 1px solid #000; padding: 10px;">
        <tr>
            <td><b>Nama Lengkap</b></td>
            <td>:</td>
            <td>{{ Auth::user()->name }}</td>
        </tr>
        <tr>
            <td><b>Alamat Email</b></td>
            <td>:</td>
            <td>{{ Auth::user()->email }}</td>
        </tr>
        <tr>
            <td><b>Status Keanggotaan</b></td>
            <td>:</td>
            <td><mark>{{ Auth::user()->role->name ?? 'User' }} / Pembeli Aktif</mark></td>
        </tr>
        <tr>
            <td><b>Tanggal Bergabung</b></td>
            <td>:</td>
            <td>{{ Auth::user()->created_at ? Auth::user()->created_at->format('d F Y') : date('d F Y') }}</td>
        </tr>
    </table>
@endsection