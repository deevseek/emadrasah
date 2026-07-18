<x-app-layout :title="$title">
    <div class="space-y-6">
        @include('payroll.partials.tabs')

        <form method="post" action="{{ $payrollComponent->exists ? route('payroll.components.update', $payrollComponent) : route('payroll.components.store') }}" class="card">
            <div class="card-body grid gap-4 md:grid-cols-2">
                @csrf
                @if($payrollComponent->exists)
                    @method('put')
                @endif

                <label>Nama<input name="name" class="input" value="{{ old('name', $payrollComponent->name) }}"></label>
                <label>Kode<input name="code" class="input" value="{{ old('code', $payrollComponent->code) }}"></label>
                <label>Jenis<select name="component_type" class="input"><option value="earning">Penghasilan</option><option value="deduction">Potongan</option></select></label>
                <label>Metode<select name="calculation_type" class="input"><option value="fixed">Nominal Tetap</option><option value="percentage">Persentase</option><option value="attendance">Berdasarkan Kehadiran</option><option value="manual">Manual</option><option value="controlled_formula">Formula Terkontrol</option></select></label>
                <label>Nominal Default<input name="default_amount" type="number" class="input" value="{{ old('default_amount', $payrollComponent->default_amount ?? 0) }}"></label>
                <label>Catatan<textarea name="notes" class="input">{{ old('notes', $payrollComponent->notes) }}</textarea></label>
                <button class="btn-primary md:col-span-2">Simpan Komponen Payroll</button>
            </div>
        </form>
    </div>
</x-app-layout>
