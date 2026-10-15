<x-app-layout title="Laporan Akademik">
    <div class="index-stack print:space-y-3">
        <div class="print:hidden">
            <x-ui.page-header
                title="Laporan Akademik"
                description="Pantau kehadiran, capaian nilai, dan kelengkapan jurnal mengajar dalam satu laporan."
            >
                <button onclick="window.print()" class="btn btn-secondary" type="button">Cetak / Simpan PDF</button>
            </x-ui.page-header>
        </div>

        <form method="GET" action="{{ route('academic.reports.attendance.export') }}" class="card card-body grid gap-4 print:hidden md:grid-cols-[1fr_1fr_auto]">
            <h2 class="font-bold text-emerald-950 md:col-span-3">Unduh Laporan Absensi Siswa</h2>
            <label><span class="label">Rombel laporan</span><select class="input mt-1" name="classroom_id" required><option value="">Pilih rombel</option>@foreach($rooms as $item)<option value="{{ $item->id }}" @selected($room?->id == $item->id)>{{ $item->display_name }}</option>@endforeach</select></label>
            <label><span class="label">Bulan laporan</span><input class="input mt-1" type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" required></label>
            <button class="btn btn-primary self-end" type="submit">Unduh Laporan XLSX</button>
        </form>

        <form method="GET" action="{{ route('academic.reports.index') }}" class="card card-body print:hidden">
            <div class="mb-5 flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-emerald-950">Filter Laporan</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih periode dan rombel untuk memperbarui seluruh rekap di bawah.</p>
                </div>
                <a class="text-sm font-semibold text-emerald-700 hover:text-emerald-900" href="{{ route('academic.reports.index') }}">Atur ulang</a>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label><span class="label">Tahun Ajaran</span><select class="input mt-1" name="academic_year_id" onchange="this.form.submit()">@foreach($years as $item)<option value="{{ $item->id }}" @selected($year == $item->id)>{{ $item->name }}</option>@endforeach</select></label>
                <label><span class="label">Semester</span><select class="input mt-1" name="semester_id">@foreach($semesters as $item)<option value="{{ $item->id }}" @selected($semester == $item->id)>{{ $item->display_name }}</option>@endforeach</select></label>
                <label><span class="label">Rombel</span><select class="input mt-1" name="classroom_id"><option value="">Semua rombel</option>@foreach($rooms as $item)<option value="{{ $item->id }}" @selected($room?->id == $item->id)>{{ $item->display_name }}</option>@endforeach</select></label>
                <label><span class="label">Siswa</span><select class="input mt-1" name="student_id" @disabled(!$room)><option value="">Rekap seluruh siswa</option>@foreach($students as $item)<option value="{{ $item->id }}" @selected($studentId == $item->id)>{{ $item->full_name }}</option>@endforeach</select></label>
                <label><span class="label">Mulai Periode Jurnal</span><input class="input mt-1" type="date" name="from_date" value="{{ $from }}"></label>
                <label><span class="label">Sampai Periode Jurnal</span><input class="input mt-1" type="date" name="to_date" value="{{ $to }}"></label>
                <div class="flex items-end md:col-span-2"><button class="btn btn-primary w-full" type="submit">Tampilkan Laporan</button></div>
            </div>
        </form>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan jurnal mengajar">
            @foreach([
                ['label' => 'Jumlah Jurnal', 'value' => $teachingRecap->count(), 'hint' => 'Pada periode terpilih'],
                ['label' => 'Guru Mengisi', 'value' => $teachingRecap->unique('personnel_id')->count(), 'hint' => 'Ustaz dan ustazah'],
                ['label' => 'Rombel Tercatat', 'value' => $teachingRecap->unique('classroom_id')->count(), 'hint' => $room?->display_name ?? 'Seluruh rombel'],
                ['label' => 'Mata Pelajaran', 'value' => $teachingRecap->unique('academic_subject_id')->count(), 'hint' => 'Memiliki jurnal'],
            ] as $stat)
                <article class="card card-body border-l-4 border-l-emerald-700">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                    <strong class="mt-2 block text-3xl font-black text-emerald-950">{{ number_format($stat['value'], 0, ',', '.') }}</strong>
                    <p class="mt-1 text-xs text-slate-500">{{ $stat['hint'] }}</p>
                </article>
            @endforeach
        </section>

        @if(!$room)
            <x-ui.empty-state
                icon="▦"
                title="Pilih rombel untuk melihat laporan siswa"
                description="Rekap jurnal mengajar tetap tersedia di bawah. Pilih rombel pada filter untuk membuka ringkasan kehadiran dan nilai siswa."
            />
        @elseif(!$studentId)
            <section>
                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div><h2 class="text-lg font-bold text-emerald-950">Rekap Siswa</h2><p class="text-sm text-slate-500">{{ $room->display_name }} · {{ $semesters->firstWhere('id', $semester)?->display_name }}</p></div>
                    <span class="text-sm font-semibold text-slate-600">{{ $students->count() }} siswa</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table min-w-[900px]">
                        <thead><tr><th>Nama Siswa</th><th class="text-center">Hadir</th><th class="text-center">Sakit</th><th class="text-center">Izin</th><th class="text-center">Alpa</th><th class="text-center">Kehadiran</th><th class="text-center">Rata-rata</th><th>Status Data</th></tr></thead>
                        <tbody>
                        @forelse($students as $student)
                            @php($studentAttendance = $attendance->get($student->id, collect()))
                            @php($studentGrades = $grades->get($student->id, collect())->whereNotNull('score'))
                            @php($presence = $studentAttendance->where('status.value', 'present')->count())
                            <tr>
                                <td><strong class="text-slate-900">{{ $student->full_name }}</strong><span class="mt-1 block text-xs text-slate-500">{{ $student->nisn ?: 'NISN belum tersedia' }}</span></td>
                                <td class="text-center font-semibold text-emerald-700">{{ $presence }}</td><td class="text-center">{{ $studentAttendance->where('status.value', 'sick')->count() }}</td><td class="text-center">{{ $studentAttendance->where('status.value', 'permitted')->count() }}</td><td class="text-center">{{ $studentAttendance->where('status.value', 'absent')->count() }}</td>
                                <td class="text-center font-semibold">{{ $studentAttendance->count() ? number_format($presence / $studentAttendance->count() * 100, 1, ',', '.').'%' : '—' }}</td>
                                <td class="text-center font-semibold">{{ $studentGrades->count() ? number_format($studentGrades->avg('score'), 2, ',', '.') : '—' }}</td>
                                <td><span @class(['badge', 'badge-success' => $studentAttendance->isNotEmpty() || $studentGrades->isNotEmpty(), 'badge-warning' => $studentAttendance->isEmpty() && $studentGrades->isEmpty()])>{{ $studentAttendance->isNotEmpty() || $studentGrades->isNotEmpty() ? 'Tersedia' : 'Belum lengkap' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-10 text-center text-slate-500">Belum ada siswa aktif pada rombel ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            @php($selectedStudent = $students->firstWhere('id', $studentId))
            @php($studentAttendance = $attendance->get($studentId, collect()))
            @php($studentGrades = $grades->get($studentId, collect())->whereNotNull('score'))
            <section class="card card-body print:shadow-none">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Laporan Akademik Siswa</p><h1 class="mt-1 text-2xl font-black text-emerald-950">{{ $selectedStudent->full_name }}</h1><p class="mt-2 text-sm text-slate-600">{{ $selectedStudent->nisn ?? 'NISN belum tersedia' }} · {{ $room->display_name }}</p><p class="text-sm text-slate-500">{{ $room->academicYear->name }} · {{ $semesters->firstWhere('id', $semester)?->display_name }} · Wali kelas: {{ $room->homeroom_name }}</p></div>
                    <button onclick="window.print()" class="btn btn-primary print:hidden" type="button">Cetak Laporan</button>
                </div>
                <h2 class="mt-6 font-bold text-emerald-950">Ringkasan Kehadiran</h2>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">@foreach(['present'=>'Hadir','sick'=>'Sakit','permitted'=>'Izin','absent'=>'Alpa'] as $key=>$label)<div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><small class="text-slate-500">{{ $label }}</small><strong class="mt-1 block text-xl text-emerald-950">{{ $studentAttendance->where('status.value', $key)->count() }}</strong></div>@endforeach<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3"><small class="text-emerald-700">Persentase</small><strong class="mt-1 block text-xl text-emerald-950">{{ $studentAttendance->count() ? number_format($studentAttendance->where('status.value', 'present')->count() / $studentAttendance->count() * 100, 1, ',', '.').'%' : '—' }}</strong></div></div>
                <h2 class="mt-7 font-bold text-emerald-950">Capaian Nilai</h2>
                <div class="table-wrap mt-3 shadow-none"><table class="data-table"><thead><tr><th>Mata Pelajaran</th><th>Nilai</th><th>Predikat</th></tr></thead><tbody>@forelse($studentGrades as $row)<tr><td>{{ $row->subject->name }}</td><td class="font-bold">{{ $row->score }}</td><td>{{ $row->predicate }}</td></tr>@empty<tr><td colspan="3" class="py-8 text-center text-slate-500">Belum ada nilai untuk siswa ini.</td></tr>@endforelse</tbody></table></div>
                <p class="mt-4 text-right font-bold text-emerald-950">Rata-rata Nilai: {{ $studentGrades->count() ? number_format($studentGrades->avg('score'), 2, ',', '.') : '—' }}</p>
            </section>
        @endif

        <section class="space-y-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between"><div><h2 class="text-lg font-bold text-emerald-950">Rekap Jurnal Mengajar</h2><p class="text-sm text-slate-500">Periode {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }}–{{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div><span class="badge badge-info">{{ $room?->display_name ?? 'Seluruh rombel' }}</span></div>
            <div class="grid gap-4 xl:grid-cols-5">
                <article class="xl:col-span-3"><div class="mb-2"><h3 class="font-bold text-slate-800">Rekap per Guru</h3><p class="text-sm text-slate-500">Produktivitas pengisian jurnal pada periode terpilih.</p></div><div class="table-wrap"><table class="data-table min-w-[680px]"><thead><tr><th>Guru</th><th class="text-center">Jurnal</th><th class="text-center">Rombel</th><th class="text-center">Mapel</th><th>Pertemuan Terakhir</th></tr></thead><tbody>@forelse($teacherRecap as $row)<tr><td class="font-semibold text-slate-900">{{ $row['teacher'] }}</td><td class="text-center font-bold text-emerald-700">{{ $row['journals'] }}</td><td class="text-center">{{ $row['classrooms'] }}</td><td class="text-center">{{ $row['subjects'] }}</td><td>{{ $row['last_meeting']->translatedFormat('d F Y') }}</td></tr>@empty<tr><td colspan="5" class="py-10 text-center text-slate-500">Belum ada jurnal pada periode ini.</td></tr>@endforelse</tbody></table></div></article>
                <article class="xl:col-span-2"><div class="mb-2"><h3 class="font-bold text-slate-800">Rekap per Rombel</h3><p class="text-sm text-slate-500">Sebaran jurnal pada setiap rombongan belajar.</p></div><div class="table-wrap"><table class="data-table min-w-[500px]"><thead><tr><th>Rombel</th><th class="text-center">Jurnal</th><th class="text-center">Guru</th><th>Terakhir</th></tr></thead><tbody>@forelse($classroomRecap as $row)<tr><td class="font-semibold text-slate-900">{{ $row['classroom'] }}</td><td class="text-center font-bold text-emerald-700">{{ $row['journals'] }}</td><td class="text-center">{{ $row['teachers'] }}</td><td>{{ $row['last_meeting']->translatedFormat('d M Y') }}</td></tr>@empty<tr><td colspan="4" class="py-10 text-center text-slate-500">Belum ada jurnal pada periode ini.</td></tr>@endforelse</tbody></table></div></article>
            </div>
        </section>
    </div>
    <style>@media print{@page{size:A4;margin:15mm}nav,aside,header{display:none!important}body{background:white}.table-wrap{box-shadow:none!important}}</style>
</x-app-layout>
