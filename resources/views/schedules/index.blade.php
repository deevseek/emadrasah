<x-app-layout title="Jadwal Pelajaran">
    <div class="space-y-6">
        <x-ui.page-header title="Jadwal Pelajaran" description="Kelola jadwal pelajaran setiap kelas dan guru." />

        <section aria-label="Aksi jadwal" class="flex flex-wrap gap-3">
            @can('schedules.import')
                <x-ui.button variant="outline" :href="route('schedules.import')">Impor Jadwal</x-ui.button>
            @endcan
            @can('schedules.create')
                <x-ui.button :href="route('schedules.create')">Tambah Jadwal</x-ui.button>
            @endcan
            @can('schedules.export')
                <x-ui.button variant="outline" :href="route('schedules.export')">Unduh CSV</x-ui.button>
            @endcan
        </section>

        @can('schedules.print')
            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.card>
                    <h2 class="text-lg font-bold text-emerald-950">Template Jadwal</h2>
                    <p class="mt-1 text-sm text-slate-600">Unggah format Word yang biasa digunakan oleh madrasah. Format ini akan digunakan saat jadwal diunduh.</p>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                        @if($scheduleTemplatePath)
                            <p><span class="font-semibold text-slate-900">Template aktif:</span> {{ basename($scheduleTemplatePath) }}</p>
                            <p class="mt-1">Terakhir diperbarui {{ $scheduleTemplateSetting->updated_at?->translatedFormat('d F Y') }} pukul {{ $scheduleTemplateSetting->updated_at?->format('H.i') }}</p>
                        @else
                            <p class="font-semibold text-slate-900">Belum ada template aktif.</p>
                            <p class="mt-1">Unggah file Word untuk mulai mengunduh jadwal.</p>
                        @endif
                    </div>

                    <form class="mt-4 space-y-3" method="POST" action="{{ route('schedules.templates.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label for="schedule-template" class="mb-1 block text-sm font-semibold text-slate-700">Pilih file Word (.docx)</label>
                            <input id="schedule-template" required type="file" name="template" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm">
                        </div>
                        <x-ui.button>Unggah Template Baru</x-ui.button>
                    </form>

                    @if($scheduleTemplatePath)
                        <div class="mt-3">
                            <x-ui.button variant="outline" :href="route('schedules.templates.download')">Unduh Template Saat Ini</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h2 class="text-lg font-bold text-emerald-950">Unduh Jadwal Word</h2>
                    <p class="mt-1 text-sm text-slate-600">Gunakan menu ini untuk membuat jadwal sesuai template Word madrasah.</p>
                    <p class="mt-1 text-sm text-slate-600">Pilih tahun ajaran, semester, tanggal dokumen, dan kelas yang akan dimasukkan.</p>

                    <form class="mt-4 grid gap-4 sm:grid-cols-2" method="GET" action="{{ route('schedules.export-word') }}">
                        <div>
                            <label for="word-academic-year" class="mb-1 block text-sm font-semibold text-slate-700">Tahun Ajaran</label>
                            <select id="word-academic-year" required name="tahun_ajaran_id" class="w-full rounded-xl border-slate-300">
                                <option value="">Pilih Tahun Ajaran</option>
                                @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('tahun_ajaran_id') == $year->id)>{{ $year->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="word-semester" class="mb-1 block text-sm font-semibold text-slate-700">Semester</label>
                            <select id="word-semester" required name="semester_id" class="w-full rounded-xl border-slate-300">
                                <option value="">Pilih Semester</option>
                                @foreach($semesters as $semester)<option value="{{ $semester->id }}" @selected(old('semester_id') == $semester->id)>{{ $semester->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="document-date" class="mb-1 block text-sm font-semibold text-slate-700">Tanggal Dokumen</label>
                            <input id="document-date" required type="date" name="tanggal_dokumen" value="{{ old('tanggal_dokumen', now()->toDateString()) }}" class="w-full rounded-xl border-slate-300">
                        </div>
                        <div>
                            <label for="word-classroom" class="mb-1 block text-sm font-semibold text-slate-700">Kelas</label>
                            <select id="word-classroom" name="kelas" class="w-full rounded-xl border-slate-300">
                                <option value="">Semua Kelas</option>
                                @foreach($classrooms->whereIn('code', \App\Services\Academic\LessonScheduleTemplateService::CLASS_CODES) as $class)<option value="{{ $class->code }}" @selected(old('kelas') === $class->code)>{{ $class->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <x-ui.button>Unduh Jadwal Word</x-ui.button>
                            <p class="mt-2 text-sm text-slate-500">Dokumen akan dibuat dari jadwal yang tersimpan di aplikasi.</p>
                        </div>
                    </form>
                </x-ui.card>
            </div>
        @endcan

        <x-ui.card>
            <h2 class="text-lg font-bold text-emerald-950">Cari Jadwal</h2>
            <form class="mt-4 grid gap-4 md:grid-cols-3 xl:grid-cols-6" method="GET">
                <div><label for="filter-semester" class="mb-1 block text-sm font-semibold text-slate-700">Semester</label><select id="filter-semester" name="semester_id" class="w-full rounded-xl border-slate-300"><option value="">Semua Semester</option>@foreach($semesters as $semester)<option value="{{ $semester->id }}" @selected(request('semester_id') == $semester->id)>{{ $semester->name }}</option>@endforeach</select></div>
                <div><label for="filter-classroom" class="mb-1 block text-sm font-semibold text-slate-700">Kelas</label><select id="filter-classroom" name="classroom_id" class="w-full rounded-xl border-slate-300"><option value="">Semua Kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                <div><label for="filter-employee" class="mb-1 block text-sm font-semibold text-slate-700">Guru</label><select id="filter-employee" name="employee_id" class="w-full rounded-xl border-slate-300"><option value="">Semua Guru</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
                <div><label for="filter-day" class="mb-1 block text-sm font-semibold text-slate-700">Hari</label><select id="filter-day" name="day_of_week" class="w-full rounded-xl border-slate-300"><option value="">Semua Hari</option>@foreach($days as $day)<option value="{{ $day->value }}" @selected(request('day_of_week') === $day->value)>{{ $day->label() }}</option>@endforeach</select></div>
                <div><label for="filter-entry-type" class="mb-1 block text-sm font-semibold text-slate-700">Jenis Kegiatan</label><select id="filter-entry-type" name="entry_type" class="w-full rounded-xl border-slate-300"><option value="">Semua Jenis</option><option value="lesson" @selected(request('entry_type') === 'lesson')>Pelajaran</option><option value="activity" @selected(request('entry_type') === 'activity')>Kegiatan Madrasah</option><option value="break" @selected(request('entry_type') === 'break')>Istirahat</option></select></div>
                <div class="flex items-end"><x-ui.button class="w-full">Tampilkan</x-ui.button></div>
            </form>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-bold text-emerald-950">Jadwal Mingguan</h2>
            @php($hasScheduleFilters = request()->hasAny(['semester_id', 'classroom_id', 'employee_id', 'day_of_week', 'entry_type']))
            @if($weekly->flatten()->isEmpty())
                <div class="mt-4">
                    @if($hasScheduleFilters)
                        <x-ui.empty-state title="Belum ada jadwal untuk pilihan tersebut." />
                    @else
                        <x-ui.empty-state title="Belum ada jadwal yang ditampilkan." description="Pilih semester, kelas, atau guru, kemudian tekan Tampilkan." />
                    @endif
                </div>
            @else
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @foreach($days as $day)
                        @if(($weekly[$day->value] ?? collect())->isNotEmpty())
                            <section class="rounded-xl border border-slate-200 p-4">
                                <h3 class="font-bold text-emerald-950">{{ $day->label() }}</h3>
                                <div class="mt-3 space-y-3">
                                    @foreach($weekly[$day->value] as $schedule)
                                        <div class="border-l-4 border-emerald-700 pl-3 text-sm">
                                            <p class="font-semibold text-slate-900">{{ substr($schedule->starts_at, 0, 5) }}–{{ substr($schedule->ends_at, 0, 5) }} · {{ $schedule->classroom?->name }}</p>
                                            <p class="text-slate-600">{{ $schedule->entry_type?->value === 'lesson' ? $schedule->subject?->name : $schedule->activity_name }}@if($schedule->entry_type?->value === 'lesson' && $schedule->employee?->name) · {{ $schedule->employee->name }}@endif</p>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-ui.table :headers="['Hari', 'Jam', 'Kelas', 'Mata Pelajaran/Kegiatan', 'Guru', 'Ruangan', 'Tindakan']">
                @forelse($schedules as $schedule)
                    <tr><td>{{ $schedule->day_of_week?->label() }}</td><td>{{ substr($schedule->starts_at, 0, 5) }}–{{ substr($schedule->ends_at, 0, 5) }}</td><td>{{ $schedule->classroom?->name }}</td><td>{{ $schedule->entry_type?->value === 'lesson' ? $schedule->subject?->name : $schedule->activity_name }}</td><td>{{ $schedule->entry_type?->value === 'lesson' ? $schedule->employee?->name : '' }}</td><td>{{ $schedule->room }}</td><td><x-ui.button variant="secondary" :href="route('schedules.show', $schedule)">Detail</x-ui.button> @can('schedules.update')<x-ui.button variant="outline" :href="route('schedules.edit', $schedule)">Edit</x-ui.button>@endcan</td></tr>
                @empty
                    <tr><td colspan="7"><x-ui.empty-state title="Belum ada jadwal." /></td></tr>
                @endforelse
            </x-ui.table>
            <div class="mt-4">{{ $schedules->links() }}</div>
        </x-ui.card>
    </div>
</x-app-layout>
