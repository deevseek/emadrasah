@php
    $editing = $invoice->exists;
    $title = $editing ? 'Edit Tagihan' : 'Tambah Tagihan';
    $action = $editing
        ? route('finance.student-invoices.update', $invoice)
        : route('finance.student-invoices.store');
@endphp

@component('finance._page', [
    'title' => $title,
    'description' => 'Pilih siswa, periode, jenis tagihan, dan nominal yang sesuai.',
])
    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-5">
            Periksa kembali data form. Terdapat {{ $errors->count() }} kesalahan validasi.
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="student_id">Siswa <span class="text-rose-600">*</span></label>
                <select id="student_id" name="student_id" required>
                    <option value="">Pilih siswa</option>
                    @foreach ($students as $student)
                        <option
                            value="{{ $student->id }}"
                            @selected((string) old('student_id', $invoice->student_id) === (string) $student->id)
                        >
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
                @error('student_id')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="academic_year_id">Tahun Ajaran <span class="text-rose-600">*</span></label>
                <select id="academic_year_id" name="academic_year_id" required>
                    <option value="">Pilih tahun ajaran</option>
                    @foreach ($academicYears as $academicYear)
                        <option
                            value="{{ $academicYear->id }}"
                            @selected((string) old('academic_year_id', $invoice->academic_year_id) === (string) $academicYear->id)
                        >
                            {{ $academicYear->name }}
                        </option>
                    @endforeach
                </select>
                @error('academic_year_id')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="semester_id">Semester</label>
                <select id="semester_id" name="semester_id">
                    <option value="">Tanpa semester khusus</option>
                    @foreach ($semesters as $semester)
                        <option
                            value="{{ $semester->id }}"
                            @selected((string) old('semester_id', $invoice->semester_id) === (string) $semester->id)
                        >
                            {{ $semester->academicYear?->name }} — {{ $semester->name }}
                        </option>
                    @endforeach
                </select>
                @error('semester_id')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="billing_period_id">Periode Tagihan</label>
                <select id="billing_period_id" name="billing_period_id">
                    <option value="">Tanpa periode khusus</option>
                    @foreach ($billingPeriods as $billingPeriod)
                        <option
                            value="{{ $billingPeriod->id }}"
                            @selected((string) old('billing_period_id', $invoice->billing_period_id) === (string) $billingPeriod->id)
                        >
                            {{ $billingPeriod->name }} — {{ $billingPeriod->academicYear?->name }}
                        </option>
                    @endforeach
                </select>
                @error('billing_period_id')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5 md:col-span-2">
                <label for="fee_type_id">Jenis Tagihan <span class="text-rose-600">*</span></label>
                <select id="fee_type_id" name="fee_type_id" required>
                    <option value="">Pilih jenis tagihan</option>
                    @foreach ($feeTypes as $feeType)
                        <option
                            value="{{ $feeType->id }}"
                            @selected((string) old('fee_type_id', $invoice->fee_type_id) === (string) $feeType->id)
                        >
                            {{ $feeType->code }} — {{ $feeType->name }}
                        </option>
                    @endforeach
                </select>
                @error('fee_type_id')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input
                name="original_amount"
                label="Nominal Awal"
                type="number"
                min="0"
                step="0.01"
                :value="$invoice->original_amount"
                required
            />
            <x-ui.input
                name="discount_amount"
                label="Potongan"
                type="number"
                min="0"
                step="0.01"
                :value="$invoice->discount_amount ?? 0"
            />
            <x-ui.input
                name="penalty_amount"
                label="Denda"
                type="number"
                min="0"
                step="0.01"
                :value="$invoice->penalty_amount ?? 0"
            />
            <x-ui.input
                name="due_on"
                label="Jatuh Tempo"
                type="date"
                :value="$invoice->due_on?->format('Y-m-d')"
            />

            <div class="md:col-span-2">
                <x-ui.textarea
                    name="description"
                    label="Keterangan"
                    :value="$invoice->description"
                    rows="3"
                />
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
            <x-ui.button
                type="button"
                variant="secondary"
                :href="$editing
                    ? route('finance.student-invoices.show', $invoice)
                    : route('finance.student-invoices.index')"
            >
                Batal
            </x-ui.button>
            <x-ui.button>{{ $editing ? 'Simpan Perubahan' : 'Buat Tagihan' }}</x-ui.button>
        </div>
    </form>
@endcomponent
