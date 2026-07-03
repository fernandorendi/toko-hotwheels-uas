@extends('layouts.app')

@section('content')
    <h3>Panel Admin: Manajemen Stok Hot Wheels</h3>
    
    <p>
        <a href="{{ route('products.create') }}"><b>[+] Tambah Koleksi Baru</b></a>
    </p>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr bgcolor="#cccccc">
                <th>No</th>
                <th>Nama Hot Wheels</th>
                <th>Kategori</th>
                <th>Seri Koleksi</th>
                <th>Harga Jual</th>
                <th>Jumlah Stok</th>
                <th>Pilihan Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><b>{{ $product->name }}</b></td>
                    <td>{{ $product->category->name }}</td>
                    <td>{{ $product->series }}</td>
                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td>{{ $product->stock }} pcs</td>
                    <td>
                        <a href="{{ route('products.edit', $product->id) }}">Edit</a>
                        | 
                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus {{ $product->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center">Belum ada data produk di database.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection