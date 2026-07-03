@extends('layouts.app')

@section('content')
    <h2>📄 NOTA DETAIL TRANSAKSI ONLINE</h2>
    <hr>
    
    <table cellpadding="6">
        <tr><td><b>Kode Nota</b></td><td>: {{ $transaction->transaction_code }}</td></tr>
        <tr><td><b>Nama Pembeli</b></td><td>: {{ $transaction->user->name }}</td></tr>
        <tr><td><b>Total Tagihan</b></td><td>: Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</td></tr>
        <tr>
            <td><b>Status Pembayaran</b></td>
            <td>: 
                @if($transaction->payment_status === 'settlement' || $transaction->payment_status === 'success')
                    <span style="color: green; font-weight: bold;">✓ LUNAS (Paid)</span>
                @else
                    <span style="color: orange; font-weight: bold;">⏳ MENUNGGU PEMBAYARAN (Unpaid)</span>
                @endif
            </td>
        </tr>
    </table>

    <br>
    
    @if($transaction->payment_status === 'pending' && $transaction->snap_token)
        <button id="pay-button" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; font-size: 14px; font-weight: bold; border-radius: 4px;">
            💳 BAYAR SEKARANG via ONLINE
        </button>
    @endif
    
    <a href="{{ route('transactions.index') }}"><button type="button" style="padding: 10px 15px;">Kembali ke Daftar</button></a>

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
    
    <script type="text/javascript">
        var payButton = document.getElementById('pay-button');
        if (payButton) {
            payButton.addEventListener('click', function () {
                // Panggil jendela pop-up snap menggunakan token yang dikirim dari controller
                window.snap.pay('{{ $transaction->snap_token }}', {
                    onSuccess: function(result){
                        alert("Pembayaran Berhasil!"); 
                        window.location.reload();
                    },
                    onPending: function(result){
                        alert("Menunggu Anda menyelesaikan transaksi pembayaran."); 
                        window.location.reload();
                    },
                    onError: function(result){
                        alert("Pembayaran gagal atau dibatalkan!");
                        window.location.reload();
                    }
                });
            });
        }
    </script>
@endsection