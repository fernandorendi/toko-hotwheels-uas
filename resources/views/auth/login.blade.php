@extends('layouts.app')

@section('content')
    <h3>Form Login Pengguna</h3>
    <p>Belum punya akun? <a href="{{ route('register') }}">Daftar Akun User di Sini</a></p>

    <form action="{{ route('login') }}" method="POST">
        @csrf
        <table cellpadding="5">
            <tr>
                <td><label>Alamat Email</label></td>
                <td>:</td>
                <td><input type="email" name="email" required placeholder="admin@gmail.com atau user@gmail.com"></td>
            </tr>
            <tr>
                <td><label>Kata Sandi (Password)</label></td>
                <td>:</td>
                <td><input type="password" name="password" required placeholder="Masukkan password"></td>
            </tr>
            <tr>
                <td colspan="3" align="right">
                    <button type="submit">Masuk Sistem (Login)</button>
                </td>
            </tr>
        </table>
    </form>
@endsection