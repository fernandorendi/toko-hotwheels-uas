@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    body {
        background-color: #050505 !important;
        background-image: 
            linear-gradient(rgba(255, 0, 60, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 0, 60, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
        color: #e0e0e0;
        font-family: 'Rajdhani', sans-serif;
    }
    .cyber-container {
        max-width: 1000px;
        margin: 40px auto;
    }
    /* Sisi Kiri: Mockup Cyberpunk */
    .cyber-mockup {
    background-size: cover;
    background-position: center;
    width: 100%;
    height: 650px;
    position: relative;
    border: 2px solid #00f3ff;
    box-shadow: 0 0 15px rgba(0, 243, 255, 0.3), inset 0 0 20px rgba(0,0,0,0.8);
    clip-path: polygon(0 0, 100% 0, 100% 90%, 90% 100%, 0 100%);
}
    .cyber-mockup-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
        padding: 30px 20px;
        border-top: 1px solid rgba(0, 243, 255, 0.5);
    }
    /* Sisi Kanan: Panel Form */
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
        width: 20px;
        height: 20px;
        border-bottom: 3px solid #ff003c;
        border-right: 3px solid #ff003c;
    }
    .cyber-title {
        font-family: 'Orbitron', sans-serif;
        color: #00f3ff;
        text-shadow: 0 0 10px rgba(0, 243, 255, 0.6);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 5px;
    }
    .cyber-subtitle {
        color: #ff003c;
        letter-spacing: 3px;
        font-size: 12px;
        text-transform: uppercase;
        margin-bottom: 25px;
    }
    /* Input Styling HUD */
    .cyber-input {
        background-color: rgba(255, 0, 60, 0.05);
        border: 1px solid #333;
        border-bottom: 2px solid #ff003c;
        border-radius: 0;
        color: #fff;
        font-family: 'Rajdhani', sans-serif;
        font-size: 15px;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }
    .cyber-input:focus {
        background-color: rgba(255, 0, 60, 0.1);
        border-color: #ff003c;
        box-shadow: 0 0 10px rgba(255, 0, 60, 0.3);
        color: #fff;
        outline: none;
    }
    .cyber-input::placeholder {
        color: #555;
    }
    /* Tombol Glitch */
    .cyber-btn {
        font-family: 'Orbitron', sans-serif;
        background-color: #00f3ff;
        color: #000;
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
        clip-path: polygon(0 0, 90% 0, 100% 10%, 100% 100%, 10% 100%, 0 90%);
        transition: all 0.2s;
        cursor: pointer;
        position: relative;
    }
    .cyber-btn:hover {
        background-color: #ff003c;
        color: #fff;
        box-shadow: 0 0 15px #ff003c;
    }
    .cyber-link {
        color: #00f3ff;
        text-decoration: none;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: 0.3s;
    }
    .cyber-link:hover {
        color: #ff003c;
        text-shadow: 0 0 8px rgba(255, 0, 60, 0.8);
    }
</style>

<div class="container cyber-container">
    <div class="row align-items-center justify-content-center g-0">
        
       <div class="col-md-5 d-none d-md-block pe-4">
    <div class="cyber-mockup" style="background-image: url('{{ asset('register.jpg') }}');">
        <div class="cyber-mockup-overlay">
            <h4 class="text-white" style="font-family: 'Orbitron', sans-serif;">Try. Fail. Repeat.</h4>
            <p class="text-danger m-0" style="font-size: 14px;">Go with the Winner</p>
        </div>
    </div>
</div>

        <div class="col-md-5 col-sm-10 col-12">
            <div class="cyber-box shadow-lg">
                <div class="text-center">
                    <h1 class="cyber-title">HOTWHEELS</h1>
                    <div class="cyber-subtitle">User Registration</div>
                </div>
                
                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small text-uppercase fw-bold mb-1">Entity_Name</label>
                        <input type="text" name="name" class="form-control cyber-input" required placeholder="Ketik nama Anda">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small text-uppercase fw-bold mb-1">User_ID (Email)</label>
                        <input type="email" name="email" class="form-control cyber-input" required placeholder="Contoh: user@gmail.com">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small text-uppercase fw-bold mb-1">Access_Key (Pass)</label>
                        <input type="password" name="password" class="form-control cyber-input" required placeholder="Min. 6 Karakter">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold mb-1">Verify_Key</label>
                        <input type="password" name="password_confirmation" class="form-control cyber-input" required placeholder="Ketik ulang password">
                    </div>
                    
                    <button type="submit" class="cyber-btn w-100">Register</button>
                </form>

                <div class="text-center mt-4 pt-3" style="border-top: 1px dashed #333;">
                    <span class="text-secondary" style="font-size: 14px;">Sudah Memiliki Akun?</span><br>
                    <a href="{{ route('login') }}" class="cyber-link d-inline-block mt-2">> Login <</a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection