@php
    $badgeVariant = match ($invoice->status) {
        'paid' => 'success',
        'overdue' => 'danger',
        'partially_paid' => 'warning',
        'cancelled' => 'muted',
        default => 'info',
    };
@endphp

@component('finance._page', [
    'title' => 'Detail Tagihan',
    'description' => $invoice->invoice_number,
])
    @if (session('status'))
        <x-ui.alert class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-5">
            {{ $errors->first() }}
        </x-ui.alert>
    @endif

    <div class="grid gap-5 lg:grid-cols-[1fr_360px]">
        <div class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Siswa' => $invoice->student?->name,
                    'Kelas' => $invoice->classroom?->name,
                    'Tahun Ajaran' => $invoice->academicYear?->name,
                    'Semester' => $invoice->semester?->name ?? '-',
                    'Periode Tagihan' => $invoice->billingPeriod?->name ?? '-',
                    'Jenis Tagihan' => $invoice->feeType?->name,
                    'Jatuh Tempo' => $invoice->due_on?->format('d/m/Y') ?? '-',
                    'Dibuat Oleh' => $invoice->generator?->name ?? '-',
                ] as $label => $value)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-1 font-semibold text-slate-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div>
                <h3 class="mb-3 text-base font-bold text-emerald-950">Riwayat Alokasi Pembayaran</h3>
                @if ($invoice->allocations->isEmpty())
                    <x-ui.empty-state
                        title="Belum ada pembayaran"
                        description="Tagihan ini belum menerima alokasi pembayaran."
                    />
                @else
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>Nomor Pembayaran</th>
                                <th>Tanggal</th>
                                <th>Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->allocations as $allocation)
                                <tr>
                                    <td>
                                        <a
                                            class="font-semibold text-emerald-700 hover:underline"
                                            href="{{ route('finance.student-payments.show', $allocation->payment) }}"
                                        >
                                            {{ $allocation->payment?->payment_number }}
                                        </a>
                                    </td>
                                    <td>{{ $allocation->payment?->payment_date?->format('d/m/Y') }}</td>
                                    <td>Rp {{ number_format((float) $allocation->amount, 0, ',', '.') }}</td>
                                    <td>{{ str($allocation->payment?->status)->replace('_', ' ')->title() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </div>
        </div>

        <aside class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-600">Status</p>
                    <x-ui.badge :variant="$badgeVariant">
                        {{ str($invoice->status)->replace('_', ' ')->title() }}
                    </x-ui.badge>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Nominal Awal</dt>
                        <dd class="font-semibold">Rp {{ number_format((float) $invoice->original_amount, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Potongan</dt>
                        <dd class="font-semibold">Rp {{ number_format((float) $invoice->discount_amount, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Denda</dt>
                        <dd class="font-semibold">Rp {{ number_format((float) $invoice->penalty_amount, 0, ',', '.') }}</dd>
                    </div>
                    <div class="border-t border-slate-200 pt-3">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Total Tagihan</dt>
                            <dd class="font-bold">Rp {{ number_format((float) $invoice->final_amount, 0, ',', '.') }}</dd>
                        </div>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Sudah Dibayar</dt>
                        <dd class="font-semibold text-emerald-700">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Sisa</dt>
                        <dd class="font-black text-rose-700">Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            @if ($invoice->description)
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $invoice->description }}</p>
                </div>
            @endif

            <div class="flex flex-col gap-2">
                <x-ui.button
                    type="button"
                    variant="secondary"
                    :href="route('finance.student-invoices.index')"
                >
                    Kembali
                </x-ui.button>

                @can('student-invoices.update')
                    @if ((float) $invoice->paid_amount === 0.0 && $invoice->status !== 'cancelled')
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            :href="route('finance.student-invoices.edit', $invoice)"
                        >
                            Edit Tagihan
                        </x-ui.button>
                    @endif
                @endcan
            </div>

            @can('student-invoices.cancel')
                @if ((float) $invoice->paid_amount === 0.0 && $invoice->status !== 'cancelled')
                    <form
                        method="POST"
                        action="{{ route('finance.student-invoices.cancel', $invoice) }}"
                        class="space-y-3 rounded-xl border border-rose-200 bg-rose-50 p-4"
                        onsubmit="return confirm('Batalkan tagihan ini?')"
                    >
                        @csrf
                        @method('PATCH')
                        <x-ui.textarea
                            name="reason"
                            label="Alasan Pembatalan"
                            rows="3"
                            required
                        />
                        <x-ui.button variant="danger" class="w-full">Batalkan Tagihan</x-ui.button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
@endcomponent
