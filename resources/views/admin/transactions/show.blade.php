@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    .anim-fade { animation: fadeIn 0.8s ease forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }

    /* Kotak Nota / Receipt Cyberpunk */
    .cyber-receipt {
        max-width: 600px; margin: 0 auto;
        background: rgba(10, 10, 12, 0.9); backdrop-filter: blur(5px);
        border: 1px solid #333; border-top: 4px solid #00ff88;
        padding: 40px; position: relative;
        box-shadow: 0 0 30px rgba(0, 255, 136, 0.1);
    }
    .cyber-receipt::before {
        content: 'RECEIPT'; position: absolute; top: -15px; left: 20px;
        background: #00ff88; color: #000; font-family: 'Orbitron', sans-serif;
        font-weight: 900; font-size: 12px; padding: 2px 10px; letter-spacing: 2px;
    }
    .receipt-title {
        font-family: 'Orbitron', sans-serif; color: #00ff88;
        font-weight: 700; letter-spacing: 2px; border-bottom: 1px dashed #444;
        padding-bottom: 15px; margin-bottom: 20px;
    }
    
    .detail-row {
        display: flex; justify-content: space-between; margin-bottom: 12px;
        font-family: 'Rajdhani', sans-serif; font-size: 16px;
    }
    .detail-label { color: #888; font-family: 'Orbitron', sans-serif; font-size: 12px; }
    .detail-value { color: #fff; font-weight: bold; font-family: 'monospace'; }

    .total-box {
        background: rgba(0, 255, 136, 0.1); border: 1px solid #00ff88;
        padding: 15px; margin-top: 25px; text-align: center;
    }
    .total-box h2 {
        color: #00ff88; font-family: 'Orbitron', sans-serif; font-weight: 900; margin: 0;
        text-shadow: 0 0 10px rgba(0,255,136,0.5);
    }

    .status-badge {
        display: inline-block; padding: 6px 20px; font-family: 'Orbitron', sans-serif;
        font-weight: 900; font-size: 14px; clip-path: polygon(10% 0, 100% 0, 90% 100%, 0% 100%);
    }
    .status-success { background: #00ff88; color: #000; box-shadow: 0 0 10px #00ff88; }
    .status-pending { background: #ffcc00; color: #000; box-shadow: 0 0 10px #ffcc00; }

    /* Tombol Bayar Midtrans */
    .btn-pay-cyber {
        font-family: 'Orbitron', sans-serif; background-color: #ffcc00; color: #000;
        border: none; padding: 12px; width: 100%; font-weight: 900; font-size: 16px;
        text-transform: uppercase; margin-top: 25px; transition: all 0.2s; cursor: pointer;
        clip-path: polygon(5% 0, 100% 0, 95% 100%, 0% 100%);
    }
    .btn-pay-cyber:hover { background-color: #fff; box-shadow: 0 0 15px #fff; }

    .btn-back {
        display: block; text-align: center; margin-top: 25px;
        color: #666; font-family: 'Orbitron', sans-serif; font-size: 11px; text-decoration: none;
    }
    .btn-back:hover { color: #fff; }
</style>

<div class="container py-5">
    <div class="cyber-receipt anim-fade">
        <h3 class="receipt-title text-center">INVOICE_{{ $transaction->transaction_code }}</h3>
        
        <div class="detail-row">
            <span class="detail-label">WAKTU_TRANSAKSI:</span>
            <span class="detail-value">{{ $transaction->created_at->format('d M Y H:i:s') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">ENTITAS_PEMBELI:</span>
            <span class="detail-value text-info">{{ $transaction->user->name ?? 'GUEST' }}</span>
        </div>

        <div class="total-box">
            <span class="detail-label d-block mb-1" style="color: #00ff88;">TOTAL_PEMBAYARAN</span>
            <h2>Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</h2>
        </div>

        <div class="text-center mt-4 mb-2">
            <span class="detail-label d-block mb-2">STATUS_SYSTEM:</span>
            @if($transaction->payment_status === 'success' || $transaction->payment_status === 'settlement' || $transaction->payment_status === 'lunas')
                <span class="status-badge status-success">LUNAS / VERIFIED</span>
            @else
                <span class="status-badge status-pending">UNPAID / PENDING</span>
            @endif
        </div>

        @if($transaction->payment_status === 'pending' && $transaction->snap_token)
            <button id="pay-button" class="btn-pay-cyber">
                INITIATE_PAYMENT >>
            </button>
        @endif
        
        <a href="{{ route('transactions.index') }}" class="btn-back"><< KEMBALI KE LOG TRANSAKSI</a>
    </div>
</div>

<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') ?? env('MIDTRANS_CLIENT_KEY') }}"></script>
<script type="text/javascript">
    var payButton = document.getElementById('pay-button');
    if (payButton) {
        payButton.addEventListener('click', function () {
            window.snap.pay('{{ $transaction->snap_token }}', {
                onSuccess: function(result){
                    alert("SYSTEM_NOTIFICATION: Pembayaran Berhasil! Sistem telah memverifikasi data."); 
                    
                    
                    window.location.href = "/transactions/" + "{{ $transaction->id }}" + "/success-callback";
                },
                onPending: function(result){
                    alert("SYSTEM_NOTIFICATION: Menunggu Anda menyelesaikan transaksi pembayaran di gerbang pembayaran."); 
                    window.location.reload();
                },
                onError: function(result){
                    alert("SYSTEM_WARNING: Pembayaran gagal atau dibatalkan! Transaksi digagalkan.");
                    window.location.reload();
                }
            });
        });
    }
</script>
@endsection