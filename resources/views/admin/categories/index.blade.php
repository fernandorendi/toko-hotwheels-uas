@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    .cyber-box {
        background: rgba(10, 10, 12, 0.85); backdrop-filter: blur(5px);
        border: 1px solid #333; border-top: 3px solid #ff003c; /* Merah untuk Kategori */
        padding: 30px; position: relative;
    }
    .cyber-box::after {
        content: ''; position: absolute; bottom: -3px; right: -3px;
        width: 15px; height: 15px; border-bottom: 2px solid #ff003c; border-right: 2px solid #ff003c;
    }
    .cyber-title {
        font-family: 'Orbitron', sans-serif; color: #ff003c;
        text-shadow: 0 0 10px rgba(255, 0, 60, 0.4); font-weight: 700; letter-spacing: 2px;
    }

    .cyber-btn {
        font-family: 'Orbitron', sans-serif; background-color: #ff003c; color: #fff;
        border: none; padding: 10px 20px; font-weight: bold; text-transform: uppercase;
        clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%); transition: all 0.2s; text-decoration: none; display: inline-block;
    }
    .cyber-btn:hover { background-color: #fff; color: #000; box-shadow: 0 0 15px #fff; }
    
    .btn-outline-cyber { background-color: transparent; border: 1px solid #00f3ff; color: #00f3ff; }
    .btn-outline-cyber:hover { background-color: #00f3ff; color: #000; box-shadow: 0 0 10px #00f3ff; }

    .btn-edit { background-color: #ffcc00; color: #000; clip-path: polygon(15% 0, 100% 0, 85% 100%, 0% 100%); }
    .btn-edit:hover { background-color: #fff; box-shadow: 0 0 10px #ffcc00; }
    
    .btn-hapus { background-color: transparent; color: #ff003c; border: 1px solid #ff003c; clip-path: polygon(15% 0, 100% 0, 85% 100%, 0% 100%); }
    .btn-hapus:hover { background-color: #ff003c; color: #fff; box-shadow: 0 0 10px #ff003c; }

    .cyber-table { color: #e0e0e0; margin-bottom: 0; --bs-table-bg: transparent !important; width: 100%; border-collapse: collapse; }
    .cyber-table thead th {
        background-color: #111 !important; color: #ff003c; border-bottom: 2px solid #333;
        font-family: 'Orbitron', sans-serif; font-size: 12px; letter-spacing: 1px; padding: 15px 10px;
    }
    .cyber-table tbody tr { background-color: transparent !important; border-bottom: 1px solid #222; transition: background-color 0.2s; }
    .cyber-table tbody tr:hover { background-color: rgba(255, 0, 60, 0.1) !important; }
    .cyber-table td { background-color: transparent !important; vertical-align: middle; padding: 15px 10px; border: none; font-family: 'Rajdhani', sans-serif; }
</style>

<div class="container py-4">
    <div class="cyber-box shadow-lg mb-5">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3 gap-3">
            <div>
                <h3 class="cyber-title m-0">MANAJEMEN KATEGORI</h3>
                <p class="text-danger small m-0 font-monospace">>> SYSTEM_CATEGORY_CONTROL</p>
            </div>
            
            <div class="d-flex gap-2">
                <a href="{{ route('categories.create') }}" class="cyber-btn" style="font-size: 13px;">
                    [+] KATEGORI BARU
                </a>
                <a href="{{ route('products.index') }}" class="cyber-btn btn-outline-cyber" style="font-size: 13px;">
                    STOK PRODUK
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table cyber-table text-center m-0">
                <thead>
                    <tr>
                        <th width="10%">ID</th>
                        <th class="text-start">NAMA KATEGORI</th>
                        <th width="30%">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $index => $category)
                        <tr>
                            <td class="text-secondary font-monospace">{{ $index + 1 }}</td>
                            <td class="text-start text-white fw-bold" style="font-size: 17px;">
                                {{ $category->name }}
                            </td>
                            <td>
                                <div class="d-flex justify-content-center" style="gap: 8px;">
                                    <a href="{{ route('categories.edit', $category->id) }}" class="cyber-btn btn-edit" style="padding: 5px 15px; font-size: 11px;">
                                        EDIT
                                    </a>
                                    
                                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="m-0" onsubmit="return confirm('SYSTEM_WARNING: Menghapus kategori {{ $category->name }} mungkin akan mempengaruhi produk di dalamnya. Lanjutkan?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cyber-btn btn-hapus" style="padding: 5px 15px; font-size: 11px;">
                                            HAPUS
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <span class="text-danger fw-bold" style="font-family: 'Orbitron', sans-serif; font-size: 18px;">ERROR 404: CATEGORY_NOT_FOUND</span>
                                <p class="text-secondary small mt-2">Belum ada data kategori di database.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
    </div>
</div>
@endsection