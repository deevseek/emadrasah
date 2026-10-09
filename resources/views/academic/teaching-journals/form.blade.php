<x-app-layout :title="$journal->exists ? 'Ubah Jurnal Mengajar' : 'Isi Jurnal Mengajar'">
    <form id="journal-form" method="post" action="{{ $journal->exists ? route('academic.teaching-journals.update', $journal) : route('academic.teaching-journals.store') }}" class="card card-body space-y-6">
        @csrf
        @if($journal->exists) @method('put') @endif
        <x-ui.page-header :title="($journal->exists ? 'Ubah' : 'Isi').' Jurnal Mengajar'" description="Catat uraian mengajar, metode pembelajaran, dan absensi siswa dalam satu proses." />

        <section class="grid gap-4 md:grid-cols-2">
            <label>Tahun Ajaran<select class="input" name="academic_year_id" required>@foreach($years as $item)<option value="{{$item->id}}" @selected(old('academic_year_id',$journal->academic_year_id)==$item->id)>{{$item->name}}</option>@endforeach</select>@error('academic_year_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Semester<select class="input" name="semester_id" required>@foreach($semesters as $item)<option value="{{$item->id}}" @selected(old('semester_id',$journal->semester_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select>@error('semester_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Hari/Tanggal<input class="input" type="date" name="journal_date" value="{{old('journal_date',$journal->journal_date?->toDateString())}}" required>@error('journal_date')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Rombel<select id="journal-classroom" class="input" name="classroom_id" required>@foreach($rooms as $item)<option value="{{$item->id}}" @selected(old('classroom_id',$journal->classroom_id)==$item->id)>{{$item->display_name}}</option>@endforeach</select>@error('classroom_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Mata Pelajaran<select class="input" name="academic_subject_id" required>@foreach($subjects as $item)<option value="{{$item->id}}" @selected(old('academic_subject_id',$journal->academic_subject_id)==$item->id)>{{$item->name}}</option>@endforeach</select>@error('academic_subject_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label>Jam ke<input class="input" required name="lesson_number" value="{{old('lesson_number',$journal->lesson_number)}}" placeholder="Contoh: 1–2">@error('lesson_number')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label class="md:col-span-2">Uraian Mengajar<textarea class="input min-h-24" required maxlength="5000" name="topic" placeholder="Materi atau uraian yang diajarkan">{{old('topic',$journal->topic)}}</textarea>@error('topic')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            <label class="md:col-span-2">Metode Pembelajaran<input class="input" required maxlength="255" name="learning_method" value="{{old('learning_method',$journal->learning_method)}}" placeholder="Contoh: diskusi, demonstrasi, dan tanya jawab">@error('learning_method')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            @foreach(['learning_objectives'=>'Tujuan Pembelajaran','learning_material'=>'Materi Pembelajaran','learning_activity'=>'Kegiatan Pembelajaran','assignment'=>'Tugas','notes'=>'Keterangan/Catatan'] as $key=>$label)
                <label>{{$label}}<textarea class="input min-h-24" maxlength="10000" name="{{$key}}">{{old($key,$journal->$key)}}</textarea>@error($key)<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>
            @endforeach
            @can('teaching-journals.view-all')<label class="md:col-span-2">Ustaz/Ustazah<select class="input" name="personnel_id">@foreach($teachers as $item)<option value="{{$item->id}}" @selected(old('personnel_id',$journal->personnel_id)==$item->id)>{{$item->full_name}}</option>@endforeach</select>@error('personnel_id')<p class="mt-1 text-sm text-red-600">{{$message}}</p>@enderror</label>@endcan
        </section>

        <section>
            <h2 class="text-lg font-black text-emerald-900">Absensi Siswa</h2>
            <p class="mb-3 text-sm text-slate-600">Kehadiran mengikuti absensi harian. Koreksi khusus pelajaran wajib disertai alasan dan tidak mengubah absensi harian.</p>
            @if($errors->any())<ul class="text-red-600">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul>@endif
            <p id="attendance-message" class="text-sm text-slate-600" role="status"></p>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-emerald-900 text-white"><tr><th class="p-2">Nama Siswa</th><th>Status Awal / Sumber / Kedatangan</th><th>Koreksi Pelajaran</th><th>Keterangan / Alasan</th></tr></thead><tbody id="attendance-rows"></tbody></table></div>
        </section>
        <button class="btn btn-primary w-full">Simpan Jurnal dan Absensi</button>
    </form>
    <script>
        const form = document.getElementById('journal-form');
        const body = document.getElementById('attendance-rows');
        const message = document.getElementById('attendance-message');
        const submit = form.querySelector('button');
        const labels = {pending:'Belum Tercatat',present:'Hadir',sick:'Sakit',permitted:'Izin',absent:'Alpa'};
        let generation = 0;
        const oldRows = @json(old('attendances', []));
        let initial = true;
        async function loadAttendance() {
            const current = ++generation;
            submit.disabled = true; body.replaceChildren(); message.textContent = 'Memuat absensi harian…';
            const params = new URLSearchParams();
            ['academic_year_id','semester_id','classroom_id','academic_subject_id','journal_date'].forEach(key => params.set(key, form.elements[key].value));
            @if($journal->exists) params.set('journal_id', @json($journal->id)); @endif
            try {
                const response = await fetch(@json(route('academic.teaching-journals.attendance', [], false)) + '?' + params, {credentials:'same-origin', headers:{Accept:'application/json'}});
                if (!response.ok) throw new Error('Absensi tidak dapat dimuat. Periksa tahun ajaran, semester, rombel, dan tanggal.');
                const data = await response.json();
                if(current !== generation) return;
                data.rows.forEach((row,index) => {
                    const tr = document.createElement('tr'); tr.className = 'border-b';
                    const name = document.createElement('td'); name.className = 'p-2'; name.textContent = row.name;
                    const id = document.createElement('input'); id.type='hidden'; id.name=`attendances[${index}][student_id]`; id.value=row.student_id; name.append(id);
                    const info = document.createElement('td'); info.textContent = labels[row.daily_status]+' · '+({rfid:'RFID',manual:'Manual'}[row.daily_source] || 'Belum Tercatat')+' · '+(row.arrival_at || '—');
                    const correction = document.createElement('td');
                    const mode = document.createElement('select'); mode.className='input'; mode.name=`attendances[${index}][mode]`;
                    const modes = {daily:'Ikuti absensi harian',manual:'Koreksi khusus pelajaran'};
                    if(row.origin !== 'daily') modes.keep='Pertahankan catatan tersimpan';
                    Object.entries(modes).forEach(([value,label]) => mode.add(new Option(label,value)));
                    mode.value=row.origin === 'daily' ? 'daily' : 'keep';
                    const status = document.createElement('select'); status.className='input'; status.required=true; status.name=`attendances[${index}][status]`;
                    Object.entries(labels).forEach(([value,label]) => status.add(new Option(label,value))); status.value=row.status;
                    correction.append(mode,status);
                    const noteCell=document.createElement('td'); const notes=document.createElement('input'); notes.className='input'; notes.maxLength=1000; notes.name=`attendances[${index}][notes]`; notes.value=row.notes || ''; noteCell.append(notes);
                    if(initial) {const old=oldRows.find(item => Number(item.student_id) === row.student_id); if(old){mode.value=old.mode || mode.value; status.value=old.status; notes.value=old.notes || '';}}
                    function toggle(){ status.disabled=false; if(mode.value !== 'manual') status.value=row.status; status.querySelector('option[value="pending"]').disabled=mode.value === 'manual'; if(mode.value === 'manual' && status.value === 'pending') status.value=''; notes.readOnly=mode.value !== 'manual'; notes.required=mode.value === 'manual'; status.style.pointerEvents=mode.value === 'manual' ? '' : 'none'; }
                    mode.addEventListener('change',toggle); toggle(); tr.append(name,info,correction,noteCell); body.append(tr);
                });
                initial=false; message.textContent=data.rows.length ? '' : 'Rombel belum memiliki siswa aktif pada tanggal ini.'; submit.disabled=!data.rows.length;
            } catch(error){if(current===generation) message.textContent=error instanceof TypeError ? 'Daftar siswa tidak dapat dimuat. Periksa koneksi lalu pilih kembali rombel atau tanggal.' : error.message;}
        }
        ['semester_id','classroom_id','academic_subject_id','journal_date','lesson_number'].forEach(key => form.elements[key].addEventListener('change', () => {initial=false; loadAttendance();}));
        form.elements.academic_year_id.addEventListener('change', async () => {
            initial=false; const current=++generation; submit.disabled=true; body.replaceChildren();
            try {
                const response=await fetch(@json(route('academic.teaching-journals.attendance', [], false))+'?options=1&academic_year_id='+encodeURIComponent(form.elements.academic_year_id.value), {credentials:'same-origin', headers:{Accept:'application/json'}});
                if(!response.ok) throw new Error('Pilihan periode tidak dapat dimuat.');
                const data=await response.json();
                if(current !== generation) return;
                for(const [key,items] of Object.entries(data)) {const select=form.elements[key]; select.replaceChildren(); items.forEach(item=>select.add(new Option(item.label,item.id)));}
                loadAttendance();
            } catch(error){if(current === generation) message.textContent=error instanceof TypeError ? 'Pilihan periode tidak dapat dimuat. Periksa koneksi lalu pilih kembali tahun ajaran.' : error.message;}
        });
        loadAttendance();
    </script>
</x-app-layout>
