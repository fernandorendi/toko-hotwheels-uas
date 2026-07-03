@extends('layouts.app')

@section('content')
    <h3>Katalog Koleksi Hot Wheels</h3>
    
    <form action="{{ route('landing') }}" method="GET">
        <label>Kategori:</label>
        <select name="category_id" onchange="this.form.submit()">
            <option value="">-- Semua Kategori --</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>

        <label>Cari Nama:</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama mobil...">
        <button type="submit">Cari Barang</button>
    </form>

    <br>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr bgcolor="#cccccc">
                <th>Foto Produk</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Seri Koleksi</th>
                <th>Harga</th>
                <th>Sisa Stok</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td align="center">
                        @if($product->image)
                            <img src="{{ asset('uploads/products/' . $product->image) }}" alt="{{ $product->name }}" width="80" style="display:block; border-radius:4px;">
                        @else
                            <span style="color: #999; font-size: 11px; display:block;">(Tidak ada foto)</span>
                        @endif
                    </td>
                    <td><b>{{ $product->name }}</b></td>
                    <td>{{ $product->category->name ?? 'Tanpa Kategori' }}</td>
                    <td>{{ $product->series ?? '-' }}</td>
                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td>{{ $product->stock }} unit</td>
                    <td>
                        @auth
                            <form action="{{ route('transactions.store') }}" method="POST" onsubmit="disableButton(this)">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn-beli">Beli Langsung</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}"><button type="button">Login untuk Beli</button></a>
                        @endauth
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center">Maaf, produk Hot Wheels belum tersedia atau tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
    function disableButton(form) {
        // Cari tombol di dalam form yang sedang di-submit
        var btn = form.querySelector('.btn-beli');
        if (btn) {
            btn.disabled = true;
            btn.innerText = "Memproses...";
        }
    }
    </script>
@endsection