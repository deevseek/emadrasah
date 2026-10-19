<x-app-layout title="Penugasan dan Jadwal Mengajar">
    <div class="card card-body space-y-4">
        <x-ui.page-header title="Penugasan dan Jadwal Mengajar" description="Tetapkan guru, rombel, dan mata pelajaran untuk setiap periode. Jadwal aktif memberikan akses pengisian jurnal pada semester tersebut." />
        <a class="btn btn-secondary" href="{{route('academic.teaching-journals.index')}}">Kembali ke Jurnal</a>
        @if($errors->any())<div class="text-red-600" role="alert">@foreach($errors->all() as $error)<p>{{$error}}</p>@endforeach</div>@endif
        <form method="post" action="{{route('academic.lesson-schedules.store')}}" class="grid gap-4 md:grid-cols-2">
            @csrf
            <label>Tahun ajaran<select class="input" name="academic_year_id" required>@foreach($years as $item)<option value="{{$item->id}}" @selected(old('academic_year_id')==$item->id)>{{$item->name}}</option>@endforeach</select></label>
            <label>Semester<select class="input" name="semester_id" required>@foreach($semesters as $item)<option value="{{$item->id}}" @selected(old('semester_id')==$item->id)>{{$item->academicYear->name}} — {{$item->display_name}}</option>@endforeach</select></label>
            <label>Rombel<select class="input" name="classroom_id" required>@foreach($rooms as $item)<option value="{{$item->id}}" @selected(old('classroom_id')==$item->id)>{{$item->academicYear->name}} — {{$item->display_name}}</option>@endforeach</select></label>
            <label>Guru<select class="input" name="personnel_id" required>@foreach($teachers as $item)<option value="{{$item->id}}" @selected(old('personnel_id')==$item->id)>{{$item->full_name}}</option>@endforeach</select></label>
            <label>Mata pelajaran<select class="input" name="academic_subject_id" required>@foreach($subjects as $item)<option value="{{$item->id}}" @selected(old('academic_subject_id')==$item->id)>{{$item->name}}</option>@endforeach</select></label>
            <label>Hari<select class="input" name="day_of_week" required>@foreach([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'] as $id=>$name)<option value="{{$id}}" @selected(old('day_of_week')==$id)>{{$name}}</option>@endforeach</select></label>
            <label>Jam mulai<input class="input" type="time" name="start_time" value="{{old('start_time')}}" required></label>
            <label>Jam selesai<input class="input" type="time" name="end_time" value="{{old('end_time')}}" required></label>
            <button class="btn btn-primary">Tambah Penugasan Aktif</button>
        </form>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Periode</th><th>Guru / Rombel / Mapel</th><th>Jadwal</th><th>Status</th></tr></thead><tbody>
            @foreach($schedules as $item)<tr class="border-b"><td class="p-2">{{$item->academicYear->name}} / {{$item->semester->display_name}}</td><td class="p-2">{{$item->personnel?->full_name ?? 'Guru belum ditetapkan'}} / {{$item->classroom->display_name}} / {{$item->subject->name}}</td><td class="p-2">{{[1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'][$item->day_of_week]}} {{$item->start_time}}–{{$item->end_time}}</td><td class="p-2">@if($item->active)<form method="post" action="{{route('academic.lesson-schedules.deactivate',$item)}}">@csrf @method('patch')<button class="btn btn-secondary">Nonaktifkan</button></form>@else Tidak aktif @endif</td></tr>@endforeach
        </tbody></table></div>
        {{$schedules->links()}}
    </div>
</x-app-layout>
