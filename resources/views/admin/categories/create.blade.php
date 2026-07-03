@extends('layouts.app')

@section('content')
    <h3>Tambah Kategori Baru</h3>
    <p><a href="{{ route('categories.index') }}">&lt;&lt; Kembali ke Tabel</a></p>

    <form action="{{ route('categories.store') }}" method="POST">
        @csrf
        
        <table cellpadding="5">
            <tr>
                <td><label>Nama Kategori</label></td>
                <td>:</td>
                <td><input type="text" name="name" required placeholder="Misal: Mainline, Premium, Track Stars"></td>
            </tr>
            <tr>
                <td colspan="3" align="right">
                    <button type="submit">Simpan Kategori</button>
                </td>
            </tr>
        </table>
    </form>
@endsection