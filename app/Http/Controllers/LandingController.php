<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Menampilkan halaman utama / etalase toko Hot Wheels.
     */
    public function index(Request $request)
    {
        // 1. Mengambil semua kategori untuk ditampilkan di menu filter/kategori
        $categories = Category::all();

        // 2. Membuat query dasar untuk produk beserta hubungan kategorinya
        $productQuery = Product::with('category')->where('stock', '>', 0);

        // 3. FITUR TAMBAHAN: Jika pengunjung memilih filter berdasarkan kategori tertentu
        if ($request->has('category') && $request->category != '') {
            $productQuery->whereHas('category', function ($query) use ($request) {
                $query->where('slug', $request->category);
            });
        }

        // 4. FITUR TAMBAHAN: Jika pengunjung mencari nama Hot Wheels di kolom pencarian
        if ($request->has('search') && $request->search != '') {
            $productQuery->where('name', 'like', '%' . $request->search . '%')
                         ->orWhere('series', 'like', '%' . $request->search . '%');
        }

        // 5. Ambil data produk terbaru
        $products = $productQuery->latest()->get();

        // 6. Lempar data ke file view bernama 'landing.blade.php'
        return view('landing', compact('categories', 'products'));
    }
}