<x-app-layout :title="($model->exists ? 'Edit ' : 'Tambah ').$title">
    <form
        method="post"
        action="{{ $model->exists ? route($key.'.update', $model) : route($key.'.store') }}"
        @if ($key === 'employees')
            enctype="multipart/form-data"
        @endif
        class="max-w-4xl space-y-5 rounded-xl border bg-white p-6"
    >
        @csrf

        @if ($model->exists)
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            @if ($key === 'grade-levels')
                <label>
                    <span>Nama</span>
                    <input name="name" value="{{ old('name', $model->name) }}" class="w-full rounded-lg border-slate-300">
                    @error('name')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Kode</span>
                    <input name="code" value="{{ old('code', $model->code) }}" class="w-full rounded-lg border-slate-300">
                    @error('code')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Level</span>
                    <input type="number" min="1" name="level" value="{{ old('level', $model->level) }}" class="w-full rounded-lg border-slate-300">
                    @error('level')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="md:col-span-2">
                    <span>Deskripsi</span>
                    <textarea name="description" class="w-full rounded-lg border-slate-300">{{ old('description', $model->description) }}</textarea>
                    @error('description')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            @elseif ($key === 'subjects')
                <label>
                    <span>Kode</span>
                    <input name="code" value="{{ old('code', $model->code) }}" class="w-full rounded-lg border-slate-300">
                    @error('code')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Nama</span>
                    <input name="name" value="{{ old('name', $model->name) }}" class="w-full rounded-lg border-slate-300">
                    @error('name')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Kategori</span>
                    <select name="category" class="w-full rounded-lg border-slate-300">
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}" @selected(old('category', $model->category?->value) === $category->value)>
                                {{ $category->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>KKM</span>
                    <input type="number" min="0" max="100" name="minimum_passing_grade" value="{{ old('minimum_passing_grade', $model->minimum_passing_grade) }}" class="w-full rounded-lg border-slate-300">
                    @error('minimum_passing_grade')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="md:col-span-2">
                    <span>Deskripsi</span>
                    <textarea name="description" class="w-full rounded-lg border-slate-300">{{ old('description', $model->description) }}</textarea>
                    @error('description')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            @elseif ($key === 'employees')
                <label>
                    <span>Akun Login</span>
                    <select name="user_id" class="w-full rounded-lg border-slate-300">
                        <option value="">Tanpa akun</option>
                        @if ($model->user)
                            <option value="{{ $model->user_id }}" @selected((int) old('user_id', $model->user_id) === $model->user_id)>
                                {{ $model->user->name }} - {{ $model->user->email }}
                            </option>
                        @endif
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) old('user_id', $model->user_id) === $user->id)>
                                {{ $user->name }} - {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Nama</span>
                    <input name="name" value="{{ old('name', $model->name) }}" class="w-full rounded-lg border-slate-300">
                    @error('name')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Nomor Pegawai</span>
                    <input name="employee_number" value="{{ old('employee_number', $model->employee_number) }}" class="w-full rounded-lg border-slate-300">
                    @error('employee_number')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>NIK</span>
                    <input name="national_identity_number" value="{{ old('national_identity_number', $model->national_identity_number) }}" class="w-full rounded-lg border-slate-300">
                    @error('national_identity_number')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Gender</span>
                    <select name="gender" class="w-full rounded-lg border-slate-300">
                        @foreach ($genders as $gender)
                            <option value="{{ $gender->value }}" @selected(old('gender', $model->gender?->value) === $gender->value)>
                                {{ $gender->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('gender')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Jenis Pegawai</span>
                    <select name="employment_type" class="w-full rounded-lg border-slate-300">
                        @foreach ($employmentTypes as $employmentType)
                            <option value="{{ $employmentType->value }}" @selected(old('employment_type', $model->employment_type?->value) === $employmentType->value)>
                                {{ $employmentType->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('employment_type')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Status</span>
                    <select name="employee_status" class="w-full rounded-lg border-slate-300">
                        @foreach ($employeeStatuses as $employeeStatus)
                            <option value="{{ $employeeStatus->value }}" @selected(old('employee_status', $model->employee_status?->value) === $employeeStatus->value)>
                                {{ $employeeStatus->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_status')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Foto</span>
                    <input type="file" name="photo" class="w-full rounded-lg border-slate-300">
                    @error('photo')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Email</span>
                    <input name="email" value="{{ old('email', $model->email) }}" class="w-full rounded-lg border-slate-300">
                    @error('email')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Telepon</span>
                    <input name="phone" value="{{ old('phone', $model->phone) }}" class="w-full rounded-lg border-slate-300">
                    @error('phone')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="md:col-span-2">
                    <span>Alamat</span>
                    <textarea name="address" class="w-full rounded-lg border-slate-300">{{ old('address', $model->address) }}</textarea>
                    @error('address')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            @elseif ($key === 'classrooms')
                <label>
                    <span>Tahun Ajaran</span>
                    <select name="academic_year_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected((int) old('academic_year_id', $model->academic_year_id) === $year->id)>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Tingkat</span>
                    <select name="grade_level_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($gradeLevels as $gradeLevel)
                            <option value="{{ $gradeLevel->id }}" @selected((int) old('grade_level_id', $model->grade_level_id) === $gradeLevel->id)>
                                {{ $gradeLevel->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_level_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Nama</span>
                    <input name="name" value="{{ old('name', $model->name) }}" class="w-full rounded-lg border-slate-300">
                    @error('name')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Kode</span>
                    <input name="code" value="{{ old('code', $model->code) }}" class="w-full rounded-lg border-slate-300">
                    @error('code')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Kapasitas</span>
                    <input type="number" name="capacity" value="{{ old('capacity', $model->capacity) }}" class="w-full rounded-lg border-slate-300">
                    @error('capacity')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Wali Kelas</span>
                    <select name="homeroom_teacher_id" class="w-full rounded-lg border-slate-300">
                        <option value="">-</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) old('homeroom_teacher_id', $model->homeroom_teacher_id) === $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('homeroom_teacher_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Ruang</span>
                    <input name="room" value="{{ old('room', $model->room) }}" class="w-full rounded-lg border-slate-300">
                    @error('room')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            @elseif ($key === 'teaching-assignments')
                <label>
                    <span>Tahun Ajaran</span>
                    <select name="academic_year_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected((int) old('academic_year_id', $model->academic_year_id) === $year->id)>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Semester</span>
                    <select name="semester_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((int) old('semester_id', $model->semester_id) === $semester->id)>
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('semester_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Guru</span>
                    <select name="employee_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) old('employee_id', $model->employee_id) === $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Kelas</span>
                    <select name="classroom_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((int) old('classroom_id', $model->classroom_id) === $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('classroom_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Mata Pelajaran</span>
                    <select name="subject_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((int) old('subject_id', $model->subject_id) === $subject->id)>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Jam/Minggu</span>
                    <input type="number" name="weekly_hours" value="{{ old('weekly_hours', $model->weekly_hours) }}" class="w-full rounded-lg border-slate-300">
                    @error('weekly_hours')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            @elseif ($key === 'schedules')
                <label>
                    <span>Tahun Ajaran</span>
                    <select name="academic_year_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected((int) old('academic_year_id', $model->academic_year_id) === $year->id)>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Semester</span>
                    <select name="semester_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((int) old('semester_id', $model->semester_id) === $semester->id)>
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('semester_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Guru</span>
                    <select name="employee_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) old('employee_id', $model->employee_id) === $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Kelas</span>
                    <select name="classroom_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((int) old('classroom_id', $model->classroom_id) === $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('classroom_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Mata Pelajaran</span>
                    <select name="subject_id" class="w-full rounded-lg border-slate-300">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((int) old('subject_id', $model->subject_id) === $subject->id)>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Hari</span>
                    <select name="day_of_week" class="w-full rounded-lg border-slate-300">
                        @foreach ($days as $day)
                            <option value="{{ $day->value }}" @selected(old('day_of_week', $model->day_of_week?->value) === $day->value)>
                                {{ $day->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('day_of_week')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Mulai</span>
                    <input type="time" name="starts_at" value="{{ old('starts_at', $model->starts_at) }}" class="w-full rounded-lg border-slate-300">
                    @error('starts_at')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Selesai</span>
                    <input type="time" name="ends_at" value="{{ old('ends_at', $model->ends_at) }}" class="w-full rounded-lg border-slate-300">
                    @error('ends_at')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>

                <label>
                    <span>Ruang</span>
                    <input name="room" value="{{ old('room', $model->room) }}" class="w-full rounded-lg border-slate-300">
                    @error('room')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </label>
            @else
                <p class="md:col-span-2 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    Resource akademik tidak dikenali.
                </p>
            @endif

            <label class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $model->is_active ?? true))>
                <span>Aktif</span>
                @error('is_active')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </label>
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-emerald-950 px-4 py-2 font-semibold text-white">Simpan</button>
            <a href="{{ route($key.'.index') }}" class="rounded-lg border px-4 py-2">Batal</a>
        </div>
    </form>
</x-app-layout>
