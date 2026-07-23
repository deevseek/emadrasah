<x-app-layout title="Rekap Absensi Siswa Bulanan">
<div class="space-y-6">
  <x-ui.page-header title="Rekap Absensi Siswa Bulanan" description="Laporan matriks bulanan seperti format XLSX: nama siswa, tanggal 1-31, jumlah S/I/A, dan keterangan.">
    <x-slot:secondary>
      @can('student-attendances.export')
        <x-ui.button variant="outline" :href="route('student-attendances.reports.export', request()->query())">Export CSV</x-ui.button>
      @endcan
      @can('student-attendances.print')
        <x-ui.button variant="secondary" :href="route('student-attendances.reports.print', request()->query())">Cetak</x-ui.button>
      @endcan
    </x-slot:secondary>
  </x-ui.page-header>

  <form method="get" class="card">
    <div class="card-body grid gap-3 md:grid-cols-4">
      <label class="text-sm font-semibold text-slate-700">Bulan
        <input type="month" name="month" value="{{ request('month', $month->format('Y-m')) }}" class="mt-1 w-full rounded-xl border-slate-300">
      </label>
      <label class="text-sm font-semibold text-slate-700">Kelas
        <select name="classroom_id" class="mt-1 w-full rounded-xl border-slate-300">
          @foreach($classrooms as $item)
            <option value="{{ $item->id }}" @selected($classroom?->id === $item->id)>{{ $item->name }}</option>
          @endforeach
        </select>
      </label>
      <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
        <p class="font-semibold">Ringkasan bulan ini</p>
        <p>S: {{ $summary['sakit'] }} · I: {{ $summary['izin'] }} · A: {{ $summary['alpha'] }}</p>
      </div>
      <div class="flex items-end">
        <button class="w-full rounded-xl bg-emerald-900 px-4 py-2 font-semibold text-white">Tampilkan Rekap</button>
      </div>
    </div>
  </form>

  <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="min-w-[1180px] font-serif text-slate-950">
      <div class="text-center leading-tight">
        <p class="text-sm font-bold uppercase">{{ $profile->foundation_name ?: 'Yayasan' }}</p>
        <h2 class="text-lg font-black uppercase text-emerald-900">{{ $profile->school_name }}</h2>
        <p class="text-xs">{{ collect([$profile->address, $profile->village, $profile->district, $profile->city])->filter()->join(', ') ?: 'Alamat madrasah belum dilengkapi' }}</p>
        <div class="my-2 border-t-2 border-emerald-900"></div>
        <h3 class="font-black uppercase">Absensi Siswa Bulanan</h3>
        <p class="font-bold uppercase">Kelas {{ $classroom?->name ?? '-' }}</p>
        <p class="font-bold uppercase">Tahun Ajaran {{ $classroom?->academicYear?->name ?? '-' }}</p>
      </div>

      <div class="mt-6 font-bold uppercase">Bulan: {{ $month->translatedFormat('F Y') }}</div>
      <table class="mt-2 w-full border-collapse text-[11px]">
        <thead>
          <tr>
            <th rowspan="2" class="border border-slate-900 px-1 py-1">NO</th>
            <th rowspan="2" class="border border-slate-900 px-2 py-1 text-left">NAMA SISWA</th>
            <th rowspan="2" class="border border-slate-900 px-1 py-1">JK<br>L/P</th>
            @foreach($days as $day)
              <th rowspan="2" @class(['border border-slate-900 px-1 py-1 text-center', 'bg-slate-100 text-slate-400' => $day > $daysInMonth])>{{ $day }}</th>
            @endforeach
            <th colspan="3" class="border border-slate-900 px-1 py-1">Jumlah</th>
            <th rowspan="2" class="border border-slate-900 px-2 py-1">Ket.</th>
          </tr>
          <tr>
            <th class="border border-slate-900 px-1 py-1">S</th>
            <th class="border border-slate-900 px-1 py-1">I</th>
            <th class="border border-slate-900 px-1 py-1">A</th>
          </tr>
        </thead>
        <tbody>
          @forelse($matrixRows as $row)
            <tr>
              <td class="border border-slate-900 px-1 py-1 text-center">{{ $row['number'] }}</td>
              <td class="border border-slate-900 px-2 py-1 font-semibold uppercase">{{ $row['student_name'] }}</td>
              <td class="border border-slate-900 px-1 py-1 text-center">{{ $row['gender_code'] }}</td>
              @foreach($days as $day)
                <td @class(['h-6 border border-slate-900 px-1 py-1 text-center font-bold', 'bg-slate-100 text-slate-400' => $day > $daysInMonth])>{{ $day <= $daysInMonth ? $row['days'][$day] : '' }}</td>
              @endforeach
              <td class="border border-slate-900 px-1 py-1 text-center">{{ $row['summary']['sakit'] }}</td>
              <td class="border border-slate-900 px-1 py-1 text-center">{{ $row['summary']['izin'] }}</td>
              <td class="border border-slate-900 px-1 py-1 text-center">{{ $row['summary']['alpha'] }}</td>
              <td class="border border-slate-900 px-2 py-1 text-xs">{{ $row['notes'] }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="38" class="border border-slate-900 p-4 text-center">Data siswa atau absensi final belum tersedia untuk filter ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="mt-8 grid grid-cols-2 text-sm">
        <div class="pl-48">
          <p>Mengetahui,</p>
          <p>Kepala Madrasah</p>
          <p class="mt-16 font-bold">{{ $profile->principal_name ?: '................................' }}</p>
        </div>
        <div class="pl-48">
          <p>{{ $profile->city ?: '................' }}, {{ now()->translatedFormat('d F Y') }}</p>
          <p>Wali Kelas {{ $classroom?->name ?? '-' }}</p>
          <p class="mt-16 font-bold">{{ $classroom?->homeroomTeacher?->fullName() ?? '................................' }}</p>
        </div>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
