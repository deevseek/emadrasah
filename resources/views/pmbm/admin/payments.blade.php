<x-layouts.app title="Pembayaran SPMB" :breadcrumbs="['SPMB', 'Pembayaran']">
    <div class="index-stack">
        <x-ui.page-header title="Pembayaran SPMB" description="Register penerimaan dan verifikasi pembayaran Sistem Penerimaan Murid Baru.">
            @can('pmbm.applicant.view')
                <a class="btn btn-primary" href="{{ route('pmbm.applicants.index') }}">Pilih pendaftar untuk mencatat pembayaran</a>
            @endcan
        </x-ui.page-header>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach($statuses as $status)
                <x-ui.stat-card :label="$status->label()" :value="'Rp '.number_format((float) $summary[$status->value]['amount'], 0, ',', '.')">
                    <p class="mt-2 text-sm text-slate-500">{{ number_format($summary[$status->value]['count'], 0, ',', '.') }} transaksi</p>
                </x-ui.stat-card>
            @endforeach
        </div>
        <p class="text-sm text-slate-500">Ringkasan mengikuti filter. Hanya pembayaran terverifikasi yang diperhitungkan sebagai penerimaan dan pengurang sisa biaya pendaftar.</p>

        <x-ui.card>
            <form method="get" action="{{ route('pmbm.payments.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2"><label for="search" class="mb-1 block text-sm font-semibold">Cari pembayaran</label><input id="search" class="input w-full" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, nomor pendaftaran, atau referensi" maxlength="100"></div>
                <div><label for="academic_year_id" class="mb-1 block text-sm font-semibold">Tahun ajaran</label><select id="academic_year_id" class="input w-full" name="academic_year_id"><option value="">Semua tahun ajaran</option>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? '') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
                <div><label for="status" class="mb-1 block text-sm font-semibold">Status verifikasi</label><select id="status" class="input w-full" name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                <div><label for="payment_stage" class="mb-1 block text-sm font-semibold">Tahap pembayaran</label><select id="payment_stage" class="input w-full" name="payment_stage"><option value="">Semua tahap</option>@foreach($stages as $stage)<option value="{{ $stage->value }}" @selected(($filters['payment_stage'] ?? '') === $stage->value)>{{ $stage->label() }}</option>@endforeach</select></div>
                <div><label for="from" class="mb-1 block text-sm font-semibold">Tanggal pembayaran mulai</label><input id="from" class="input w-full" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
                <div><label for="until" class="mb-1 block text-sm font-semibold">Tanggal pembayaran sampai</label><input id="until" class="input w-full" type="date" name="until" value="{{ $filters['until'] ?? '' }}"></div>
                <div class="flex flex-wrap items-end gap-2"><button class="btn btn-primary">Terapkan filter</button><a class="btn btn-secondary" href="{{ route('pmbm.payments.index') }}">Atur ulang</a></div>
            </form>
        </x-ui.card>

        <x-ui.card class="overflow-hidden !p-0">
            <div class="border-b border-slate-200 p-4"><h2 class="font-bold text-emerald-950">Register pembayaran</h2><p class="mt-1 text-sm text-slate-500">{{ $payments->total() }} transaksi ditemukan. Periksa bukti dan kesesuaian nominal sebelum verifikasi.</p></div>
            <x-ui.table :headers="['No.', 'Tanggal pembayaran', 'Pendaftar', 'Tahap', 'Nominal', 'Metode / referensi', 'Status / petugas', 'Tindak lanjut']">
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payments->firstItem() + $loop->index }}</td>
                        <td class="whitespace-nowrap">{{ $payment->paid_at?->translatedFormat('d M Y') ?? '—' }}<small class="block text-slate-500">{{ $payment->paid_at?->format('H:i') }}</small></td>
                        <td><span class="font-semibold">{{ $payment->applicant->full_name }}</span><small class="block text-slate-500">{{ $payment->applicant->registration_number }}</small><small class="block text-slate-500">{{ $payment->applicant->academicYear?->name ?? '—' }}</small></td>
                        <td>{{ $payment->payment_stage->label() }}</td>
                        <td class="whitespace-nowrap text-right font-semibold">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                        <td>{{ ['cash' => 'Tunai', 'tunai' => 'Tunai', 'transfer' => 'Transfer bank', 'bank_transfer' => 'Transfer bank', 'qris' => 'QRIS'][$payment->payment_method] ?? $payment->payment_method }}<small class="block break-all text-slate-500">Referensi: {{ $payment->reference_number ?: '—' }}</small></td>
                        <td>
                            <x-ui.badge :variant="match($payment->status->value) { 'verified' => 'success', 'rejected' => 'danger', default => 'warning' }">{{ $payment->status->label() }}</x-ui.badge>
                            <small class="mt-2 block text-slate-500">Pencatat: {{ $payment->receiver?->name ?? 'Orang tua/wali' }}</small>
                            @if($payment->verified_at)<small class="block text-slate-500">Verifikator: {{ $payment->verifier?->name ?? '—' }}<br>{{ $payment->verified_at->translatedFormat('d M Y H:i') }}</small>@endif
                            @if($payment->verification_note)<p class="mt-1 text-sm">Catatan: {{ $payment->verification_note }}</p>@endif
                        </td>
                        <td><div class="flex flex-col items-start gap-2">
                            @if($payment->proof_path)<a class="font-semibold text-emerald-800 underline" href="{{ route('pmbm.payments.proof', [$payment->applicant, $payment]) }}">Unduh bukti</a>@else<span class="text-sm text-slate-500">Bukti belum dilampirkan</span>@endif
                            @can('pmbm.applicant.view')<a class="font-semibold text-emerald-800 underline" href="{{ route('pmbm.applicants.show', $payment->applicant) }}#pembayaran">Detail pendaftar</a>@endcan
                            @can('pmbm.payment.manage')
                                @if($payment->status === \App\Enums\Pmbm\PaymentStatus::Pending)
                                    <details><summary class="cursor-pointer font-semibold text-emerald-800">Verifikasi pembayaran</summary>
                                        <form class="mt-3 grid gap-2" method="post" action="{{ route('pmbm.payments.verify', [$payment->applicant, $payment]) }}">@csrf
                                            <label for="decision-{{ $payment->id }}">Keputusan</label><select id="decision-{{ $payment->id }}" class="input" name="decision" required><option value="">Pilih keputusan</option><option value="verified">Terverifikasi</option><option value="rejected">Ditolak</option></select>
                                            <label for="note-{{ $payment->id }}">Catatan verifikasi</label><textarea id="note-{{ $payment->id }}" class="input" name="verification_note" maxlength="1000" rows="3"></textarea>
                                            <button class="btn btn-primary">Simpan keputusan</button>
                                        </form>
                                    </details>
                                @endif
                            @endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="!py-12 text-center"><p class="font-semibold text-emerald-950">{{ count(array_filter($filters)) ? 'Tidak ada pembayaran yang sesuai filter.' : 'Belum ada transaksi pembayaran.' }}</p><p class="mt-2 text-sm text-slate-500">{{ count(array_filter($filters)) ? 'Ubah pencarian atau atur ulang filter untuk melihat transaksi lainnya.' : 'Pembayaran yang dicatat melalui detail pendaftar atau dikirim orang tua/wali akan tampil di sini untuk diverifikasi.' }}</p></td></tr>
                @endforelse
            </x-ui.table>
            <div class="p-4">{{ $payments->links() }}</div>
        </x-ui.card>
    </div>
</x-layouts.app>
