<x-app-layout :title="$journal->exists ? 'Ubah Jurnal Mengajar' : 'Isi Jurnal Mengajar'">
    @php($contextValue = fn ($key, $fallback = null) => request()->header('X-Journal-Context') ? $fallback : old($key, $fallback))
    <form id="journal-form" method="post" enctype="multipart/form-data" action="{{ $journal->exists ? route('academic.teaching-journals.update', $journal) : route('academic.teaching-journals.store') }}" class="card card-body space-y-6">
        @csrf
        @if($journal->exists) @method('put') @endif
        <x-ui.page-header :title="($journal->exists ? 'Ubah' : 'Isi').' Jurnal Mengajar'" description="Catat metode pembelajaran dan absensi siswa dalam satu proses." />

        <section class="grid gap-4 md:grid-cols-2">
            <label>Tahun Ajaran<select class="input" name="academic_year_id" required>@foreach($years as $item)<option value="{{$item->id}}" @selected($contextValue('academic_year_id',$journal->academic_year_id)==$item->id)>{{$item->name}}</option>@endforeach</select>@error('academic_year_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Semester<select class="input" name="semester_id" required>@foreach($semesters as $item)<option value="{{$item->id}}" @selected($contextValue('semester_id',$journal->semester_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select>@error('semester_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Hari/Tanggal<input class="input" type="date" name="journal_date" value="{{$contextValue('journal_date',$journal->journal_date?->toDateString())}}" required>@error('journal_date')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Rombel<select id="journal-classroom" class="input" name="classroom_id" required>@foreach($rooms as $item)<option value="{{$item->id}}" @selected($contextValue('classroom_id',$journal->classroom_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select>@error('classroom_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Mata Pelajaran<select class="input" name="academic_subject_id" required>@foreach($subjects as $item)<option value="{{$item->id}}" @selected($contextValue('academic_subject_id',$journal->academic_subject_id)==$item->id)>{{$item->name}}</option>@endforeach</select>@error('academic_subject_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Jam ke<input id="journal-lesson-number" class="input" required name="lesson_number" value="{{$contextValue('lesson_number',$journal->lesson_number)}}" placeholder="Contoh: 1–2">@error('lesson_number')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label class="md:col-span-2">Metode Pembelajaran<input class="input" required maxlength="255" name="learning_method" value="{{$contextValue('learning_method',$journal->learning_method)}}" placeholder="Contoh: diskusi, demonstrasi, dan tanya jawab">@error('learning_method')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            @foreach(['learning_material'=>'Materi Pembelajaran','notes'=>'Keterangan/Catatan'] as $key=>$label)
                <label>{{$label}}<textarea class="input min-h-24" maxlength="10000" name="{{$key}}">{{$contextValue($key,$journal->$key)}}</textarea>@error($key)<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            @endforeach
            <fieldset class="space-y-3 rounded-xl border border-slate-200 p-4 md:col-span-2">
                <legend class="px-2 font-bold text-emerald-900">Tugas</legend>
                <label class="block">Keterangan Tugas<textarea class="input min-h-24" maxlength="10000" name="assignment">{{$contextValue('assignment',$journal->assignment)}}</textarea></label>
                @error('assignment')<p class="text-sm text-red-600">{{$message}}</p>@enderror
                <label class="block">Upload File dari Komputer<input class="input" type="file" name="assignment_files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.txt"></label>
                <p class="text-sm text-slate-500">Maksimal 10 lampiran per jurnal, masing-masing file maksimal 10 MB. Format: PDF, Word, Excel, PowerPoint, JPG, PNG, atau TXT. Pilih ulang file jika penyimpanan belum berhasil.</p>
                @error('assignment_files')<p class="text-sm text-red-600">{{$message}}</p>@enderror
                @foreach($errors->get('assignment_files.*') as $messages)@foreach($messages as $message)<p class="text-sm text-red-600">{{$message}}</p>@endforeach @endforeach
                <div class="space-y-2" data-google-drive-picker data-client-id="{{$drivePicker['ready'] ? $drivePicker['clientId'] : ''}}" data-api-key="{{$drivePicker['ready'] ? $drivePicker['apiKey'] : ''}}" data-app-id="{{$drivePicker['ready'] ? $drivePicker['appId'] : ''}}" data-existing-count="{{$journal->exists ? $journal->attachments->count() : 0}}">
                    <button type="button" class="btn btn-secondary" data-drive-select disabled>Pilih dari Google Drive</button>
                    <p class="text-sm text-slate-600">Login Google untuk memilih file, lalu tekan Simpan Jurnal agar salinan file tersimpan di e-Madrasah. Dokumen Google akan dikonversi ke Word, Excel, PowerPoint, atau PDF.</p>
                    <p class="text-sm text-slate-600" data-drive-status role="status" aria-live="polite">{{$drivePicker['ready'] ? 'Memuat pemilih Google Drive…' : 'Pemilih Google Drive belum diaktifkan. Hubungi operator untuk mengisi konfigurasi Google Cloud pada Pengaturan Aplikasi.'}}</p>
                    <ul class="space-y-1 text-sm text-emerald-800" data-selected-assignment-files></ul>
                </div>
                <label class="block">Tautan Google Drive (opsional)<input class="input" type="url" name="assignment_drive_url" maxlength="2048" value="{{$contextValue('assignment_drive_url')}}" placeholder="https://drive.google.com/file/d/…/view"></label>
                <p class="text-sm text-slate-500">Tempel tautan file Google Drive atau Google Docs. File tetap berada di Drive; pastikan izin berbagi mengizinkan penerima jurnal membukanya.</p>
                @error('assignment_drive_url')<p class="text-sm text-red-600">{{$message}}</p>@enderror
                @if($journal->exists && $journal->attachments->isNotEmpty())
                    <ul class="space-y-2">@foreach($journal->attachments as $attachment)<li class="flex flex-wrap items-center gap-3">
                        @if($attachment->drive_url)<a class="text-emerald-800 underline" href="{{$attachment->drive_url}}" target="_blank" rel="noopener noreferrer">{{$attachment->original_name}} ↗</a>
                        @else<a class="text-emerald-800 underline" href="{{route('academic.teaching-journals.attachments.download',[$journal,$attachment])}}">{{$attachment->original_name}}</a>@endif
                        <label class="text-sm"><input type="checkbox" name="remove_assignment_attachments[]" value="{{$attachment->id}}" @checked(in_array($attachment->id,$contextValue('remove_assignment_attachments',[])))> Hapus lampiran saat disimpan</label>
                    </li>@endforeach</ul>
                @endif
                @error('remove_assignment_attachments')<p class="text-sm text-red-600">{{$message}}</p>@enderror
            </fieldset>
            @can('teaching-journals.view-all')<label class="md:col-span-2">Ustaz/Ustazah<select class="input" name="personnel_id">@foreach($teachers as $item)<option value="{{$item->id}}" @selected($contextValue('personnel_id',$journal->personnel_id)==$item->id)>{{$item->full_name}}</option>@endforeach</select>@error('personnel_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>@endcan
        </section>
        <p class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" data-context-status role="status">Memuat absensi sesuai konteks yang dipilih…</p>

        <section id="journal-attendance-context">
        @if($rooms->isEmpty())<p class="rounded-lg bg-amber-50 p-3 text-amber-900">Belum ada rombel aktif pada tahun ajaran ini. Hubungi Operator Madrasah untuk mengaktifkan rombel.</p>@endif
            <h2 class="text-lg font-black text-emerald-900">Absensi Siswa</h2>
            <p class="mb-3 text-sm text-slate-600" data-manual-help>Untuk jam 1–2, isi status seluruh siswa. Data ini menjadi absensi resmi harian dan dipakai jurnal berikutnya.</p>
            <p class="mb-3 hidden text-sm text-slate-600" data-auto-help>Jurnal lanjutan menggunakan absensi harian secara otomatis. Koreksi hanya berlaku untuk pelajaran ini dan wajib disertai alasan.</p>
            @error('attendances')<p class="mb-2 text-sm font-semibold text-red-600">{{$message}}</p>@enderror
            @foreach($rooms->where('id', $journal->classroom_id) as $room)
                <div class="attendance-roster overflow-x-auto" data-classroom="{{$room->id}}">
                    <table class="w-full text-sm"><thead><tr class="bg-emerald-900 text-white"><th class="p-2 text-left">Nama Siswa</th><th class="p-2">Status</th><th class="p-2 text-left">Keterangan / Koreksi</th></tr></thead><tbody>
                    @foreach($room->students->sortBy('full_name')->values() as $index=>$student)
                        @php($record=$dailyAttendance->get($student->id))
                        @php($snapshot=$snapshotAttendance->get($student->id))
                        @php($fromRfid=($record?->source?->value ?? $record?->source) === 'rfid')
                        <tr class="border-b">
                            <td class="p-2 font-semibold">{{$student->full_name}}</td>
                            <td class="p-2" data-manual-fields><input type="hidden" name="attendances[{{$index}}][student_id]" value="{{$student->id}}">@if($fromRfid)<input type="hidden" name="attendances[{{$index}}][status]" value="present"><span class="block rounded-lg bg-emerald-100 px-3 py-2 text-center font-semibold text-emerald-800">✓ Hadir otomatis (RFID)</span>@else<select class="input" required name="attendances[{{$index}}][status]"><option value="">Pilih status</option>@foreach(['present'=>'Hadir','sick'=>'Sakit','permitted'=>'Izin','absent'=>'Alpa'] as $value=>$label)<option value="{{$value}}" @selected($contextValue("attendances.$index.status",$record?->status?->value)===$value)>{{$label}}</option>@endforeach</select>@endif @error("attendances.$index.status")<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</td>
                            <td class="p-2" data-manual-fields><input class="input" maxlength="1000" name="attendances[{{$index}}][notes]" value="{{$contextValue("attendances.$index.notes",$record?->notes)}}" placeholder="Opsional" @if($fromRfid) disabled data-rfid-disabled @endif>@error("attendances.$index.notes")<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</td>
                            <td class="hidden p-2" data-auto-fields><span class="badge {{$record?'badge-success':'badge-warning'}}">{{$record?->status?->label() ?? 'Belum diisi oleh guru jam 1–2'}}</span><small class="mt-1 block text-slate-500">Sumber: {{$fromRfid?'RFID':($record?'Absensi harian manual':'Menunggu absensi harian')}}</small></td>
                            <td class="hidden p-2" data-auto-fields><input type="hidden" name="attendance_corrections[{{$index}}][student_id]" value="{{$student->id}}"><select class="input" name="attendance_corrections[{{$index}}][status]"><option value="">Tanpa koreksi</option>@foreach(['present'=>'Hadir','sick'=>'Sakit','permitted'=>'Izin','absent'=>'Alpa'] as $value=>$label)<option value="{{$value}}" @selected($contextValue("attendance_corrections.$index.status",$snapshot?->source==='correction'?$snapshot->status->value:null)===$value)>{{$label}}</option>@endforeach</select><input class="input mt-2" maxlength="1000" name="attendance_corrections[{{$index}}][reason]" value="{{$contextValue("attendance_corrections.$index.reason",$snapshot?->correction_reason)}}" placeholder="Alasan koreksi khusus pelajaran">@error("attendance_corrections.$index.reason")<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</td>
                        </tr>
                    @endforeach
                    </tbody></table>
                    @if($room->students->isEmpty())<p class="p-4 text-slate-500">Rombel belum memiliki siswa aktif.</p>@endif
                </div>
            @endforeach
        </section>
        <button data-journal-submit class="btn btn-primary w-full">Simpan Jurnal dan Absensi</button>
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
        lessonNumber.addEventListener('input', () => { if (!contextLoading) showRoster(); }); showRoster();
        let contextLoading = false;
        let contextReloadTimer;
        let contextVersion = 0;
        const reloadContext = (event) => {
            contextLoading = true;
            document.getElementById('journal-form').dataset.contextLoading = 'true';
            document.querySelector('#journal-form [data-journal-submit]').disabled = true;
            const version = ++contextVersion;
            clearTimeout(contextReloadTimer);
            document.querySelector('[data-context-status]').textContent = 'Memuat absensi sesuai konteks yang dipilih…';
            document.querySelector('[data-context-status]').classList.remove('hidden');
            document.querySelectorAll('.attendance-roster').forEach((roster) => {
                roster.hidden = true;
                roster.querySelectorAll('input,select').forEach((input) => input.disabled = true);
            });
            contextReloadTimer = setTimeout(async () => {
                if (version !== contextVersion) return;
                const query = new URLSearchParams({
                    academic_year_id: document.querySelector('[name="academic_year_id"]').value,
                    journal_date: document.querySelector('[name="journal_date"]').value,
                    academic_subject_id: document.querySelector('[name="academic_subject_id"]').value,
                    lesson_number: lessonNumber.value,
                });
                if (event.target.name !== 'academic_year_id') {
                    if (event.target.name !== 'journal_date') query.set('semester_id', document.querySelector('[name="semester_id"]').value);
                    query.set('classroom_id', classroom.value);
                }
                if (event.target.name === 'semester_id') query.delete('classroom_id');
                try {
                    const response = await fetch(`?${query}`, {headers: {'Accept': 'text/html', 'X-Journal-Context': '1'}});
                    if (!response.ok || response.redirected) throw new Error('Konteks tidak dapat dimuat. Periksa periode dan rombel yang dipilih.');
                    const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                    if (version !== contextVersion) return;
                    for (const name of ['semester_id', 'classroom_id', 'academic_subject_id']) {
                        const current = document.querySelector(`[name="${name}"]`);
                        const selected = current.value;
                        current.innerHTML = doc.querySelector(`[name="${name}"]`).innerHTML;
                        if (Array.from(current.options).some(option => option.value === selected)) current.value = selected;
                        // Roster harus sesuai rombel yang dipilih server setelah pergantian periode.
                        if (name === 'classroom_id' || name === 'semester_id') current.value = doc.querySelector(`[name="${name}"]`).value;
                    }
                    document.getElementById('journal-attendance-context').innerHTML = doc.getElementById('journal-attendance-context').innerHTML;
                    contextLoading = false;
                    document.getElementById('journal-form').dataset.contextLoading = 'false';
                    showRoster();
                    document.querySelector('[data-context-status]').classList.add('hidden');
                    document.querySelector('#journal-form [data-journal-submit]').disabled = !classroom.value || document.getElementById('journal-form').dataset.driveBusy === 'true';
                } catch (error) {
                    if (version !== contextVersion) return;
                    document.querySelector('[data-context-status]').textContent = error.message;
                }
            }, 250);
        };
        document.querySelectorAll('[name="academic_year_id"],[name="semester_id"],[name="journal_date"],[name="classroom_id"]').forEach((field) => field.addEventListener('change', reloadContext));
        document.getElementById('journal-form').addEventListener('submit', (event) => { if (contextLoading) event.preventDefault(); });
    </script>
    <script type="module" src="{{asset('js/journal-drive-picker.js')}}"></script>
</x-app-layout>
