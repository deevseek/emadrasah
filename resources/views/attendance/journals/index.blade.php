<x-app-layout title="Jurnal Mengajar"><div class="space-y-6">
<x-ui.page-header title="Jurnal Mengajar" description="Kelola jurnal mengajar harian berdasarkan jadwal dan penugasan yang sah." />
<x-ui.card><h2 class="mb-4 font-bold text-emerald-950">Jadwal Hari Ini</h2><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">@forelse($todaySchedules as $schedule)@php($journal=$todayJournals->get($schedule->id))<div class="rounded-2xl border border-slate-200 p-4"><p class="font-semibold">{{ substr($schedule->starts_at,0,5) }}-{{ substr($schedule->ends_at,0,5) }} · {{ $schedule->classroom->name }}</p><p class="text-sm text-slate-600">{{ $schedule->subject->name }}</p><x-ui.badge :variant="$journal ? 'success' : 'warning'">{{ $journal?->status->label() ?? 'Belum Diisi' }}</x-ui.badge><div class="mt-3">@if(!$journal)<x-ui.button :href="route('teaching-journals.create',['lesson_schedule_id'=>$schedule->id,'date'=>today()->toDateString()])">Isi Jurnal</x-ui.button>@elseif($journal->isEditableByTeacher())<x-ui.button variant="outline" :href="route('teaching-journals.edit',$journal)">Lanjutkan</x-ui.button>@else<x-ui.button variant="secondary" :href="route('teaching-journals.show',$journal)">Lihat</x-ui.button>@endif</div></div>@empty<div class="md:col-span-2 xl:col-span-3"><x-ui.empty-state title="Tidak ada jadwal hari ini" description="Jadwal mengajar hari ini belum tersedia." /></div>@endforelse</div></x-ui.card>

<x-ui.card>
  <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <h2 class="font-bold text-emerald-950">Export Jurnal Akademik Bulanan</h2>
      <p class="mt-1 text-sm text-slate-600">Tersedia dua format seperti buku administrasi: <strong>Jurnal Guru</strong> per guru mapel dan <strong>Jurnal Kelas</strong> per wali kelas/rombel.</p>
    </div>
    <div class="grid gap-3 lg:min-w-[34rem]">
      <form class="grid gap-3 sm:grid-cols-3" method="get" action="{{ route('teaching-journals.templates.export') }}">
        <input type="month" name="month" value="{{ request('month', today()->format('Y-m')) }}" class="rounded-xl border-slate-300">
        <select name="type" class="rounded-xl border-slate-300">
          <option value="teacher">Jurnal Guru</option>
          <option value="class">Jurnal Kelas</option>
        </select>
        <x-ui.button>Export Word</x-ui.button>
      </form>
    </div>
  </div>
  <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-emerald-950"><strong>Jurnal Guru</strong> memuat kelas, guru mapel, bulan, semester, uraian mengajar, metode, jumlah hadir/tidak hadir, dan kolom paraf.</div>
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-slate-700"><strong>Jurnal Kelas</strong> memakai tabel yang sama, tetapi identitas sampul dan tanda tangan diarahkan ke wali kelas.</div>
  </div>
  <div class="mt-4 rounded-2xl border border-emerald-100 bg-white p-4 text-sm text-slate-700">
    <h3 class="font-bold text-emerald-950">Cara mengisi jurnal</h3>
    <ol class="mt-2 list-decimal space-y-1 pl-5">
      <li>Pastikan guru sudah memiliki <strong>Penugasan Mengajar</strong> dan <strong>Jadwal Pelajaran</strong> aktif pada tahun ajaran serta semester berjalan.</li>
      <li>Pada bagian <strong>Jadwal Hari Ini</strong>, klik tombol <strong>Isi Jurnal</strong> pada kartu jadwal yang muncul.</li>
      <li>Lengkapi topik, tujuan, materi, metode, media, kegiatan pembelajaran, penilaian, catatan, lalu pilih <strong>Simpan Draft</strong> atau <strong>Simpan dan Kirim</strong>.</li>
      <li>Jurnal yang sudah dikirim akan masuk daftar dan dapat diverifikasi oleh kepala madrasah atau petugas yang berwenang.</li>
      <li>Setelah data jurnal tersedia, pilih bulan dan jenis jurnal, lalu klik <strong>Export Word</strong> untuk mengunduh file sesuai template Word yang sudah diunggah.</li>
    </ol>
    <p class="mt-2 text-amber-700">Jika bagian Jadwal Hari Ini kosong, isi atau aktifkan dahulu jadwal pelajaran guru pada menu Akademik.</p>
  </div>

  @can('teaching-journals.print')
    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
      <h3 class="font-bold">Upload template Word resmi</h3>
      <p class="mt-1">Gunakan file .docx dari madrasah sebagai template. Letakkan placeholder seperti <code>${nama_madrasah}</code>, <code>${kelas}</code>, <code>${bulan}</code>, <code>${nama_guru_mapel}</code>, <code>${nama_wali_kelas}</code>, dan <code>${baris_jurnal}</code> pada dokumen Word.</p>
      <form class="mt-3 grid gap-3 md:grid-cols-[180px_1fr_auto]" method="post" enctype="multipart/form-data" action="{{ route('teaching-journals.templates.store') }}">
        @csrf
        <select name="type" class="rounded-xl border-slate-300"><option value="teacher">Template Jurnal Guru</option><option value="class">Template Jurnal Kelas</option></select>
        <input type="file" name="template" accept=".docx" class="rounded-xl border border-slate-300 bg-white p-2">
        <x-ui.button variant="secondary">Unggah Template</x-ui.button>
      </form>
      <div class="mt-2 grid gap-1 text-xs text-amber-900 sm:grid-cols-2">
        <span>Template Jurnal Guru: {{ $templatePaths['teacher'] ? 'sudah diunggah' : 'belum diunggah' }}</span>
        <span>Template Jurnal Kelas: {{ $templatePaths['class'] ? 'sudah diunggah' : 'belum diunggah' }}</span>
      </div>
    </div>
  @endcan
</x-ui.card>

<x-ui.card><form class="grid gap-3 md:grid-cols-5" method="get"><input type="date" name="date" value="{{ request('date') }}"><select name="status"><option value="">Semua status</option>@foreach(\App\Enums\TeachingJournalStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ $status->label() }}</option>@endforeach</select><x-ui.button>Filter</x-ui.button><x-ui.button variant="secondary" :href="route('teaching-journals.index')">Reset</x-ui.button>@can('teaching-journals.export')<x-ui.button variant="outline" :href="route('teaching-journals.export', request()->query())">Export CSV</x-ui.button>@endcan</form></x-ui.card>
<x-ui.card><x-ui.table :headers="['Tanggal','Jam','Guru','Kelas','Mapel','Pertemuan','Topik','Status','Tindakan']">@forelse($journals as $journal)<tr><td>{{ $journal->journal_date->format('d/m/Y') }}</td><td>{{ substr($journal->scheduled_start_time ?? $journal->starts_at,0,5) }}</td><td>{{ $journal->employee->name }}</td><td>{{ $journal->classroom->name }}</td><td>{{ $journal->subject->name }}</td><td>{{ $journal->meeting_number }}</td><td>{{ $journal->learning_topic ?: '-' }}</td><td><x-ui.badge>{{ $journal->status->label() }}</x-ui.badge></td><td><x-ui.button variant="secondary" :href="route('teaching-journals.show',$journal)">Detail</x-ui.button></td></tr>@empty<tr><td colspan="9"><x-ui.empty-state title="Belum ada jurnal" description="Jurnal akan muncul setelah guru mengisi jadwal mengajar." /></td></tr>@endforelse</x-ui.table><div class="mt-4">{{ $journals->links() }}</div></x-ui.card>
</div></x-app-layout>
