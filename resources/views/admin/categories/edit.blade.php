@extends('layouts.app')

@section('content')
    <h3>Ubah / Edit Kategori</h3>
    <p><a href="{{ route('categories.index') }}">&lt;&lt; Kembali ke Tabel</a></p>

    <form action="{{ route('categories.update', $category->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <table cellpadding="5">
            <tr>
                <td><label>Nama Kategori</label></td>
                <td>:</td>
                <td><input type="text" name="name" value="{{ $category->name }}" required></td>
            </tr>
            <tr>
                <td colspan="3" align="right">
                    <button type="submit">Perbarui Kategori</button>
                </td>
            </tr>
        </table>
    </form>
@endsection