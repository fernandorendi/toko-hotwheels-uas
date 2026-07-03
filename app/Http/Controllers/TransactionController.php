<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function __construct()
    {
        // Set konfigurasi utama Midtrans menggunakan import namespace resmi
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$is3ds = true;
    }

    /**
     * Menampilkan Riwayat Transaksi
     */
    public function index()
    {
        if (Auth::user()->role && Auth::user()->role->name === 'Admin') {
            $transactions = Transaction::with('user')->latest()->get();
        } else {
            $transactions = Transaction::where('user_id', Auth::id())->latest()->get();
        }

        return view('admin.transactions.index', compact('transactions'));
    }

    /**
     * Memproses Pembelian Langsung & Pembuatan Token Midtrans Snap
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return redirect()->back()->with('error', 'Maaf, stok untuk produk ' . $product->name . ' telah habis!');
        }

        // 1. Kurangi stok produk
        $product->stock = $product->stock - $request->quantity;
        $product->save();

        // 2. Hitung total belanjaan
        $totalPrice = $product->price * $request->quantity;
        $orderCode = 'TRX-' . strtoupper(uniqid());

        // 3. Buat transaksi lokal
        $transaction = Transaction::create([
            'transaction_code' => $orderCode,
            'user_id'          => Auth::id(),
            'total_price'      => $totalPrice,
            'payment_status'   => 'pending',
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id'     => $product->id,
            'quantity'       => $request->quantity,
            'price_at_sale'  => $product->price,
        ]);

        // 4. Siapkan parameter data kiriman untuk Midtrans Snap
        $params = [
            'transaction_details' => [
                'order_id'     => $orderCode,
                'gross_amount' => (int) $totalPrice,
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email'      => Auth::user()->email,
            ],
            'item_details' => [
                [
                    'id'       => $product->id,
                    'price'    => (int) $product->price,
                    'quantity' => (int) $request->quantity,
                    'name'     => substr($product->name, 0, 50),
                ]
            ]
        ];

        try {
            // 5. Minta token Snap resmi
            $snapToken = Snap::getSnapToken($params);

            // 6. Simpan token ke database
            $transaction->update(['snap_token' => $snapToken]);

            // 7. Arahkan user menuju nota halaman detail pembayaran
            return redirect()->route('transactions.show', $transaction->id)->with('success', 'Transaksi berhasil dibuat! Silakan lakukan pembayaran online.');
        } catch (\Exception $e) {
            return redirect()->route('transactions.index')->with('error', 'Gagal terhubung ke server pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan Detail Nota
     */
    public function show(Transaction $transaction)
    {
        if (Auth::user()->role->name !== 'Admin' && $transaction->user_id !== Auth::id()) {
            return redirect()->route('transactions.index')->with('error', 'Anda tidak memiliki hak akses untuk melihat nota transaksi ini!');
        }

        $transaction->load('details.product', 'user');
        return view('admin.transactions.show', compact('transaction'));
    }
}