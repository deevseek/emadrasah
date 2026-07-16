<x-app-layout title="Penempatan Kelas">
    <div class="space-y-6">
        <div class="flex justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-emerald-950">Penempatan Kelas</h2>
                <p class="text-sm text-slate-500">Pantau dan kelola siswa pada rombel aktif.</p>
            </div>
            @can('student-enrollments.create')
                <a class="btn btn-primary" href="{{ route('student-enrollments.create') }}">Tempatkan Siswa</a>
            @endcan
        </div>

        @if (request()->routeIs('student-enrollments.create'))
            <form method="post" action="{{ route('student-enrollments.store') }}" class="card card-body grid gap-3 md:grid-cols-3">
                @csrf
                <label>
                    <span>Siswa</span>
                    <select name="student_id">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected((int) old('student_id') === $student->id)>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('student_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Tahun Ajaran</span>
                    <select name="academic_year_id">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected((int) old('academic_year_id') === $year->id)>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Kelas</span>
                    <select name="classroom_id">
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((int) old('classroom_id') === $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('classroom_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <div class="md:col-span-3 flex gap-3">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('student-enrollments.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        @endif

        <form class="card card-body grid gap-3 md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Cari siswa atau NIS" class="md:col-span-2">
            <button class="btn btn-primary">Filter</button>
        </form>

        @if ($enrollments->isEmpty())
            <div class="empty-state">Belum ada penempatan kelas.</div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>NIS</th>
                            <th>Tahun Ajaran</th>
                            <th>Kelas</th>
                            <th>Tanggal Masuk</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollments as $enrollment)
                            <tr>
                                <td class="font-semibold">{{ $enrollment->student?->name }}</td>
                                <td>{{ $enrollment->student?->student_number }}</td>
                                <td>{{ $enrollment->academicYear?->name }}</td>
                                <td>{{ $enrollment->classroom?->name }}</td>
                                <td>{{ $enrollment->enrolled_at?->format('d/m/Y') }}</td>
                                <td><span class="badge badge-success">{{ str($enrollment->enrollment_status)->headline() }}</span></td>
                                <td class="flex gap-2">
                                    @can('student-enrollments.transfer')
                                        <button class="btn btn-secondary px-3 py-1.5">Transfer</button>
                                    @endcan
                                    @can('student-enrollments.delete')
                                        <form method="post" action="{{ route('student-enrollments.destroy', $enrollment) }}" onsubmit="return confirm('Keluarkan siswa dari kelas ini?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-danger px-3 py-1.5">Keluarkan</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $enrollments->links() }}
        @endif
    </div>
</x-app-layout>
