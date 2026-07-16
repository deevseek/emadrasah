<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kuitansi {{ $payment->payment_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 8px; padding: 16px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 8px; padding: 10px 16px; background: #047857; color: #fff; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar a { background: #475569; }
        .receipt { width: min(760px, calc(100% - 32px)); margin: 0 auto 32px; background: #fff; padding: 40px; box-shadow: 0 10px 30px rgba(15, 23, 42, .12); }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #065f46; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #064e3b; font-size: 26px; }
        .header p { margin: 5px 0 0; color: #475569; }
        .number { text-align: right; }
        .number strong { display: block; margin-top: 5px; font-size: 18px; }
        .meta { display: grid; grid-template-columns: 150px 1fr; gap: 8px 16px; margin: 24px 0; font-size: 14px; }
        .meta dt { color: #64748b; }
        .meta dd { margin: 0; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .amount { text-align: right; white-space: nowrap; }
        .total { margin-top: 20px; display: flex; justify-content: space-between; border-radius: 10px; background: #ecfdf5; padding: 16px; color: #065f46; font-size: 20px; font-weight: 800; }
        .footer { display: grid; grid-template-columns: 1fr 220px; gap: 32px; margin-top: 42px; font-size: 13px; }
        .signature { text-align: center; }
        .signature-space { height: 72px; }
        .cancelled { margin-top: 20px; border: 2px solid #e11d48; padding: 12px; color: #9f1239; font-weight: 700; text-align: center; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .receipt { width: 100%; margin: 0; padding: 20px; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Cetak Kuitansi</button>
        <a href="{{ route('finance.student-payments.show', $payment) }}">Kembali</a>
    </div>

    <main class="receipt">
        <header class="header">
            <div>
                <h1>MI Muslimat NU Demak</h1>
                <p>Kuitansi Pembayaran Siswa</p>
            </div>
            <div class="number">
                <span>Nomor Kuitansi</span>
                <strong>{{ $payment->payment_number }}</strong>
            </div>
        </header>

        <dl class="meta">
            <dt>Nama Siswa</dt>
            <dd>{{ $payment->student?->name }}</dd>
            <dt>Tanggal Pembayaran</dt>
            <dd>{{ $payment->payment_date?->format('d/m/Y') }}</dd>
            <dt>Metode</dt>
            <dd>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</dd>
            <dt>Nomor Referensi</dt>
            <dd>{{ $payment->reference_number ?? '-' }}</dd>
        </dl>

        <table>
            <thead>
                <tr>
                    <th>Nomor Tagihan</th>
                    <th>Jenis Tagihan</th>
                    <th class="amount">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payment->allocations as $allocation)
                    <tr>
                        <td>{{ $allocation->invoice?->invoice_number }}</td>
                        <td>{{ $allocation->invoice?->feeType?->name }}</td>
                        <td class="amount">Rp {{ number_format((float) $allocation->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            <span>Total Diterima</span>
            <span>Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</span>
        </div>

        @if ($payment->notes)
            <p style="margin-top: 20px; white-space: pre-line; color: #475569; font-size: 13px;">
                Catatan: {{ $payment->notes }}
            </p>
        @endif

        @if ($payment->status === 'cancelled')
            <div class="cancelled">
                PEMBAYARAN DIBATALKAN<br>
                <small>{{ $payment->cancellation_reason }}</small>
            </div>
        @endif

        <footer class="footer">
            <div>
                <p>Kuitansi ini dibuat oleh sistem dan tercatat dalam jurnal keuangan madrasah.</p>
                <p>Status: <strong>{{ str($payment->status)->title() }}</strong></p>
            </div>
            <div class="signature">
                <p>Petugas Penerima</p>
                <div class="signature-space"></div>
                <strong>{{ $payment->receiver?->name ?? '-' }}</strong>
            </div>
        </footer>
    </main>
</body>
</html>
