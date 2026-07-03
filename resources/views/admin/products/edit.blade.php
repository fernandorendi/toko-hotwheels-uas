@extends('layouts.app')

@section('content')
    <h3>Edit Produk Hot Wheels</h3>
    <hr>

    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <table cellpadding="8">
            <tr>
                <td><label>Nama Produk</label></td>
                <td><input type="text" name="name" value="{{ $product->name }}" required style="width: 300px;"></td>
            </tr>
            <tr>
                <td><label>Kategori</label></td>
                <td>
                    <select name="category_id" required style="width: 308px;">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
            </tr>
            <tr>
                <td><label>Seri Koleksi</label></td>
                <td><input type="text" name="series" value="{{ $product->series }}" style="width: 300px;"></td>
            </tr>
            <tr>
                <td><label>Harga</label></td>
                <td><input type="number" name="price" value="{{ $product->price }}" required style="width: 300px;"></td>
            </tr>
            <tr>
                <td><label>Jumlah Stok</label></td>
                <td><input type="number" name="stock" value="{{ $product->stock }}" required style="width: 300px;"></td>
            </tr>
            
            <tr>
                <td><label>Foto Saat Ini</label></td>
                <td>
                    @if($product->image && file_exists(public_path('uploads/products/' . $product->image)))
                        <img src="{{ asset('uploads/products/' . $product->image) }}" alt="Foto Produk" width="120" style="display:block; margin-bottom:5px; border: 1px solid #ccc; padding: 2px; border-radius: 4px;">
                        <small style="color: green;">✓ Sudah ada foto terpasang</small>
                    @else
                        <span style="color: #999; font-size: 12px; display:block; margin-bottom:5px;">(Belum ada foto produk)</span>
                    @endif
                </td>
            </tr>
            
            <tr>
                <td><label>Ganti Foto Baru</label></td>
                <td>
                    <input type="file" name="image" accept="image/*">
                    <br><small style="color: gray;">*Biarkan tulisan "No file chosen" jika tidak ingin mengubah foto yang sudah ada</small>
                </td>
            </tr>
            
            <tr>
                <td></td>
                <td>
                    <button type="submit">Perbarui Produk</button>
                    <a href="{{ route('products.index') }}"><button type="button">Batal</button></a>
                </td>
            </tr>
        </table>
    </form>
@endsection