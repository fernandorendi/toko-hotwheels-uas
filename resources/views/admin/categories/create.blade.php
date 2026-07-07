@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    .cyber-box {
        background: rgba(10, 10, 12, 0.85); backdrop-filter: blur(5px);
        border: 1px solid #333; border-top: 3px solid #ff003c;
        padding: 30px; position: relative; max-width: 600px; margin: 0 auto;
    }
    .cyber-box::after {
        content: ''; position: absolute; bottom: -3px; right: -3px;
        width: 15px; height: 15px; border-bottom: 2px solid #ff003c; border-right: 2px solid #ff003c;
    }
    .cyber-title {
        font-family: 'Orbitron', sans-serif; color: #ff003c;
        text-shadow: 0 0 10px rgba(255, 0, 60, 0.4); font-weight: 700; letter-spacing: 2px;
    }
    .cyber-input {
        background-color: rgba(255, 0, 60, 0.05); border: 1px solid #333;
        border-bottom: 2px solid #ff003c; border-radius: 0; color: #fff; padding: 12px 15px; font-family: 'Rajdhani', sans-serif;
    }
    .cyber-input:focus {
        background-color: rgba(255, 0, 60, 0.1); border-color: #ff003c; color: #fff; box-shadow: 0 0 10px rgba(255, 0, 60, 0.2);
    }
    .cyber-btn {
        font-family: 'Orbitron', sans-serif; background-color: #ff003c; color: #fff;
        border: none; padding: 10px 25px; font-weight: bold; text-transform: uppercase;
        clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%); transition: all 0.2s; text-decoration: none; display: inline-block;
    }
    .cyber-btn:hover { background-color: #fff; color: #000; box-shadow: 0 0 15px #fff; }
    
    .btn-batal { background-color: transparent; color: #666; border: 1px solid #666; }
    .btn-batal:hover { background-color: #666; color: #fff; }
</style>

<div class="container py-4">
    <div class="cyber-box shadow-lg">
        
        <div class="mb-4 border-bottom border-secondary pb-3">
            <h3 class="cyber-title m-0">TAMBAH KATEGORI</h3>
            <p class="text-danger small m-0 font-monospace">>> CREATE_NEW_CATEGORY_NODE</p>
        </div>

        <form action="{{ route('categories.store') }}" method="POST">
            @csrf

            <div class="mb-5">
                <label class="form-label text-secondary small fw-bold font-monospace mb-2">NAMA_KATEGORI</label>
                <input type="text" name="name" class="form-control cyber-input" required placeholder="Misal: Mainline, Premium, Track Stars...">
            </div>

            <div class="d-flex" style="gap: 15px;">
                <button type="submit" class="cyber-btn">SIMPAN DATA</button>
                <a href="{{ route('categories.index') }}" class="cyber-btn btn-batal">BATAL</a>
            </div>
        </form>

    </div>
</div>
@endsection