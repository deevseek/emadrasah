<x-app-layout :title="$journal->exists ? 'Ubah Jurnal Mengajar' : 'Isi Jurnal Mengajar'">
    <form method="post" action="{{ $journal->exists ? route('academic.teaching-journals.update', $journal) : route('academic.teaching-journals.store') }}" class="card card-body space-y-6">
        @csrf
        @if($journal->exists) @method('put') @endif
        <x-ui.page-header :title="($journal->exists ? 'Ubah' : 'Isi').' Jurnal Mengajar'" description="Catat uraian mengajar, metode pembelajaran, dan absensi siswa dalam satu proses." />

        <section class="grid gap-4 md:grid-cols-2">
            <label>Tahun Ajaran<select class="input" name="academic_year_id" required>@foreach($years as $item)<option value="{{$item->id}}" @selected(old('academic_year_id',$journal->academic_year_id)==$item->id)>{{$item->name}}</option>@endforeach</select>@error('academic_year_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Semester<select class="input" name="semester_id" required>@foreach($semesters as $item)<option value="{{$item->id}}" @selected(old('semester_id',$journal->semester_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select>@error('semester_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Hari/Tanggal<input class="input" type="date" name="journal_date" value="{{old('journal_date',$journal->journal_date?->toDateString())}}" required>@error('journal_date')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Rombel<select id="journal-classroom" class="input" name="classroom_id" required>@foreach($rooms as $item)<option value="{{$item->id}}" @selected(old('classroom_id',$journal->classroom_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select>@error('classroom_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Mata Pelajaran<select class="input" name="academic_subject_id" required>@foreach($subjects as $item)<option value="{{$item->id}}" @selected(old('academic_subject_id',$journal->academic_subject_id)==$item->id)>{{$item->name}}</option>@endforeach</select>@error('academic_subject_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Jam ke<input id="journal-lesson-number" class="input" required name="lesson_number" value="{{old('lesson_number',$journal->lesson_number)}}" placeholder="Contoh: 1–2">@error('lesson_number')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label class="md:col-span-2">Uraian Mengajar<textarea class="input min-h-24" required maxlength="5000" name="topic" placeholder="Materi atau uraian yang diajarkan">{{old('topic',$journal->topic)}}</textarea>@error('topic')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label class="md:col-span-2">Metode Pembelajaran<input class="input" required maxlength="255" name="learning_method" value="{{old('learning_method',$journal->learning_method)}}" placeholder="Contoh: diskusi, demonstrasi, dan tanya jawab">@error('learning_method')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            @foreach(['learning_objectives'=>'Tujuan Pembelajaran','learning_material'=>'Materi Pembelajaran','learning_activity'=>'Kegiatan Pembelajaran','assignment'=>'Tugas','notes'=>'Keterangan/Catatan'] as $key=>$label)
                <label>{{$label}}<textarea class="input min-h-24" maxlength="10000" name="{{$key}}">{{old($key,$journal->$key)}}</textarea>@error($key)<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            @endforeach
            @can('teaching-journals.view-all')<label class="md:col-span-2">Ustaz/Ustazah<select class="input" name="personnel_id">@foreach($teachers as $item)<option value="{{$item->id}}" @selected(old('personnel_id',$journal->personnel_id)==$item->id)>{{$item->full_name}}</option>@endforeach</select>@error('personnel_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>@endcan
        </section>
        @unless($journal->exists)<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3"><button class="btn btn-secondary" type="button" data-reload-attendance>Muat Data Absensi Sesuai Konteks</button><p class="mt-2 text-xs text-slate-600">Gunakan tombol ini setelah mengganti tahun ajaran, semester, tanggal, atau rombel agar data harian yang tepat dimuat.</p></div>@endunless

        <section>
            <h2 class="text-lg font-black text-emerald-900">Absensi Siswa</h2>
            <p class="mb-3 text-sm text-slate-600" data-manual-help>Untuk jam 1–2, isi status seluruh siswa. Data ini menjadi absensi resmi harian dan dipakai jurnal berikutnya.</p>
            <p class="mb-3 hidden text-sm text-slate-600" data-auto-help>Jurnal lanjutan menggunakan absensi harian secara otomatis. Koreksi hanya berlaku untuk pelajaran ini dan wajib disertai alasan.</p>
            @error('attendances')<p class="mb-2 text-sm font-semibold text-red-600">{{$message}}</p>@enderror
            @foreach($rooms as $room)
                <div class="attendance-roster overflow-x-auto" data-classroom="{{$room->id}}">
                    <table class="w-full text-sm"><thead><tr class="bg-emerald-900 text-white"><th class="p-2 text-left">Nama Siswa</th><th class="p-2">Status</th><th class="p-2 text-left">Keterangan / Koreksi</th></tr></thead><tbody>
                    @foreach($room->students->sortBy('full_name')->values() as $index=>$student)
                        @php($record=$dailyAttendance->get($student->id))
                        @php($snapshot=$snapshotAttendance->get($student->id))
                        @php($fromRfid=($record?->source?->value ?? $record?->source) === 'rfid')
                        <tr class="border-b">
                            <td class="p-2 font-semibold">{{$student->full_name}}</td>
                            <td class="p-2" data-manual-fields><input type="hidden" name="attendances[{{$index}}][student_id]" value="{{$student->id}}">@if($fromRfid)<input type="hidden" name="attendances[{{$index}}][status]" value="present"><span class="block rounded-lg bg-emerald-100 px-3 py-2 text-center font-semibold text-emerald-800">✓ Hadir otomatis (RFID)</span>@else<select class="input" required name="attendances[{{$index}}][status]"><option value="">Pilih status</option>@foreach(['present'=>'Hadir','sick'=>'Sakit','permitted'=>'Izin','absent'=>'Alpa'] as $value=>$label)<option value="{{$value}}" @selected(old("attendances.$index.status",$record?->status?->value)===$value)>{{$label}}</option>@endforeach</select>@endif @error("attendances.$index.status")<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</td>
                            <td class="p-2" data-manual-fields><input class="input" maxlength="1000" name="attendances[{{$index}}][notes]" value="{{old("attendances.$index.notes",$record?->notes)}}" placeholder="Opsional" @if($fromRfid) disabled data-rfid-disabled @endif>@error("attendances.$index.notes")<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</td>
                            <td class="hidden p-2" data-auto-fields><span class="badge {{$record?'badge-success':'badge-warning'}}">{{$record?->status?->label() ?? 'Belum diisi oleh guru jam 1–2'}}</span><small class="mt-1 block text-slate-500">Sumber: {{$fromRfid?'RFID':($record?'Absensi harian manual':'Menunggu absensi harian')}}</small></td>
                            <td class="hidden p-2" data-auto-fields><input type="hidden" name="attendance_corrections[{{$index}}][student_id]" value="{{$student->id}}"><select class="input" name="attendance_corrections[{{$index}}][status]"><option value="">Tanpa koreksi</option>@foreach(['present'=>'Hadir','sick'=>'Sakit','permitted'=>'Izin','absent'=>'Alpa'] as $value=>$label)<option value="{{$value}}" @selected(old("attendance_corrections.$index.status",$snapshot?->source==='correction'?$snapshot->status->value:null)===$value)>{{$label}}</option>@endforeach</select><input class="input mt-2" maxlength="1000" name="attendance_corrections[{{$index}}][reason]" value="{{old("attendance_corrections.$index.reason",$snapshot?->correction_reason)}}" placeholder="Alasan koreksi khusus pelajaran">@error("attendance_corrections.$index.reason")<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</td>
                        </tr>
                    @endforeach
                    </tbody></table>
                    @if($room->students->isEmpty())<p class="p-4 text-slate-500">Rombel belum memiliki siswa aktif.</p>@endif
                </div>
            @endforeach
        </section>
        <button class="btn btn-primary w-full">Simpan Jurnal dan Absensi</button>
    </form>
    <script>
        const classroom = document.getElementById('journal-classroom');
        const lessonNumber = document.getElementById('journal-lesson-number');
        const isOpeningLesson = () => (lessonNumber.value.match(/\d+/g) || []).slice(0, 2).join('-') === '1-2';
        function showRoster() {
            const manual = isOpeningLesson();
            document.querySelector('[data-manual-help]').classList.toggle('hidden', !manual);
            document.querySelector('[data-auto-help]').classList.toggle('hidden', manual);
            document.querySelectorAll('.attendance-roster').forEach((roster) => {
                const active = roster.dataset.classroom === classroom.value;
                roster.hidden = !active;
                roster.querySelectorAll('[data-manual-fields]').forEach((cell) => cell.classList.toggle('hidden', !manual));
                roster.querySelectorAll('[data-auto-fields]').forEach((cell) => cell.classList.toggle('hidden', manual));
                roster.querySelectorAll('[data-manual-fields] input,[data-manual-fields] select').forEach((input) => input.disabled = !active || !manual || input.hasAttribute('data-rfid-disabled'));
                roster.querySelectorAll('[data-auto-fields] input,[data-auto-fields] select').forEach((input) => input.disabled = !active || manual);
            });
        }
        classroom.addEventListener('change', showRoster); lessonNumber.addEventListener('input', showRoster); showRoster();
        document.querySelector('[data-reload-attendance]')?.addEventListener('click', () => {
            const query = new URLSearchParams({academic_year_id: document.querySelector('[name="academic_year_id"]').value, semester_id: document.querySelector('[name="semester_id"]').value, journal_date: document.querySelector('[name="journal_date"]').value, classroom_id: classroom.value});
            window.location.assign(`{{ route('academic.teaching-journals.create') }}?${query}`);
        });
    </script>
</x-app-layout>
