<x-app-layout title="Input Absensi Siswa">
<form method="post" action="{{ route('student-attendances.store') }}" enctype="multipart/form-data" x-data="{submitting:false, markAll(){document.querySelectorAll('[data-status]').forEach(el=>el.value='hadir')}}" @submit="submitting=true">
@csrf
<input type="hidden" name="attendance_date" value="{{ $session->attendance_date->toDateString() }}">
<input type="hidden" name="classroom_id" value="{{ $session->classroom_id }}">
<div class="space-y-6">
  <x-ui.page-header title="Input Absensi Siswa" description="Catat kehadiran siswa per kelas berdasarkan data rombel aktif dan tanggal absensi.">
    <x-slot:secondary>
      <x-ui.button type="button" variant="outline" @click="markAll()">Tandai Semua Hadir</x-ui.button>
      <x-ui.button variant="secondary" :href="route('student-attendances.index')">Kembali</x-ui.button>
    </x-slot:secondary>
  </x-ui.page-header>
  <x-ui.card>
    <div class="grid gap-3 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-4">
      <div><span class="font-semibold text-slate-700">Kelas:</span> {{ $session->classroom->name }}</div>
      <div><span class="font-semibold text-slate-700">Tanggal:</span> {{ $session->attendance_date->format('d/m/Y') }}</div>
      <div><span class="font-semibold text-slate-700">Tahun Ajaran:</span> {{ $session->academicYear?->name ?? '-' }}</div>
      <div><span class="font-semibold text-slate-700">Wali Kelas:</span> {{ $session->classroom->homeroomTeacher?->fullName() ?? '-' }}</div>
    </div>
  </x-ui.card>
  <div class="grid gap-3 md:grid-cols-5">@foreach($summary as $key=>$total)<x-ui.card><p class="text-xs font-semibold text-slate-500">{{ $statuses[$key] ?? $key }}</p><p class="mt-2 text-2xl font-bold text-emerald-950">{{ $total }}</p></x-ui.card>@endforeach</div>
  <x-ui.card>
    <x-ui.table :headers="['No','Siswa','Status','Jam','Keterangan','Bukti']">
      @forelse($enrollments as $enrollment)
        @php($attendance=$session->attendances->firstWhere('student_enrollment_id',$enrollment->id))
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td><div class="font-semibold text-emerald-950">{{ $enrollment->student->name }}</div><div class="text-xs text-slate-500">NIS {{ $enrollment->student->nis ?? '-' }}</div></td>
          <td><select data-status name="students[{{ $enrollment->id }}][status]">@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(old("students.{$enrollment->id}.status", $attendance?->status?->value)===$value)>{{ $label }}</option>@endforeach</select>@error("students.{$enrollment->id}.status")<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</td>
          <td><div class="grid gap-2"><input type="time" name="students[{{ $enrollment->id }}][arrival_time]" value="{{ old("students.{$enrollment->id}.arrival_time", $attendance?->arrival_time) }}"><input type="number" min="0" name="students[{{ $enrollment->id }}][late_minutes]" value="{{ old("students.{$enrollment->id}.late_minutes", $attendance?->late_minutes ?? 0) }}" placeholder="Menit terlambat"></div></td>
          <td><textarea name="students[{{ $enrollment->id }}][notes]" rows="2" placeholder="Catatan opsional">{{ old("students.{$enrollment->id}.notes", $attendance?->notes) }}</textarea></td>
          <td><input type="file" name="students[{{ $enrollment->id }}][attachment]" class="text-xs">@if($attendance?->attachment_path)<x-ui.button class="mt-2" variant="ghost" :href="route('student-attendances.attachments.show',$attendance)">Unduh Bukti</x-ui.button>@endif</td>
        </tr>
      @empty
        <tr><td colspan="6"><x-ui.empty-state title="Tidak ada siswa aktif" description="Tidak ada siswa aktif pada kelas dan tanggal ini. Pilih kelas lain atau periksa data rombel." /></td></tr>
      @endforelse
    </x-ui.table>
  </x-ui.card>
  <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
    <x-ui.button type="submit" name="action" value="draft" variant="outline" ::disabled="submitting">Simpan Draft</x-ui.button>
    <x-ui.button type="submit" name="action" value="finalize" onclick="return confirm('Finalisasi absensi? Data final hanya dapat diubah melalui koreksi.')" ::disabled="submitting">Finalisasi Absensi</x-ui.button>
  </div>
</div>
</form>
</x-app-layout>
