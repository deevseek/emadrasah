<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Gaji {{ $payroll->employee?->name }} - {{ $payroll->period?->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 8px; padding: 16px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 8px; padding: 10px 16px; background: #047857; color: #fff; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar a { background: #475569; }
        .slip { width: min(820px, calc(100% - 32px)); margin: 0 auto 32px; background: #fff; padding: 40px; box-shadow: 0 10px 30px rgba(15, 23, 42, .12); }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #065f46; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #064e3b; font-size: 26px; }
        .header p { margin: 5px 0 0; color: #475569; }
        .period { text-align: right; }
        .period strong { display: block; margin-top: 5px; font-size: 18px; }
        .meta { display: grid; grid-template-columns: 160px 1fr 160px 1fr; gap: 8px 16px; margin: 24px 0; font-size: 14px; }
        .meta dt { color: #64748b; }
        .meta dd { margin: 0; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 11px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .amount { text-align: right; white-space: nowrap; }
        .summary { margin-top: 20px; margin-left: auto; width: min(100%, 400px); }
        .summary div { display: flex; justify-content: space-between; gap: 20px; padding: 8px 0; }
        .summary .net { border-top: 2px solid #065f46; margin-top: 5px; padding-top: 14px; color: #065f46; font-size: 19px; font-weight: 800; }
        .attendance { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 22px 0; }
        .attendance div { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; text-align: center; }
        .attendance span { display: block; color: #64748b; font-size: 11px; text-transform: uppercase; }
        .attendance strong { display: block; margin-top: 5px; font-size: 18px; }
        .footer { display: grid; grid-template-columns: 1fr 220px; gap: 32px; margin-top: 42px; font-size: 13px; }
        .signature { text-align: center; }
        .signature-space { height: 72px; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .slip { width: 100%; margin: 0; padding: 20px; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Cetak Slip</button>
        <a href="{{ route('finance.payrolls.show', $payroll) }}">Kembali</a>
    </div>

    <main class="slip">
        <header class="header">
            <div>
                <h1>MI Muslimat NU Demak</h1>
                <p>Slip Gaji Pegawai</p>
            </div>
            <div class="period">
                <span>Periode</span>
                <strong>{{ $payroll->period?->name }}</strong>
            </div>
        </header>

        <dl class="meta">
            <dt>Nama Pegawai</dt>
            <dd>{{ $payroll->employee?->name }}</dd>
            <dt>Nomor Pegawai</dt>
            <dd>{{ $payroll->employee?->employee_number ?? '-' }}</dd>
            <dt>Rentang Periode</dt>
            <dd>{{ $payroll->period?->starts_on?->format('d/m/Y') }} - {{ $payroll->period?->ends_on?->format('d/m/Y') }}</dd>
            <dt>Status</dt>
            <dd>{{ str($payroll->status)->title() }}</dd>
        </dl>

        <div class="attendance">
            <div><span>Hadir</span><strong>{{ $payroll->attendance_present }}</strong></div>
            <div><span>Terlambat</span><strong>{{ $payroll->attendance_late }}</strong></div>
            <div><span>Izin</span><strong>{{ $payroll->attendance_permission }}</strong></div>
            <div><span>Sakit</span><strong>{{ $payroll->attendance_sick }}</strong></div>
            <div><span>Alpha</span><strong>{{ $payroll->attendance_alpha }}</strong></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Komponen</th>
                    <th>Jenis</th>
                    <th class="amount">Kuantitas</th>
                    <th class="amount">Tarif</th>
                    <th class="amount">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payroll->items as $item)
                    <tr>
                        <td>{{ $item->component_name_snapshot }}</td>
                        <td>{{ $item->component_type === 'earning' ? 'Pendapatan' : 'Potongan' }}</td>
                        <td class="amount">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                        <td class="amount">
                            @if ($item->notes === 'Persentase dari gaji pokok')
                                {{ number_format((float) $item->rate, 2, ',', '.') }} %
                            @else
                                Rp {{ number_format((float) $item->rate, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="amount">Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div><span>Total Pendapatan</span><strong>Rp {{ number_format((float) $payroll->total_earnings, 0, ',', '.') }}</strong></div>
            <div><span>Total Potongan</span><strong>Rp {{ number_format((float) $payroll->total_deductions, 0, ',', '.') }}</strong></div>
            <div class="net"><span>Gaji Bersih</span><strong>Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}</strong></div>
        </div>

        <footer class="footer">
            <div>
                <p>Slip ini dihasilkan oleh Sistem Informasi Manajemen MI Muslimat NU Demak.</p>
                @if ($payroll->reference_number)
                    <p>Referensi pembayaran: <strong>{{ $payroll->reference_number }}</strong></p>
                @endif
            </div>
            <div class="signature">
                <p>Bendahara</p>
                <div class="signature-space"></div>
                <strong>{{ $payroll->approver?->name ?? '-' }}</strong>
            </div>
        </footer>
    </main>
</body>
</html>
