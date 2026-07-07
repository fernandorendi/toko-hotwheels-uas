@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    /* Menggunakan base style yang sama dengan Create */
    .cyber-box {
        background: rgba(10, 10, 12, 0.85); backdrop-filter: blur(5px);
        border: 1px solid #333; border-top: 3px solid #ffcc00; /* Warna Edit (Kuning) */
        padding: 30px; position: relative; max-width: 800px; margin: 0 auto;
    }
    .cyber-box::after {
        content: ''; position: absolute; bottom: -3px; right: -3px;
        width: 15px; height: 15px; border-bottom: 2px solid #ffcc00; border-right: 2px solid #ffcc00;
    }
    .cyber-title {
        font-family: 'Orbitron', sans-serif; color: #ffcc00;
        text-shadow: 0 0 10px rgba(255, 204, 0, 0.4); font-weight: 700; letter-spacing: 2px;
    }
    .cyber-input {
        background-color: rgba(255, 204, 0, 0.05); border: 1px solid #333;
        border-bottom: 2px solid #ffcc00; border-radius: 0; color: #fff; padding: 12px 15px; font-family: 'Rajdhani', sans-serif;
    }
    .cyber-input:focus {
        background-color: rgba(255, 204, 0, 0.1); border-color: #ffcc00; color: #fff; box-shadow: 0 0 10px rgba(255, 204, 0, 0.2);
    }
    .cyber-input[type="file"] { padding: 8px 15px; }
    
    .cyber-btn {
        font-family: 'Orbitron', sans-serif; background-color: #ffcc00; color: #000;
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
            <h3 class="cyber-title m-0">EDIT DATA PRODUK</h3>
            <p class="text-warning small m-0 font-monospace">>> MELAKUKAN OVERRIDE PADA DATABASE</p>
        </div>

        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">NAMA_PRODUK</label>
                    <input type="text" name="name" value="{{ $product->name }}" class="form-control cyber-input" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">PILIH_KATEGORI</label>
                    <select name="category_id" class="form-select cyber-input" required>
                        <option value="">-- PILIH KATEGORI --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-secondary small fw-bold font-monospace mb-1">SERI_KOLEKSI</label>
                <input type="text" name="series" value="{{ $product->series }}" class="form-control cyber-input">
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">HARGA_JUAL (IDR)</label>
                    <input type="number" name="price" value="{{ $product->price }}" class="form-control cyber-input" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">JUMLAH_STOK (PCS)</label>
                    <input type="number" name="stock" value="{{ $product->stock }}" class="form-control cyber-input" required>
                </div>
            </div>

            <!-- Panel Visual / Foto -->
            <div class="mb-4 p-3 border border-secondary d-flex align-items-center" style="background: rgba(0,0,0,0.5); gap: 20px;">
                <div>
                    <label class="form-label text-warning small fw-bold font-monospace mb-2 d-block">VISUAL_SAAT_INI</label>
                    @if($product->image && file_exists(public_path('uploads/products/' . $product->image)))
                        <img src="{{ asset('uploads/products/' . $product->image) }}" alt="Foto Produk" style="width: 120px; border: 1px solid #ffcc00; padding: 2px; background: #000;">
                        <small class="text-success font-monospace d-block mt-2">>> VALID</small>
                    @else
                        <div style="width: 120px; height: 120px; border: 1px dashed #666; display: flex; align-items: center; justify-content: center;">
                            <span class="text-secondary font-monospace small">NO_IMG</span>
                        </div>
                    @endif
                </div>
                
                <div class="flex-grow-1">
                    <label class="form-label text-secondary small fw-bold font-monospace mb-1">UPDATE_FOTO_VISUAL</label>
                    <input type="file" name="image" class="form-control cyber-input mb-2" accept="image/*">
                    <small class="text-secondary font-monospace">>> Biarkan kolom ini "No file chosen" jika Anda TIDAK INGIN mengubah foto visual yang sudah ada saat ini.</small>
                </div>
            </div>

            <div class="d-flex" style="gap: 15px;">
                <button type="submit" class="cyber-btn">PERBARUI DATA</button>
                <a href="{{ route('products.index') }}" class="cyber-btn btn-batal">BATAL</a>
            </div>
        </form>

    </div>
</div>
@endsection