@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    .cyber-box {
        background: rgba(10, 10, 12, 0.85);
        backdrop-filter: blur(5px);
        border: 1px solid #333;
        border-top: 3px solid #00f3ff;
        padding: 30px;
        position: relative;
        max-width: 800px;
        margin: 0 auto;
    }
    .cyber-box::after {
        content: ''; position: absolute; bottom: -3px; right: -3px;
        width: 15px; height: 15px;
        border-bottom: 2px solid #00f3ff; border-right: 2px solid #00f3ff;
    }
    .cyber-title {
        font-family: 'Orbitron', sans-serif; color: #00f3ff;
        text-shadow: 0 0 10px rgba(0, 243, 255, 0.4); font-weight: 700; letter-spacing: 2px;
    }
    .cyber-input {
        background-color: rgba(0, 243, 255, 0.05); border: 1px solid #333;
        border-bottom: 2px solid #00f3ff; border-radius: 0; color: #fff; padding: 12px 15px; font-family: 'Rajdhani', sans-serif;
    }
    .cyber-input:focus {
        background-color: rgba(0, 243, 255, 0.1); border-color: #00f3ff; color: #fff; box-shadow: 0 0 10px rgba(0, 243, 255, 0.2);
    }
    .cyber-input[type="file"] { padding: 8px 15px; }
    
    .cyber-btn {
        font-family: 'Orbitron', sans-serif; background-color: #00f3ff; color: #000;
        border: none; padding: 10px 25px; font-weight: bold; text-transform: uppercase;
        clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%); transition: all 0.2s; text-decoration: none; display: inline-block;
    }
    .cyber-btn:hover { background-color: #fff; box-shadow: 0 0 15px #fff; color: #000; }
    
    .btn-batal {
        background-color: transparent; color: #ff003c; border: 1px solid #ff003c;
    }
    .btn-batal:hover { background-color: #ff003c; color: #fff; box-shadow: 0 0 10px #ff003c; }
</style>

<div class="container py-4">
    <div class="cyber-box shadow-lg">
        
        <div class="mb-4 border-bottom border-secondary pb-3">
            <h3 class="cyber-title m-0">INPUT DATA PRODUK</h3>
            <p class="text-info small m-0 font-monospace">>> TAMBAH KOLEKSI HOT WHEELS BARU KE DATABASE</p>
        </div>

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">NAMA_PRODUK</label>
                    <input type="text" name="name" class="form-control cyber-input" required placeholder="Masukkan Nama Produk...">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">PILIH_KATEGORI</label>
                    <select name="category_id" class="form-select cyber-input" required>
                        <option value="">-- PILIH KATEGORI --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-secondary small fw-bold font-monospace mb-1">SERI_KOLEKSI</label>
                <input type="text" name="series" class="form-control cyber-input" placeholder="Contoh: Fast & Furious, Silver Series...">
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">HARGA_JUAL (IDR)</label>
                    <input type="number" name="price" class="form-control cyber-input" required placeholder="Contoh: 50000">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">JUMLAH_STOK (PCS)</label>
                    <input type="number" name="stock" class="form-control cyber-input" required placeholder="Contoh: 12">
                </div>
            </div>

            <div class="mb-5 p-3 border border-secondary" style="background: rgba(0,0,0,0.5);">
                <label class="form-label text-info small fw-bold font-monospace mb-1">UPLOAD_FOTO_VISUAL</label>
                <input type="file" name="image" class="form-control cyber-input mb-2" accept="image/*">
                <small class="text-secondary font-monospace">>> Format didukung: JPG, PNG, WEBP. Biarkan kosong jika belum ada foto.</small>
            </div>

            <div class="d-flex" style="gap: 15px;">
                <button type="submit" class="cyber-btn">SIMPAN DATA</button>
                <a href="{{ route('products.index') }}" class="cyber-btn btn-batal">BATAL</a>
            </div>
        </form>

    </div>
</div>
@endsection