@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    /* TEMA DASAR CYBERPUNK UNTUK ADMIN */
    .cyber-box {
        background: rgba(10, 10, 12, 0.85);
        backdrop-filter: blur(5px);
        border: 1px solid #333;
        border-top: 3px solid #00f3ff;
        padding: 30px;
        position: relative;
    }
    .cyber-box::after {
        content: '';
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 15px;
        height: 15px;
        border-bottom: 2px solid #00f3ff;
        border-right: 2px solid #00f3ff;
    }
    .cyber-title {
        font-family: 'Orbitron', sans-serif;
        color: #00f3ff;
        text-shadow: 0 0 10px rgba(0, 243, 255, 0.4);
        font-weight: 700;
        letter-spacing: 2px;
    }

    /* TOMBOL CYBERPUNK */
    .cyber-btn {
        font-family: 'Orbitron', sans-serif;
        background-color: #00f3ff;
        color: #000;
        border: none;
        padding: 10px 20px;
        font-weight: bold;
        text-transform: uppercase;
        clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%);
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .cyber-btn:hover {
        background-color: #fff;
        color: #000;
        box-shadow: 0 0 15px #fff;
    }
    
    /* Tombol Khusus Edit & Hapus */
    .btn-edit {
        background-color: #ffcc00; /* Kuning */
        clip-path: polygon(15% 0, 100% 0, 85% 100%, 0% 100%);
    }
    .btn-edit:hover { background-color: #fff; box-shadow: 0 0 10px #ffcc00; }
    
    .btn-hapus {
        background-color: transparent;
        color: #ff003c;
        border: 1px solid #ff003c;
        clip-path: polygon(15% 0, 100% 0, 85% 100%, 0% 100%);
    }
    .btn-hapus:hover { background-color: #ff003c; color: #fff; box-shadow: 0 0 10px #ff003c; }

    /* TABEL CYBERPUNK */
    .cyber-table { color: #e0e0e0; margin-bottom: 0; --bs-table-bg: transparent !important; width: 100%; border-collapse: collapse; }
    .cyber-table thead th {
        background-color: #111 !important;
        color: #00f3ff;
        border-bottom: 2px solid #333;
        font-family: 'Orbitron', sans-serif;
        font-size: 11px;
        letter-spacing: 1px;
        padding: 15px 10px;
    }
    .cyber-table tbody tr { 
        background-color: transparent !important; 
        border-bottom: 1px solid #222; 
        transition: background-color 0.2s; 
    }
    .cyber-table tbody tr:hover { 
        background-color: rgba(0, 243, 255, 0.1) !important; 
    }
    .cyber-table td { 
        background-color: transparent !important; 
        vertical-align: middle; 
        padding: 15px 10px; 
        border: none;
        font-family: 'Rajdhani', sans-serif;
    }
</style>

<div class="container py-4">

    <div class="cyber-box shadow-lg mb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
            <div>
                <h3 class="cyber-title m-0">PANEL ADMIN</h3>
                <p class="text-info small m-0" style="font-family: 'Orbitron', sans-serif;">>> DATABASE_MANAGEMENT_SYSTEM</p>
            </div>
            
            <a href="{{ route('products.create') }}" class="cyber-btn" style="font-size: 13px;">
                [+] TAMBAH KOLEKSI
            </a>
        </div>

        <div class="table-responsive">
            <table class="table cyber-table text-center m-0">
                <thead>
                    <tr>
                        <th width="50">NO</th>
                        <th class="text-start">NAMA HOT WHEELS</th>
                        <th>KATEGORI</th>
                        <th>SERI KOLEKSI</th>
                        <th>HARGA JUAL</th>
                        <th>STOK</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        <tr>
                            <td class="text-secondary font-monospace">{{ $index + 1 }}</td>
                            
                            <td class="text-start text-white fw-bold" style="font-size: 17px;">
                                {{ $product->name }}
                            </td>
                            
                            <td>
                                <span class="text-info" style="font-size: 14px;">{{ $product->category->name }}</span>
                            </td>
                            
                            <td>
                                <span class="text-secondary" style="font-size: 14px;">{{ $product->series ?? '-' }}</span>
                            </td>
                            
                            <td class="text-warning fw-bold">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                            
                            <td>
                                <span class="badge bg-dark border border-secondary text-white">{{ $product->stock }} PCS</span>
                            </td>
                            
                            <td>
                                <div class="d-flex justify-content-center" style="gap: 8px;">
                                    <a href="{{ route('products.edit', $product->id) }}" class="cyber-btn btn-edit" style="padding: 5px 12px; font-size: 11px; color: #000;">
                                        EDIT
                                    </a>
                                    
                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="m-0" onsubmit="return confirm('SYSTEM_WARNING: Apakah Anda yakin ingin menghapus data {{ $product->name }} secara permanen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn btn-hapus" style="padding: 5px 12px; font-size: 11px;">
                                            HAPUS
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <span class="text-danger fw-bold" style="font-family: 'Orbitron', sans-serif; font-size: 18px;">ERROR 404: DATABASE_EMPTY</span>
                                <p class="text-secondary small mt-2">Belum ada data produk yang tersimpan di dalam sistem.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
    </div>
</div>
@endsection