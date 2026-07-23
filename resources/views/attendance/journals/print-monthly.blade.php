<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>{{ $type === 'class' ? 'Jurnal Kelas' : 'Jurnal Guru' }} {{ $month->translatedFormat('F Y') }}</title>
  <style>
    @page { size: A4 landscape; margin: 10mm; }
    body { color: #111827; font-family: "Times New Roman", serif; font-size: 12px; }
    .no-print { margin-bottom: 12px; }
    .cover { align-items: center; display: flex; flex-direction: column; justify-content: center; min-height: 170mm; text-align: center; }
    .cover h1, .cover h2, .cover h3 { margin: 10px 0; text-transform: uppercase; }
    .seal { align-items: center; border: 4px solid #eab308; border-radius: 50%; color: #fff; display: flex; height: 120px; justify-content: center; margin: 40px 0; width: 120px; background: #047857; font-weight: 700; }
    .identity { border: 1px solid #111827; border-radius: 20px; margin-top: 24px; padding: 22px; text-align: left; width: 430px; }
    .identity div { display: grid; grid-template-columns: 170px 1fr; font-size: 18px; font-weight: 700; margin: 10px 0; }
    .page-break { page-break-after: always; }
    .letterhead { align-items: center; display: grid; grid-template-columns: 260px 1fr; margin: 0 auto 8px; max-width: 1040px; }
    .wave { background: linear-gradient(135deg,#047857 0%,#047857 65%,#fff 66%); border-bottom-left-radius: 60px; color:#fff; height:78px; padding:18px 0 0 46px; }
    .small-seal { align-items:center; background:#10b981; border:2px solid #eab308; border-radius:50%; display:flex; font-size:10px; height:58px; justify-content:center; width:58px; }
    .school { line-height: 1.1; text-align:center; }
    .school h1 { color:#047857; font-size:24px; margin:0; text-transform:uppercase; }
    .school h2 { color:#047857; font-size:20px; margin:0; text-transform:uppercase; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #111827; padding: 4px; vertical-align: middle; }
    th { font-size: 12px; font-weight: 700; text-align: center; }
    td { height: 28px; }
    .center { text-align:center; }
    .signatures { display:grid; grid-template-columns:1fr 1fr; gap:90px; margin:16px 90px 0; }
    .signature-name { font-weight:700; margin-top:52px; text-decoration: underline; }
    @media print { .no-print { display:none; } }
  </style>
</head>
<body onload="window.print()">
  <button class="no-print" onclick="window.print()">Cetak</button>
  <section class="cover page-break">
    <h2>Jurnal Mengajar</h2>
    <h1>{{ $profile->school_name }}</h1>
    <h2>Tahun Ajaran {{ $academicYear?->name ?? '-' }}</h2>
    <div class="seal">Logo<br>Madrasah</div>
    <div class="identity">
      <div><span>KELAS</span><span>: {{ $selectedClassroom?->name ?? '.........................' }}</span></div>
      <div><span>{{ $type === 'class' ? 'NAMA WALI KELAS' : 'NAMA GURU MAPEL' }}</span><span>: {{ ($type === 'class' ? $selectedClassroom?->homeroomTeacher?->fullName() : $selectedEmployee?->fullName()) ?? '.........................' }}</span></div>
      <div><span>BULAN</span><span>: {{ $month->translatedFormat('F Y') }}</span></div>
      <div><span>SEMESTER</span><span>: {{ strtoupper($semester?->name ?? '-') }}</span></div>
    </div>
  </section>

  <section>
    <div class="letterhead">
      <div class="wave"><div class="small-seal">Logo</div></div>
      <div class="school">
        <h2>{{ $profile->foundation_name ?: 'Yayasan' }}</h2>
        <p>NSM: {{ $profile->nsm ?: '-' }} / NPSN: {{ $profile->npsn ?: '-' }}</p>
        <h1>{{ $profile->school_name }}</h1>
        <p>{{ collect([$profile->address, $profile->village, $profile->district, $profile->city])->filter()->join(', ') ?: 'Alamat madrasah belum dilengkapi' }} · Email: {{ $profile->email ?: '-' }}</p>
      </div>
    </div>
    <table>
      <thead>
        <tr><th rowspan="2">No</th><th rowspan="2">Hari/Tanggal</th><th rowspan="2">Jam<br>Ke</th><th rowspan="2">Mapel</th><th rowspan="2">Ustadz / Ustadzah</th><th rowspan="2">Uraian Mengajar</th><th rowspan="2">Metode Pembelajaran</th><th colspan="2">Jumlah Siswa</th><th colspan="3">Siswa tidak<br>Hadir Karena</th><th rowspan="2">Keterangan</th></tr>
        <tr><th>Hadir</th><th>Tdk<br>Hadir</th><th>S</th><th>I</th><th>A</th></tr>
      </thead>
      <tbody>
        @forelse($journals as $journal)
          <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td>{{ $journal->journal_date->translatedFormat('l, d/m/Y') }}</td>
            <td class="center">{{ $journal->meeting_number }}</td>
            <td>{{ $journal->subject?->name }}</td>
            <td>{{ $journal->employee?->fullName() ?? $journal->employee?->name }}</td>
            <td>{{ $journal->learning_topic ?: $journal->learning_material }}</td>
            <td>{{ $journal->learning_method ?: '-' }}</td>
            <td></td><td></td><td></td><td></td><td></td>
            <td>{{ $journal->teacher_notes ?: '-' }}</td>
          </tr>
        @empty
          <tr><td class="center" colspan="13">Belum ada jurnal mengajar pada bulan ini.</td></tr>
        @endforelse
        @for($i = $journals->count(); $i < 10; $i++)
          <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
      </tbody>
    </table>
    <div class="center" style="margin-top:10px;">{{ $profile->city ?: '................' }}, ........................<br>Mengetahui,</div>
    <div class="signatures">
      <div><p>Kepala {{ $profile->school_name }}</p><p class="signature-name">{{ $profile->principal_name ?: '................................' }}</p><p>Ketua Yayasan</p><p class="signature-name">................................</p></div>
      <div><p>{{ $type === 'class' ? 'Wali Kelas' : 'Guru Mata pelajaran' }} .....</p><p class="signature-name">................................</p><p>Kepala Pengawas MI</p><p class="signature-name">................................</p></div>
    </div>
  </section>
</body>
</html>
