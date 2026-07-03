@extends('layouts.app')

@section('content')
    <h3>Riwayat Transaksi Toko Hot Wheels</h3>
    <p><a href="{{ route('landing') }}">&lt;&lt; Kembali ke Etalase Depan</a></p>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr bgcolor="#cccccc">
                <th>No</th>
                <th>Tanggal</th>
                <th>Kode Transaksi</th>
                <th>Nama Pembeli</th>
                <th>Total Bayar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $trx->created_at->format('d M Y H:i') }}</td>
                    <td><code>{{ $trx->transaction_code }}</code></td>
                    <td><b>{{ $trx->user->name ?? 'Guest' }}</b></td>
                    <td>Rp {{ number_format($trx->total_price, 0, ',', '.') }}</td>
                    <td>
                        <a href="{{ route('transactions.show', $trx->id) }}">Lihat Nota</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" align="center">Belum ada riwayat transaksi masuk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection