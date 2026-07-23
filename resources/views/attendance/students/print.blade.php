<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Cetak Rekap Absensi Siswa Bulanan</title>
  <style>
    @page { size: A4 landscape; margin: 10mm; }
    body { color: #111827; font-family: "Times New Roman", serif; font-size: 11px; }
    .no-print { margin-bottom: 12px; }
    .header { line-height: 1.1; text-align: center; }
    .header h1 { color: #064e3b; font-size: 18px; margin: 0; text-transform: uppercase; }
    .header p { margin: 1px 0; }
    .divider { border-top: 2px solid #064e3b; margin: 6px 0; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #111827; padding: 2px 3px; }
    th { font-weight: 700; text-align: center; }
    .name { font-weight: 700; text-transform: uppercase; white-space: nowrap; }
    .center { text-align: center; }
    .muted { background: #f1f5f9; color: #94a3b8; }
    .signatures { display: grid; grid-template-columns: 1fr 1fr; margin-top: 28px; }
    .signature-block { padding-left: 90px; }
    .signature-name { font-weight: 700; margin-top: 56px; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body onload="window.print()">
  <button class="no-print" onclick="window.print()">Cetak</button>
  <div class="header">
    <p><strong>{{ strtoupper($profile->foundation_name ?: 'Yayasan') }}</strong></p>
    <h1>{{ $profile->school_name }}</h1>
    <p>{{ collect([$profile->address, $profile->village, $profile->district, $profile->city])->filter()->join(', ') ?: 'Alamat madrasah belum dilengkapi' }}</p>
    <div class="divider"></div>
    <p><strong>ABSENSI SISWA BULANAN</strong></p>
    <p><strong>KELAS {{ strtoupper($classroom?->name ?? '-') }}</strong></p>
    <p><strong>TAHUN AJARAN {{ strtoupper($classroom?->academicYear?->name ?? '-') }}</strong></p>
  </div>

  <p><strong>BULAN: {{ strtoupper($month->translatedFormat('F Y')) }}</strong></p>
  <table>
    <thead>
      <tr>
        <th rowspan="2">NO</th>
        <th rowspan="2">NAMA SISWA</th>
        <th rowspan="2">JK<br>L/P</th>
        @foreach($days as $day)
          <th rowspan="2" class="{{ $day > $daysInMonth ? 'muted' : '' }}">{{ $day }}</th>
        @endforeach
        <th colspan="3">Jumlah</th>
        <th rowspan="2">Ket.</th>
      </tr>
      <tr>
        <th>S</th>
        <th>I</th>
        <th>A</th>
      </tr>
    </thead>
    <tbody>
      @forelse($matrixRows as $row)
        <tr>
          <td class="center">{{ $row['number'] }}</td>
          <td class="name">{{ $row['student_name'] }}</td>
          <td class="center">{{ $row['gender_code'] }}</td>
          @foreach($days as $day)
            <td class="center {{ $day > $daysInMonth ? 'muted' : '' }}">{{ $day <= $daysInMonth ? $row['days'][$day] : '' }}</td>
          @endforeach
          <td class="center">{{ $row['summary']['sakit'] }}</td>
          <td class="center">{{ $row['summary']['izin'] }}</td>
          <td class="center">{{ $row['summary']['alpha'] }}</td>
          <td>{{ $row['notes'] }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="38" class="center">Data siswa atau absensi final belum tersedia untuk filter ini.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="signatures">
    <div class="signature-block">
      <p>Mengetahui,</p>
      <p>Kepala Madrasah</p>
      <p class="signature-name">{{ $profile->principal_name ?: '................................' }}</p>
    </div>
    <div class="signature-block">
      <p>{{ $profile->city ?: '................' }}, {{ now()->translatedFormat('d F Y') }}</p>
      <p>Wali Kelas {{ $classroom?->name ?? '-' }}</p>
      <p class="signature-name">{{ $classroom?->homeroomTeacher?->fullName() ?? '................................' }}</p>
    </div>
  </div>
</body>
</html>
