<x-app-layout :title="$title">
    <div class="space-y-6">
        @include('payroll.partials.tabs')

        <div class="card">
            <div class="card-body">
                <h2 class="text-xl font-bold">{{ $payrollComponent->name }}</h2>
                <p>Kode: {{ $payrollComponent->code }}</p>
                <p>Nominal: Rp {{ number_format((int) $payrollComponent->default_amount, 0, ',', '.') }}</p>

                @can('payroll-components.manage')
                    <a href="{{ route('payroll.components.edit', $payrollComponent) }}">Edit</a>
                    <form method="post" action="{{ route('payroll.components.toggle', $payrollComponent) }}">
                        @csrf
                        @method('patch')
                        <button>Aktif/Nonaktif</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
