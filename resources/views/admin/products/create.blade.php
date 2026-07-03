@extends('layouts.app')

@section('content')
    <h3>Tambah Produk Hot Wheels Baru</h3>
    <hr>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <table cellpadding="8">
            <tr>
                <td><label>Nama Produk</label></td>
                <td><input type="text" name="name" required style="width: 300px;"></td>
            </tr>
            <tr>
                <td><label>Kategori</label></td>
                <td>
                    <select name="category_id" required style="width: 308px;">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
            <tr>
                <td><label>Seri Koleksi</label></td>
                <td><input type="text" name="series" style="width: 300px;"></td>
            </tr>
            <tr>
                <td><label>Harga</label></td>
                <td><input type="number" name="price" required style="width: 300px;"></td>
            </tr>
            <tr>
                <td><label>Stok Awal</label></td>
                <td><input type="number" name="stock" required style="width: 300px;"></td>
            </tr>
            <tr>
                <td><label>Foto Produk</label></td>
                <td><input type="file" name="image" accept="image/*"></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit">Simpan Produk</button>
                    <a href="{{ route('products.index') }}"><button type="button">Kembali</button></a>
                </td>
            </tr>
        </table>
    </form>
@endsection