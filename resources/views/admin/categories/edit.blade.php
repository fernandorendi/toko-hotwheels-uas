@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    .cyber-box {
        background: rgba(10, 10, 12, 0.85); backdrop-filter: blur(5px);
        border: 1px solid #333; border-top: 3px solid #ffcc00; /* Warna Kuning untuk Edit */
        padding: 30px; position: relative; max-width: 600px; margin: 0 auto;
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
    .cyber-btn {
        font-family: 'Orbitron', sans-serif; background-color: #ffcc00; color: #000;
        border: none; padding: 10px 25px; font-weight: bold; text-transform: uppercase;
        clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%); transition: all 0.2s; text-decoration: none; display: inline-block;
    }
    .cyber-btn:hover { background-color: #fff; color: #000; box-shadow: 0 0 15px #fff; }
    
    .btn-batal { background-color: transparent; color: #ff003c; border: 1px solid #ff003c; }
    .btn-batal:hover { background-color: #ff003c; color: #fff; box-shadow: 0 0 10px #ff003c;}
</style>

<div class="container py-4">
    <div class="cyber-box shadow-lg">
        
        <div class="mb-4 border-bottom border-secondary pb-3">
            <h3 class="cyber-title m-0">EDIT KATEGORI</h3>
            <p class="text-warning small m-0 font-monospace">>> OVERRIDE_CATEGORY_DATA</p>
        </div>

        <form action="{{ route('categories.update', $category->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label class="form-label text-secondary small fw-bold font-monospace mb-2">NAMA_KATEGORI</label>
                <input type="text" name="name" value="{{ $category->name }}" class="form-control cyber-input" required>
            </div>

            <div class="d-flex" style="gap: 15px;">
                <button type="submit" class="cyber-btn">PERBARUI DATA</button>
                <a href="{{ route('categories.index') }}" class="cyber-btn btn-batal">BATAL</a>
            </div>
        </form>

    </div>
</div>
@endsection