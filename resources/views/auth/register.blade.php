@extends('layouts.app')

@section('content')
    <h3>Pendaftaran Akun Baru (User/Kolektor)</h3>
    <p>Sudah punya akun? <a href="{{ route('login') }}">Login di Sini</a></p>

    <form action="{{ route('register') }}" method="POST">
        @csrf
        <table cellpadding="5">
            <tr>
                <td><label>Nama Lengkap</label></td>
                <td>:</td>
                <td><input type="text" name="name" required placeholder="Ketik nama Anda"></td>
            </tr>
            <tr>
                <td><label>Alamat Email</label></td>
                <td>:</td>
                <td><input type="email" name="email" required placeholder="Contoh: budi@gmail.com"></td>
            </tr>
            <tr>
                <td><label>Kata Sandi (Min 6 Karakter)</label></td>
                <td>:</td>
                <td><input type="password" name="password" required placeholder="Ketik password baru"></td>
            </tr>
            <tr>
                <td><label>Konfirmasi Kata Sandi</label></td>
                <td>:</td>
                <td><input type="password" name="password_confirmation" required placeholder="Ketik ulang password"></td>
            </tr>
            <tr>
                <td colspan="3" align="right">
                    <button type="submit">Daftar Sekarang</button>
                </td>
            </tr>
        </table>
    </form>
@endsection