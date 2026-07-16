@component('finance._page', [
    'title' => 'Daftar Tagihan',
    'description' => 'Pantau tagihan siswa, sisa pembayaran, jatuh tempo, dan status workflow.',
])
    @if (session('status'))
        <x-ui.alert class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-4">
            <x-ui.input
                name="search"
                label="Pencarian"
                :value="request('search')"
                placeholder="Nomor tagihan atau siswa"
            />

            <div class="space-y-1.5">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach (['unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ str($status)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="student_id">Siswa</label>
                <select id="student_id" name="student_id">
                    <option value="">Semua siswa</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <x-ui.button>Terapkan</x-ui.button>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="route('finance.student-invoices.index')"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>

        @can('student-invoices.create')
            <x-ui.button
                type="button"
                :href="route('finance.student-invoices.create')"
            >
                Tambah Tagihan
            </x-ui.button>
        @endcan
    </div>

    @if ($invoices->isEmpty())
        <x-ui.empty-state
            title="Belum ada tagihan"
            description="Buat tagihan pertama untuk mulai mencatat kewajiban pembayaran siswa."
        />
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Siswa</th>
                    <th>Jenis</th>
                    <th>Periode</th>
                    <th>Jatuh Tempo</th>
                    <th>Nilai</th>
                    <th>Sisa</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    @php
                        $badgeVariant = match ($invoice->status) {
                            'paid' => 'success',
                            'overdue' => 'danger',
                            'partially_paid' => 'warning',
                            'cancelled' => 'muted',
                            default => 'info',
                        };
                    @endphp
                    <tr>
                        <td class="font-semibold text-emerald-900">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->student?->name }}</td>
                        <td>{{ $invoice->feeType?->name }}</td>
                        <td>{{ $invoice->billingPeriod?->name ?? '-' }}</td>
                        <td>{{ $invoice->due_on?->format('d/m/Y') ?? '-' }}</td>
                        <td>Rp {{ number_format((float) $invoice->final_amount, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</td>
                        <td>
                            <x-ui.badge :variant="$badgeVariant">
                                {{ str($invoice->status)->replace('_', ' ')->title() }}
                            </x-ui.badge>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    class="btn btn-secondary px-3 py-1.5"
                                    href="{{ route('finance.student-invoices.show', $invoice) }}"
                                >
                                    Detail
                                </a>
                                @can('student-invoices.update')
                                    @if ((float) $invoice->paid_amount === 0.0 && $invoice->status !== 'cancelled')
                                        <a
                                            class="btn btn-secondary px-3 py-1.5"
                                            href="{{ route('finance.student-invoices.edit', $invoice) }}"
                                        >
                                            Edit
                                        </a>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <x-ui.pagination :paginator="$invoices" />
    @endif
@endcomponent
