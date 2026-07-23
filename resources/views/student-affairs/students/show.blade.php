<x-app-layout>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-700">Data Siswa</p>
                <h1 class="text-2xl font-bold text-emerald-950">Detail Siswa: {{ $student->name }}</h1>
            </div>
            <a href="{{ route('students.index') }}" class="btn btn-secondary">Kembali</a>
        </div>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-emerald-950">Profil Siswa</h2>
            <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                <p><span class="font-semibold text-slate-700">NIS:</span> {{ $student->student_number ?? '-' }}</p>
                <p><span class="font-semibold text-slate-700">NISN:</span> {{ $student->national_student_number ?? '-' }}</p>
                <p><span class="font-semibold text-slate-700">NIK:</span> {{ $student->national_identity_number ?? '-' }}</p>
                <p><span class="font-semibold text-slate-700">Status:</span> {{ $student->student_status?->label() ?? '-' }}</p>
                <p><span class="font-semibold text-slate-700">Kelas aktif:</span> {{ $student->activeEnrollment?->classroom?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-700">Tempat/Tanggal lahir:</span> {{ trim(($student->birth_place ?? '').' / '.($student->birth_date?->format('d/m/Y') ?? ''), ' /') ?: '-' }}</p>
                <p><span class="font-semibold text-slate-700">No Telepon:</span> {{ $student->phone ?? '-' }}</p>
                <p><span class="font-semibold text-slate-700">Nomor KIP/PIP:</span> {{ $student->kip_pip_number ?? '-' }}</p>
                <p><span class="font-semibold text-slate-700">Kebutuhan khusus:</span> {{ $student->special_needs ?: 'Tidak Ada' }}</p>
                <p><span class="font-semibold text-slate-700">Disabilitas:</span> {{ $student->disability ?: 'Tidak Ada' }}</p>
                <p class="md:col-span-2"><span class="font-semibold text-slate-700">Alamat:</span> {{ $student->address ?? '-' }}</p>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-emerald-950">Wali Utama dan Daftar Wali</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse($student->guardians as $guardian)
                    <li>{{ $guardian->name }} - {{ $guardian->pivot->relationship }} @if($guardian->pivot->is_primary) <span class="font-semibold text-emerald-700">(Utama)</span> @endif</li>
                @empty
                    <li>Belum ada wali yang tertaut.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('students.guardians.store',$student) }}" class="mt-4 grid gap-3 md:grid-cols-4">
                @csrf
                <select name="guardian_id" class="md:col-span-2">@foreach($guardians as $guardian)<option value="{{ $guardian->id }}">{{ $guardian->name }}</option>@endforeach</select>
                <select name="relationship">@foreach($relationships as $rel)<option value="{{ $rel->value }}">{{ $rel->label() }}</option>@endforeach</select>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_primary" value="1"> Wali utama</label>
                <button class="btn btn-primary md:col-span-4">Tautkan</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-emerald-950">Riwayat Kelas</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse($student->enrollments as $enrollment)
                    <li>{{ $enrollment->academicYear?->name ?? '-' }} - {{ $enrollment->classroom?->name ?? '-' }} - {{ $enrollment->enrollment_status?->label() ?? '-' }}</li>
                @empty
                    <li>Belum ada riwayat kelas.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-emerald-950">Riwayat Status</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse($student->statusHistories as $history)
                    <li>{{ $history->effective_date?->format('d/m/Y') ?? '-' }}: {{ $history->previous_status?->label() ?? '-' }} → {{ $history->new_status?->label() ?? '-' }}</li>
                @empty
                    <li>Belum ada riwayat status.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('students.status.store',$student) }}" class="mt-4 grid gap-3 md:grid-cols-4">
                @csrf
                <select name="new_status">@foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>
                <input type="date" name="effective_date" value="{{ now()->toDateString() }}">
                <input name="reason" placeholder="Alasan" class="md:col-span-2">
                <button class="btn btn-primary md:col-span-4">Ubah Status</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-emerald-950">Dokumen</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse($student->documents as $document)
                    <li>{{ $document->document_type?->label() ?? '-' }} <a class="font-semibold text-emerald-700" href="{{ route('student-documents.download',$document) }}">Unduh</a></li>
                @empty
                    <li>Belum ada dokumen.</li>
                @endforelse
            </ul>
            <form method="POST" enctype="multipart/form-data" action="{{ route('students.documents.store',$student) }}" class="mt-4 grid gap-3 md:grid-cols-3">
                @csrf
                <select name="document_type">@foreach($documentTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>
                <input type="file" name="file" class="md:col-span-2">
                <button class="btn btn-primary md:col-span-3">Unggah</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-emerald-950">Activity Log</h2>
            <p class="mt-2 text-sm text-slate-600">Aktivitas terkait siswa dicatat melalui activity log sistem bila pengguna berwenang.</p>
        </section>
    </div>
</x-app-layout>
