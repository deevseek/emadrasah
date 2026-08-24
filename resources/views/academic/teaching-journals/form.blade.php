<x-app-layout :title="$journal->exists ? 'Ubah Jurnal Mengajar' : 'Isi Jurnal Mengajar'">
    <form method="post" action="{{ $journal->exists ? route('academic.teaching-journals.update', $journal) : route('academic.teaching-journals.store') }}" class="card card-body space-y-6">
        @csrf
        @if($journal->exists) @method('put') @endif
        <x-ui.page-header :title="($journal->exists ? 'Ubah' : 'Isi').' Jurnal Mengajar'" description="Catat uraian mengajar, metode pembelajaran, dan absensi siswa dalam satu proses." />

        <section class="grid gap-4 md:grid-cols-2">
            <label>Tahun Ajaran<select class="input" name="academic_year_id" required>@foreach($years as $item)<option value="{{$item->id}}" @selected(old('academic_year_id',$journal->academic_year_id)==$item->id)>{{$item->name}}</option>@endforeach</select></label>
            <label>Semester<select class="input" name="semester_id" required>@foreach($semesters as $item)<option value="{{$item->id}}" @selected(old('semester_id',$journal->semester_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select></label>
            <label>Hari/Tanggal<input class="input" type="date" name="journal_date" value="{{old('journal_date',$journal->journal_date?->toDateString())}}" required></label>
            <label>Rombel<select id="journal-classroom" class="input" name="classroom_id" required>@foreach($rooms as $item)<option value="{{$item->id}}" @selected(old('classroom_id',$journal->classroom_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select></label>
            <label>Mata Pelajaran<select class="input" name="academic_subject_id" required>@foreach($subjects as $item)<option value="{{$item->id}}" @selected(old('academic_subject_id',$journal->academic_subject_id)==$item->id)>{{$item->name}}</option>@endforeach</select></label>
            <label>Jam ke<input class="input" required name="lesson_number" value="{{old('lesson_number',$journal->lesson_number)}}" placeholder="Contoh: 1–2"></label>
            <label class="md:col-span-2">Uraian Mengajar<textarea class="input min-h-24" required name="topic" placeholder="Materi atau uraian yang diajarkan">{{old('topic',$journal->topic)}}</textarea></label>
            <label class="md:col-span-2">Metode Pembelajaran<input class="input" required name="learning_method" value="{{old('learning_method',$journal->learning_method)}}" placeholder="Contoh: diskusi, demonstrasi, dan tanya jawab"></label>
            @foreach(['learning_objectives'=>'Tujuan Pembelajaran','learning_material'=>'Materi Pembelajaran','learning_activity'=>'Kegiatan Pembelajaran','assignment'=>'Tugas','notes'=>'Keterangan/Catatan'] as $key=>$label)
                <label>{{$label}}<textarea class="input min-h-24" name="{{$key}}">{{old($key,$journal->$key)}}</textarea></label>
            @endforeach
            @can('teaching-journals.view-all')<label class="md:col-span-2">Ustaz/Ustazah<select class="input" name="personnel_id">@foreach($teachers as $item)<option value="{{$item->id}}" @selected(old('personnel_id',$journal->personnel_id)==$item->id)>{{$item->full_name}}</option>@endforeach</select></label>@endcan
        </section>

        <section>
            <h2 class="text-lg font-black text-emerald-900">Absensi Siswa</h2>
            <p class="mb-3 text-sm text-slate-600">Absensi ini tersimpan bersama jurnal sekaligus memperbarui data absensi harian siswa.</p>
            @error('attendances')<p class="mb-2 text-sm font-semibold text-red-600">{{$message}}</p>@enderror
            @foreach($rooms as $room)
                <div class="attendance-roster overflow-x-auto" data-classroom="{{$room->id}}">
                    <table class="w-full text-sm"><thead><tr class="bg-emerald-900 text-white"><th class="p-2 text-left">Nama Siswa</th><th class="p-2">Status</th><th class="p-2 text-left">Keterangan</th></tr></thead><tbody>
                    @foreach($room->students->sortBy('full_name')->values() as $index=>$student)
                        @php($record=$savedAttendance->get($student->id))
                        <tr class="border-b"><td class="p-2 font-semibold">{{$student->full_name}}<input type="hidden" name="attendances[{{$index}}][student_id]" value="{{$student->id}}"></td><td class="p-2"><select class="input" required name="attendances[{{$index}}][status]">@foreach(['present'=>'Hadir','sick'=>'Sakit','permitted'=>'Izin','absent'=>'Alpa'] as $value=>$label)<option value="{{$value}}" @selected(old("attendances.$index.status",$record?->status?->value ?? 'present')===$value)>{{$label}}</option>@endforeach</select></td><td class="p-2"><input class="input" name="attendances[{{$index}}][notes]" value="{{old("attendances.$index.notes",$record?->notes)}}" placeholder="Opsional"></td></tr>
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
        function showRoster() {
            document.querySelectorAll('.attendance-roster').forEach((roster) => {
                const active = roster.dataset.classroom === classroom.value;
                roster.hidden = !active;
                roster.querySelectorAll('input,select').forEach((input) => input.disabled = !active);
            });
        }
        classroom.addEventListener('change', showRoster); showRoster();
    </script>
</x-app-layout>
