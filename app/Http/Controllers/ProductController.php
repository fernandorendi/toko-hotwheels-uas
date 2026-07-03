<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar semua produk di panel Admin
     */
    public function index()
    {
        $products = Product::with('category')->latest()->get();
        return view('admin.products.index', compact('products'));
    }

    /**
     * Menampilkan formulir untuk menambah produk baru
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Memproses penyimpanan produk baru beserta fotonya ke database
     */
    public function store(Request $request)
    {
        // 1. Validasi input teks dan file gambar (maksimal ukuran 2MB)
        $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'series'      => 'nullable|string|max:255',
            'price'       => 'required|integer|min:0',
            'stock'       => 'required|integer|min:0',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        // 2. Ambil semua inputan dari form
        $data = $request->all();

        // 3. Logika pemrosesan unggah file foto
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Membuat nama file acak yang unik berdasarkan timestamp waktu
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            
            // Memindahkan file fisik foto ke folder: public/uploads/products/
            $file->move(public_path('uploads/products'), $fileName);
            
            // Masukkan nama file unik ke dalam array data untuk database
            $data['image'] = $fileName;
        }

        // 4. Masukkan data ke database
        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Produk Hot Wheels baru beserta foto berhasil ditambahkan!');
    }

    /**
     * Menampilkan detail produk tertentu (opsional jika dibutuhkan)
     */
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    /**
     * Menampilkan formulir edit produk berdasarkan ID yang dipilih
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Memproses pembaruan data produk dan foto lama di database
     */
    public function update(Request $request, Product $product)
    {
        // 1. Validasi input teks dan file gambar baru (maksimal 2MB)
        $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'series'      => 'nullable|string|max:255',
            'price'       => 'required|integer|min:0',
            'stock'       => 'required|integer|min:0',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // 2. Ambil semua data input teks dari form
        $data = $request->all();

        // 3. Logika jika ada file gambar baru yang diunggah oleh Admin
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '.' . $file->getClientOriginalExtension();

            // HAPUS FOTO LAMA (Jika sebelumnya produk sudah punya foto fisik di folder penyimpanan)
            if ($product->image && file_exists(public_path('uploads/products/' . $product->image))) {
                unlink(public_path('uploads/products/' . $product->image));
            }

            // Pindahkan file foto baru ke folder permanen public/uploads/products/
            $file->move(public_path('uploads/products'), $fileName);

            // Perbarui data array gambar dengan nama file baru
            $data['image'] = $fileName;
        }

        // 4. Jalankan kueri pembaruan data ke database
        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Data produk Hot Wheels berhasil diperbarui!');
    }

    /**
     * Menghapus produk beserta file fotonya dari penyimpanan secara permanen
     */
    public function destroy(Product $product)
    {
        // Hapus file foto dari folder lokal jika produk yang dihapus memiliki foto
        if ($product->image && file_exists(public_path('uploads/products/' . $product->image))) {
            unlink(public_path('uploads/products/' . $product->image));
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus secara permanen!');
    }
}