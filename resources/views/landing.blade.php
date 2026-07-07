@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    /* TEMA DASAR CYBERPUNK */
    body {
        background-color: #050505 !important;
        background-image: 
            linear-gradient(rgba(0, 243, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 243, 255, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
        color: #e0e0e0;
        font-family: 'Rajdhani', sans-serif;
    }

    /* SMOOTH SCROLL UNTUK SLIDESHOW */
    html {
        scroll-behavior: smooth;
    }

    /* ANIMASI POP-UP HALAMAN */
    @keyframes cyberPopup {
        0% { opacity: 0; transform: translateY(30px) scale(0.95); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    .pop-anim-1 { animation: cyberPopup 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; opacity: 0; }
    .pop-anim-2 { animation: cyberPopup 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s forwards; opacity: 0; }
    .pop-anim-3 { animation: cyberPopup 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.4s forwards; opacity: 0; }

    /* KARTU & PANEL (CYBER BOX) */
    .cyber-box {
        background: rgba(10, 10, 12, 0.85);
        backdrop-filter: blur(5px);
        border: 1px solid #333;
        border-top: 3px solid #ff003c;
        padding: 25px;
        position: relative;
    }
    .cyber-box-alt { border-top: 3px solid #00f3ff; }
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
        color: #ff003c;
        text-shadow: 0 0 10px rgba(255, 0, 60, 0.4);
        font-weight: 700;
        letter-spacing: 2px;
    }

    /* INPUT & TOMBOL */
    .cyber-input {
        background-color: rgba(0, 243, 255, 0.05);
        border: 1px solid #333;
        border-bottom: 2px solid #00f3ff;
        border-radius: 0;
        color: #fff;
        padding: 10px 15px;
        font-size: 15px;
    }
    .cyber-input:focus {
        background-color: rgba(0, 243, 255, 0.1);
        border-color: #00f3ff;
        color: #fff;
        box-shadow: 0 0 10px rgba(0, 243, 255, 0.2);
    }
    .cyber-btn {
        font-family: 'Orbitron', sans-serif;
        background-color: #00f3ff;
        color: #000;
        border: none;
        padding: 10px 15px;
        font-weight: bold;
        text-transform: uppercase;
        clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%);
        transition: all 0.2s;
    }
    .cyber-btn:hover {
        background-color: #ff003c;
        color: #fff;
        box-shadow: 0 0 15px #ff003c;
    }

    /* TABEL CYBERPUNK & EFEK GAMBAR INTERAKTIF */
    /* TABEL CYBERPUNK & EFEK GAMBAR INTERAKTIF */
    .cyber-table { 
        color: #e0e0e0; 
        margin-bottom: 0; 
        --bs-table-bg: transparent !important; /* Paksa Bootstrap agar tidak berwarna putih */
    }
    .cyber-table thead th {
        background-color: #111 !important;
        color: #00f3ff;
        border-bottom: 2px solid #333;
        font-family: 'Orbitron', sans-serif;
        font-size: 11px;
        letter-spacing: 1px;
    }
    .cyber-table tbody tr { 
        background-color: transparent !important; 
        border-bottom: 1px solid #222; 
        transition: background-color 0.2s; 
    }
    .cyber-table tbody tr:hover { 
        background-color: rgba(255, 0, 60, 0.1) !important; 
    }
    .cyber-table td { 
        background-color: transparent !important; /* Hapus warna putih pada kolom */
        vertical-align: middle; 
        padding: 15px 10px; 
        border: none; 
    }
    
    .interactive-img {
        width: 70px; 
        height: 70px; 
        object-fit: cover; 
        border: 1px solid #00f3ff; 
        background: #000; 
        padding: 2px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .interactive-img:hover {
        transform: scale(1.2);
        box-shadow: 0 0 15px #00f3ff;
        border-color: #fff !important;
        position: relative;
        z-index: 10;
    }

    /* SLIDESHOW / CAROUSEL CYBERPUNK */
    .cyber-carousel-box {
        border: 1px solid #333;
        border-top: 3px solid #00f3ff;
        border-bottom: 3px solid #ff003c;
        box-shadow: 0 0 20px rgba(0, 243, 255, 0.1);
        position: relative;
        overflow: hidden;
    }
    .cyber-slide-img {
        height: 700px;
        object-fit: cover;
        opacity: 0.6;
        transition: opacity 0.5s;
    }
    .carousel-item.active .cyber-slide-img { opacity: 0.85; }
    
    .cyber-caption {
        background: rgba(10, 10, 12, 0.85);
        border-left: 4px solid #00f3ff;
        padding: 15px 25px;
        bottom: 10%;
        left: 5%;
        right: auto;
        text-align: left;
        backdrop-filter: blur(5px);
        max-width: 500px;
        clip-path: polygon(0 0, 100% 0, 95% 100%, 0 100%);
    }
    .cyber-caption-title {
        font-family: 'Orbitron', sans-serif;
        color: #00f3ff;
        margin-bottom: 5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 0 0 10px rgba(0,243,255,0.5);
    }
    .carousel-indicators [data-bs-target] { background-color: #00f3ff; height: 4px; }

    /* AKSEN PROMO & MODAL POP-UP */
    .promo-ribbon {
        position: absolute;
        top: 20px;
        left: 0;
        background: #E22027;
        color: #fff;
        padding: 8px 20px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.9rem;
        box-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        z-index: 10;
        clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%);
    }
    
    .cyber-modal-content {
        background: rgba(10, 10, 12, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid #00f3ff;
        border-top: 4px solid #ff003c;
    }

    /* EFEK SLIDESHOW HOVER (4 GAMBAR) */
    .cyber-hover-gallery {
        display: flex;
        width: 100%;
        height: 350px;
        gap: 5px;
        overflow: hidden;
        border: 1px solid #333;
        border-top: 3px solid #00f3ff;
        border-bottom: 3px solid #ff003c;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 243, 255, 0.1);
        padding: 5px;
        background: rgba(10, 10, 12, 0.85);
    }
    
    .hover-panel {
        flex: 1; /* Ukuran awal saat diam */
        position: relative;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        border-radius: 5px;
        transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
        cursor: pointer;
        filter: grayscale(80%) brightness(0.5); /* Gelap saat tidak disorot */
    }
    
    /* Gradien hitam di bawah agar teks terbaca */
    .hover-panel::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 60%);
        opacity: 0;
        transition: opacity 0.4s;
    }
    
    /* Animasi Melebar Ketika Mouse Diarahkan */
    .cyber-hover-gallery:hover .hover-panel:hover {
        flex: 5; /* Melebar 5x lipat */
        filter: grayscale(0%) brightness(1.1); /* Warna kembali normal & terang */
        box-shadow: 0 0 20px #00f3ff;
        z-index: 2;
    }
    .cyber-hover-gallery:hover .hover-panel:hover::after {
        opacity: 1;
    }
    
    /* Animasi Teks Muncul dari Bawah */
    .panel-content {
        position: absolute;
        bottom: -50px; /* Sembunyi di bawah */
        left: 20px;
        opacity: 0;
        transition: all 0.4s ease-in-out;
        z-index: 3;
        white-space: nowrap;
    }
    .cyber-hover-gallery:hover .hover-panel:hover .panel-content {
        bottom: 20px; /* Muncul ke atas */
        opacity: 1;
    }
    .panel-title {
        font-family: 'Orbitron', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 0;
    }
</style>

<div class="container py-4">

    <!-- 1. JUDUL HALAMAN -->
    <div class="mb-4 pop-anim-1">
        <h3 class="cyber-title m-0">Katalog Hot Wheels</h3>
        <p class="text-info small m-0" style="font-family: 'Orbitron', sans-serif;">>> Search Your Dream Car</p>
    </div>

    <!-- 2. SLIDESHOW ATAS (Bisa Diklik) -->
    <div id="cyberSlideshow" class="carousel slide cyber-carousel-box mb-5 pop-anim-2" data-bs-ride="carousel" data-bs-interval="3500">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#cyberSlideshow" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#cyberSlideshow" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#cyberSlideshow" data-bs-slide-to="2"></button>
        </div>
        
        <div class="carousel-inner">
            <div class="carousel-item active">
                <a href="#katalog-stok">
                    <img src="{{ asset('supra.jpg') }}" class="d-block w-100 cyber-slide-img" alt="Promo 1">
                </a>
                <div class="carousel-caption d-none d-md-block cyber-caption">
                    <h4 class="cyber-caption-title">NEW_ARRIVAL</h4>
                    <p class="m-0 text-light small font-monospace">JDM Car (Japanese Domestic Market) terbaru telah hadir.</p>
                </div>
            </div>
            
            <div class="carousel-item">
                <a href="#katalog-stok">
                    <img src="{{ asset('ferrari.jpg') }}" class="d-block w-100 cyber-slide-img" alt="Promo 2">
                </a>
                <div class="carousel-caption d-none d-md-block cyber-caption" style="border-left-color: #ff003c;">
                    <h4 class="cyber-caption-title" style="color: #ff003c; text-shadow: 0 0 10px rgba(255,0,60,0.5);">RARE_EDITION_ALERT</h4>
                    <p class="m-0 text-light small font-monospace">Amankan unit edisi terbatas !!! Segera serok sebelum kehabisan.</p>
                </div>
            </div>

            <div class="carousel-item">
                <a href="#katalog-stok">
                    <img src="{{ asset('pf1.webp') }}" class="d-block w-100 cyber-slide-img" alt="Promo 3">
                </a>
                <div class="carousel-caption d-none d-md-block cyber-caption" style="border-left-color: #00ff88;">
                    <h4 class="cyber-caption-title" style="color: #00ff88; text-shadow: 0 0 10px rgba(0,255,136,0.5);">Premium Edition</h4>
                    <p class="m-0 text-light small font-monospace">Seri Premium Formula 1 Kini Tersedia.</p>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#cyberSlideshow" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0 0 5px #00f3ff);"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#cyberSlideshow" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0 0 5px #00f3ff);"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- 3. BARIS PENCARIAN & TABEL KATALOG -->
    <div id="katalog-stok" class="row g-4 mb-5" style="scroll-margin-top: 50px;">
        
        <!-- Kolom Kiri: Form Pencarian -->
        <div class="col-lg-3 col-md-4 pop-anim-2">
            <div class="cyber-box cyber-box-alt shadow-lg h-100">
                <form action="{{ route('landing') }}" method="GET" class="d-flex flex-column h-100">
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold mb-1">FILTER_KATEGORI</label>
                        <select name="category_id" class="form-select cyber-input" onchange="this.form.submit()">
                            <option value="">-- Semua Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold mb-1">SCAN_NAMA</label>
                        <input type="text" name="search" class="form-control cyber-input" value="{{ request('search') }}" placeholder="Cth: Nissan Skyline...">
                    </div>

                    <button type="submit" class="cyber-btn w-100 mt-auto">
                        SEARCH
                    </button>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Tabel Data -->
        <div class="col-lg-9 col-md-8 pop-anim-3">
            <div class="cyber-box shadow-lg p-0 h-100" style="overflow: hidden;">
                <div class="table-responsive">
                    <table class="table cyber-table text-center m-0">
                        <thead>
                            <tr>
                                <th>Visual</th>
                                <th class="text-start">Kode / Nama</th>
                                <th>Kategori</th>
                                <th>Seri</th>
                                <th>Harga (IDR)</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td width="90">
                                        @if($product->image)
                                            <!-- GAMBAR INTERAKTIF -->
                                            <img src="{{ asset('uploads/products/' . $product->image) }}" 
                                                 alt="{{ $product->name }}" 
                                                 class="interactive-img"
                                                 onclick="showProductModal('{{ asset('uploads/products/' . $product->image) }}', '{{ $product->name }}', '{{ number_format($product->price, 0, ',', '.') }}', '{{ $product->stock }}')">
                                        @else
                                            <span style="font-size: 10px; color: #666; border: 1px dashed #444; padding: 10px; display: block;">NO_IMG</span>
                                        @endif
                                    </td>
                                    <td class="text-start text-white fw-bold" style="font-size: 16px;">
                                        {{ $product->name }}
                                    </td>
                                    <td>
                                        <span class="text-info" style="font-size: 13px;">{{ $product->category->name ?? 'UNKNOWN' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary" style="font-size: 13px;">{{ $product->series ?? '-' }}</span>
                                    </td>
                                    <td class="text-warning fw-bold">
                                        {{ number_format($product->price, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-dark border border-secondary text-white">{{ $product->stock }} unit</span>
                                    </td>
                                    <td>
                                        @auth
                                            <form action="{{ route('transactions.store') }}" method="POST" onsubmit="disableButton(this)" class="m-0">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="cyber-btn btn-beli" style="padding: 6px 15px; font-size: 11px;">
                                                    BELI
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 0; font-family: 'Orbitron', sans-serif; font-size: 10px;">
                                                REQ_LOGIN
                                            </a>
                                        @endauth
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <span class="text-danger fw-bold" style="font-family: 'Orbitron', sans-serif;">ERROR 404: RECORD_NOT_FOUND</span>
                                        <p class="text-secondary small mt-2">Produk mungkin kosong atau tidak cocok dengan filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> <!-- Akhir dari Baris Pencarian & Tabel -->

    <!-- 4. SLIDESHOW BAWAH -->
    <div class="mb-3 d-flex justify-content-between align-items-end">
        <div>
            <h4 class="cyber-title m-0" style="font-size: 1.2rem;">[ HIGHLIGHT_COLLECTIONS ]</h4>
        </div>
    </div>

    <div class="cyber-hover-gallery mb-5">
        
        <div class="hover-panel" style="background-image: url('{{ asset('civic.webp') }}'); border: 1px solid #00f3ff;">
            <div class="panel-content">
                <h3 class="panel-title text-info" style="text-shadow: 0 0 10px #00f3ff;">JDM SERIES</h3>
                <p class="text-white small m-0 font-monospace">Japanese Domestic Market.</p>
            </div>
        </div>
        
        <div class="hover-panel" style="background-image: url('{{ asset('exotic2.webp') }}'); border: 1px solid #ff003c;">
            <div class="panel-content">
                <h3 class="panel-title text-danger" style="text-shadow: 0 0 10px #ff003c;">EXOTIC SUPERCAR</h3>
                <p class="text-white small m-0 font-monospace">Limited item unit.</p>
            </div>
        </div>
        
        <div class="hover-panel" style="background-image: url('{{ asset('f1down.jpg') }}'); border: 1px solid #00ff88;">
            <div class="panel-content">
                <h3 class="panel-title" style="color: #00ff88; text-shadow: 0 0 10px #00ff88;">FORMULA 1</h3>
                <p class="text-white small m-0 font-monospace">Premium F1 race cars.</p>
            </div>
        </div>
        
        <div class="hover-panel" style="background-image: url('{{ asset('transport.webp') }}'); border: 1px solid #ffffff;">
            <div class="panel-content">
                <h3 class="panel-title text-warning" style="text-shadow: 0 0 10px #ffffff;">Team Transport</h3>
                <p class="text-white small m-0 font-monospace">Comes With 2 Premium Cars.</p>
            </div>
        </div>

    </div>

    <!-- 5. GAMBAR PROMOSI BAWAH -->
    <div class="position-relative overflow-hidden rounded mb-5" style="border: 1px solid #333;">
        
        <div class="promo-ribbon">
            HOT DEAL 2026
        </div>

        <img src="{{ asset('loose.jpg') }}" class="img-fluid w-100" style="height: 250px; object-fit: cover;">
        
        <div class="position-absolute top-0 start-50 translate-middle-x mt-4" style="z-index: 10; width: 100%; text-align: center;">
            <h2 style="font-weight: 900; text-shadow: 2px 2px 8px rgb(255, 0, 0); color: #fff;">DISKON SPESIAL KOLEKTOR</h2>
            <p style="font-weight: 600; text-shadow: 2px 2px 8px rgb(0, 46, 249); color: #fff;">Dapatkan potongan harga untuk setiap pembelian unit ke-3!</p>
        </div>
    </div>

</div> <!-- Akhir Container Utama -->

<!-- KOTAK POP-UP (MODAL) GAMBAR & STOK -->
<div class="modal fade" id="cyberProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cyber-modal-content">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-info fw-bold font-monospace" id="modalProductName">NAMA PRODUK</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <!-- Gambar Besar -->
                <img id="modalProductImage" src="" style="width: 100%; max-height: 350px; object-fit: contain; border: 1px solid #333; margin-bottom: 20px;">
                
                <!-- Info Harga dan Stok -->
                <div class="d-flex justify-content-around">
                    <div>
                        <small class="text-secondary font-monospace d-block">HARGA</small>
                        <h4 id="modalProductPrice" class="text-warning fw-bold m-0"></h4>
                    </div>
                    <div style="border-left: 1px solid #333; padding-left: 20px;">
                        <small class="text-secondary font-monospace d-block">SISA STOK</small>
                        <h4 class="text-white fw-bold m-0"><span id="modalProductStock"></span> UNIT</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi HTML/JS Murni untuk menembak data dari tabel ke Pop-Up
function showProductModal(imageSrc, name, price, stock) {
    document.getElementById('modalProductImage').src = imageSrc;
    document.getElementById('modalProductName').innerText = name;
    document.getElementById('modalProductPrice').innerText = 'Rp ' + price;
    document.getElementById('modalProductStock').innerText = stock;
    
    // Tampilkan Modal Bootstrap
    var productModal = new bootstrap.Modal(document.getElementById('cyberProductModal'));
    productModal.show();
}

// Fungsi untuk menonaktifkan tombol beli saat ditekan (agar tidak dobel order)
function disableButton(form) {
    var btn = form.querySelector('.btn-beli');
    if (btn) {
        btn.disabled = true;
        btn.innerText = "PROCESSING...";
        btn.style.backgroundColor = "#555";
        btn.style.color = "#aaa";
        btn.style.boxShadow = "none";
    }
}
</script>
@endsection