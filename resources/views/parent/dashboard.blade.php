<x-layouts.app title="Portal Orang Tua">
    <div class="mx-auto max-w-5xl space-y-5">
        @php
            $titles = [
                'dashboard' => ['Portal Orang Tua', 'Ringkasan kondisi anak hari ini.'],
                'children' => ['Anak Saya', 'Data anak yang terhubung dengan akun orang tua/wali.'],
                'schedule' => ['Jadwal & Kegiatan', 'Jadwal pelajaran dan kegiatan belajar anak.'],
                'attendance' => ['Absensi & Izin', 'Kehadiran dan riwayat pengajuan izin anak.'],
                'finance' => ['SPP & Pembayaran', 'Tagihan dan riwayat pembayaran siswa.'],
                'profile' => ['Profil Orang Tua/Wali', 'Informasi akun wali yang terhubung ke siswa.'],
            ];
            [$pageTitle, $pageSubtitle] = $titles[$section] ?? $titles['dashboard'];
        @endphp

        <x-ui.page-header :title="$pageTitle" :subtitle="$pageSubtitle" />

        @if($guardian === null)
            <x-ui.empty-state
                title="Akun belum terhubung sebagai orang tua/wali"
                description="Route Portal Orang Tua sudah aktif. Akun ini belum mempunyai profil guardian. Hubungkan akun ke data orang tua/wali dan siswa melalui administrator madrasah."
            />
        @else
            @if($children->count() > 1)
                <x-ui.card>
                    <form method="get" class="space-y-2">
                        <label for="student" class="block text-sm font-medium text-gray-700">Pilih anak</label>
                        <select id="student" name="student" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300">
                            @foreach($children as $child)
                                <option value="{{ $child->id }}" @selected($student?->id === $child->id)>{{ $child->full_name }}</option>
                            @endforeach
                        </select>
                    </form>
                </x-ui.card>
            @endif

            @if($section === 'profile')
                <x-ui.card>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div><dt class="text-sm text-gray-500">Nama</dt><dd class="font-semibold">{{ $guardian->name }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Nomor HP</dt><dd class="font-semibold">{{ $guardian->phone_number ?: 'Belum diisi' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Hubungan</dt><dd class="font-semibold">{{ $guardian->relationship ?: 'Wali' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Jumlah anak terhubung</dt><dd class="font-semibold">{{ $children->count() }}</dd></div>
                    </dl>
                </x-ui.card>
            @elseif($student === null)
                <x-ui.empty-state title="Belum ada anak terhubung" description="Profil wali sudah ada, tetapi belum dihubungkan dengan data siswa." />
            @elseif($section === 'children')
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach($children as $child)
                        <x-ui.card>
                            <h2 class="text-lg font-semibold">{{ $child->full_name }}</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ $child->nis ?? $child->nisn ?? 'NIS belum tersedia' }}</p>
                            <p class="mt-2">Kelas: <span class="font-medium">{{ $child->current_classroom_name }}</span></p>
                            <a href="{{ route('parent.dashboard', ['student' => $child->id]) }}" class="mt-4 inline-block text-sm font-semibold text-emerald-700">Lihat dashboard anak →</a>
                        </x-ui.card>
                    @endforeach
                </div>
            @elseif($section === 'schedule')
                <x-ui.card>
                    <h2 class="text-lg font-semibold">{{ $student->full_name }} · {{ $student->current_classroom_name }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($schedule['timeline'] as $lesson)
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <div class="font-semibold">{{ $lesson->subject?->name ?? 'Mata pelajaran' }}</div>
                                        <div class="text-sm text-gray-600">{{ substr($lesson->start_time, 0, 5) }}–{{ substr($lesson->end_time, 0, 5) }} · {{ $lesson->personnel?->full_name ?? 'Guru belum ditentukan' }}</div>
                                    </div>
                                    @if($schedule['current']?->id === $lesson->id)<x-ui.badge>SEDANG BERLANGSUNG</x-ui.badge>@endif
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600">Tidak ada jadwal pelajaran hari ini.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            @elseif($section === 'attendance')
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.card>
                        <h2 class="font-semibold">Kehadiran Hari Ini</h2>
                        <p class="mt-2 text-xl font-semibold">{{ $attendance?->status_label ?? ($attendance?->status ?? 'Belum Absen') }}</p>
                        <p class="text-sm text-gray-600">Sumber: {{ $attendance?->source ?? '—' }}</p>
                    </x-ui.card>
                    <x-ui.card>
                        <h2 class="font-semibold">Riwayat Izin</h2>
                        <p class="mt-2 text-sm text-gray-600">{{ $leaveRequests->count() }} pengajuan terakhir.</p>
                    </x-ui.card>
                </div>
                <x-ui.card>
                    <div class="space-y-3">
                        @forelse($leaveRequests as $leave)
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex justify-between gap-3">
                                    <div>
                                        <div class="font-semibold">{{ ucfirst($leave->leave_type) }}</div>
                                        <div class="text-sm text-gray-600">{{ $leave->start_date?->format('d/m/Y') }} - {{ $leave->end_date?->format('d/m/Y') }}</div>
                                        <div class="mt-1 text-sm">{{ $leave->reason }}</div>
                                    </div>
                                    <x-ui.badge>{{ strtoupper($leave->status) }}</x-ui.badge>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600">Belum ada pengajuan izin.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            @elseif($section === 'finance')
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.card><div class="text-sm text-gray-500">Total Tagihan</div><div class="mt-1 text-xl font-semibold">{{ $invoices->count() }}</div></x-ui.card>
                    <x-ui.card><div class="text-sm text-gray-500">Belum Lunas</div><div class="mt-1 text-xl font-semibold">{{ $invoices->whereNotIn('status', ['paid', 'cancelled'])->count() }}</div></x-ui.card>
                    <x-ui.card><div class="text-sm text-gray-500">Pembayaran Tercatat</div><div class="mt-1 text-xl font-semibold">{{ $payments->count() }}</div></x-ui.card>
                </div>
                <x-ui.card>
                    <h2 class="font-semibold">Tagihan SPP & Keuangan</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($invoices as $invoice)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                                <div>
                                    <div class="font-semibold">{{ $invoice->invoice_number }}</div>
                                    <div class="text-sm text-gray-600">Jatuh tempo {{ $invoice->due_date?->format('d/m/Y') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold">Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</div>
                                    <x-ui.badge>{{ strtoupper($invoice->status) }}</x-ui.badge>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600">Belum ada tagihan untuk siswa ini.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            @else
                <x-ui.card>
                    <h2 class="text-xl font-semibold text-emerald-950">{{ $student->full_name }}</h2>
                    <p>{{ $student->nis ?? $student->nisn ?? 'NIS belum tersedia' }} · {{ $student->current_classroom_name }}</p>
                </x-ui.card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.card>
                        <h3 class="font-semibold">Kehadiran Hari Ini</h3>
                        <p class="mt-2 text-lg">{{ $attendance?->status_label ?? ($attendance?->status ?? 'Belum Absen') }}</p>
                        <p class="text-sm text-gray-600">Sumber: {{ $attendance?->source ?? '—' }}</p>
                    </x-ui.card>
                    <x-ui.card>
                        <h3 class="font-semibold">Jadwal Saat Ini</h3>
                        @if($schedule['current'])
                            <p class="mt-2 text-lg">{{ $schedule['current']->subject?->name ?? 'Mata pelajaran' }}</p>
                            <x-ui.badge>SEDANG BERLANGSUNG</x-ui.badge>
                            <p>{{ substr($schedule['current']->start_time, 0, 5) }}–{{ substr($schedule['current']->end_time, 0, 5) }} · {{ $schedule['current']->personnel?->full_name ?? 'Guru belum ditentukan' }}</p>
                        @else
                            <p class="mt-2 text-gray-600">Tidak ada pelajaran yang sedang berlangsung.</p>
                        @endif
                    </x-ui.card>
                </div>
                <x-ui.card>
                    <h3 class="font-semibold">Pelajaran Selanjutnya</h3>
                    <p>{{ $schedule['next']?->subject?->name ?? 'Tidak ada jadwal selanjutnya hari ini.' }}</p>
                </x-ui.card>
            @endif
        @endif
    </div>
</x-layouts.app>
