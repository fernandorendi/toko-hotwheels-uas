@extends('layouts.app')

@section('content')
    <h3>Panel Admin: Manajemen Kategori Hot Wheels</h3>
    
    <p>
        <a href="{{ route('categories.create') }}"><b>[+] Tambah Kategori Baru</b></a>
        | <a href="{{ route('products.index') }}">Kembali ke Stok Produk</a>
    </p>

    <table border="1" cellpadding="8" cellspacing="0" width="50%">
        <thead>
            <tr bgcolor="#cccccc">
                <th width="10%">No</th>
                <th>Nama Kategori</th>
                <th width="30%">Pilihan Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $index => $category)
                <tr>
                    <td align="center">{{ $index + 1 }}</td>
                    <td><b>{{ $category->name }}</b></td>
                    <td align="center">
                        <a href="{{ route('categories.edit', $category->id) }}">Edit</a>
                        | 
                        <form action="{{ route('categories.destroy', $category->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori {{ $category->name }}? Semua produk di kategori ini mungkin akan terpengaruh.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" align="center">Belum ada data kategori di database.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection