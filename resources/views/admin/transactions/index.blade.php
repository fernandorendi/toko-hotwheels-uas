@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@500;700&display=swap');

    .cyber-box {
        background: rgba(10, 10, 12, 0.85); backdrop-filter: blur(5px);
        border: 1px solid #333; border-top: 3px solid #00ff88; /* Hijau Neon */
        padding: 30px; position: relative;
    }
    .cyber-box::after {
        content: ''; position: absolute; bottom: -3px; right: -3px;
        width: 15px; height: 15px; border-bottom: 2px solid #00ff88; border-right: 2px solid #00ff88;
    }
    .cyber-title {
        font-family: 'Orbitron', sans-serif; color: #00ff88;
        text-shadow: 0 0 10px rgba(0, 255, 136, 0.4); font-weight: 700; letter-spacing: 2px;
    }

    .cyber-btn-outline {
        font-family: 'Orbitron', sans-serif; background-color: transparent; color: #00ff88;
        border: 1px solid #00ff88; padding: 6px 15px; font-size: 11px; font-weight: bold;
        text-transform: uppercase; clip-path: polygon(15% 0, 100% 0, 85% 100%, 0% 100%);
        transition: all 0.2s; text-decoration: none; display: inline-block;
    }
    .cyber-btn-outline:hover { background-color: #00ff88; color: #000; box-shadow: 0 0 10px #00ff88; }

    .cyber-table { color: #e0e0e0; margin-bottom: 0; --bs-table-bg: transparent !important; width: 100%; border-collapse: collapse; }
    .cyber-table thead th {
        background-color: #111 !important; color: #00ff88; border-bottom: 2px solid #333;
        font-family: 'Orbitron', sans-serif; font-size: 11px; letter-spacing: 1px; padding: 15px 10px;
    }
    .cyber-table tbody tr { background-color: transparent !important; border-bottom: 1px solid #222; transition: background-color 0.2s; }
    .cyber-table tbody tr:hover { background-color: rgba(0, 255, 136, 0.1) !important; }
    .cyber-table td { background-color: transparent !important; vertical-align: middle; padding: 15px 10px; border: none; font-family: 'Rajdhani', sans-serif; }
</style>

<div class="container py-4">
    <div class="cyber-box shadow-lg mb-5">
        
        <div class="mb-4 border-bottom border-secondary pb-3">
            <h3 class="cyber-title m-0">LOG TRANSAKSI</h3>
            <p class="small m-0 font-monospace" style="color: #00ff88;">>> FINANCIAL_DATA_RECORDS</p>
        </div>

        <div class="table-responsive">
            <table class="table cyber-table text-center m-0">
                <thead>
                    <tr>
                        <th width="5%">NO</th>
                        <th>WAKTU_TRANSAKSI</th>
                        <th class="text-start">KODE_INVOICE</th>
                        <th class="text-start">ENTITAS_PELANGGAN</th>
                        <th>TOTAL_BIAYA (IDR)</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $trx)
                        <tr>
                            <td class="text-secondary font-monospace">{{ $index + 1 }}</td>
                            <td class="font-monospace text-secondary">{{ $trx->created_at->format('d M Y H:i') }}</td>
                            <td class="text-start">
                                <span class="badge bg-dark border font-monospace px-2 py-1" style="color: #00ff88; border-color: #00ff88 !important;">
                                    {{ $trx->transaction_code }}
                                </span>
                            </td>
                            <td class="text-start text-white fw-bold" style="font-size: 15px;">{{ $trx->user->name ?? 'GUEST_ENTITY' }}</td>
                            <td class="text-warning fw-bold font-monospace">
                                Rp {{ number_format($trx->total_price, 0, ',', '.') }}
                            </td>
                            <td>
                                <a href="{{ route('transactions.show', $trx->id) }}" class="cyber-btn-outline">
                                    LIHAT_NOTA
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <span class="text-danger fw-bold" style="font-family: 'Orbitron', sans-serif; font-size: 18px;">ERROR 404: RECORD_EMPTY</span>
                                <p class="text-secondary small mt-2">Belum ada log transaksi yang tercatat di dalam sistem.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
    </div>
</div>
@endsection