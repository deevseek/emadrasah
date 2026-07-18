<x-app-layout :title="$title">
<div class="space-y-6">

    @can('student-finance-dashboard.view')
        <section class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between">
                <div><h2 class="text-lg font-bold text-emerald-950">Keuangan Siswa</h2><p class="text-sm text-slate-600">Ringkasan operasional e-Madrasah berdasarkan data madrasah terkini.</p></div>
                <a href="{{ route('student-finance.dashboard') }}" class="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-semibold text-white">Buka Keuangan Siswa</a>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <x-ui.stat-card title="Pembayaran Hari Ini" :value="'Rp '.number_format((int) $studentFinanceTodayPayments, 0, ',', '.')" />
                <x-ui.stat-card title="Pemasukan Bulan Ini" :value="'Rp '.number_format((int) $studentFinanceMonthPayments, 0, ',', '.')" />
                <x-ui.stat-card title="Total Tunggakan" :value="'Rp '.number_format((int) $studentFinanceOutstanding, 0, ',', '.')" />
                <x-ui.stat-card title="Siswa Menunggak" :value="$studentFinanceArrearStudents" />
                <x-ui.stat-card title="Jatuh Tempo Hari Ini" :value="$studentFinanceDueToday" />
                <x-ui.stat-card title="Jatuh Tempo 7 Hari" :value="$studentFinanceDueSevenDays" />
                <x-ui.stat-card title="Transaksi Dibatalkan" :value="$studentFinanceCancelledMonth" />
            </div>
        </section>
    @endcan



    @can('payroll-dashboard.view')
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between">
                <div><h2 class="text-lg font-bold text-emerald-950">Payroll Pegawai</h2><p class="text-sm text-slate-600">Ringkasan operasional e-Madrasah berdasarkan data madrasah terkini.</p></div>
                <a href="{{ route('payroll.dashboard') }}" class="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-semibold text-white">Buka Payroll Pegawai</a>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <x-ui.stat-card title="Periode Payroll Aktif" :value="$payrollActivePeriod" />
                <x-ui.stat-card title="Pegawai Belum Profil" :value="$payrollEmployeesWithoutProfile" />
                <x-ui.stat-card title="Menunggu Persetujuan" :value="$payrollWaitingApproval" />
                <x-ui.stat-card title="Perlu Perbaikan" :value="$payrollNeedsRevision" />
                <x-ui.stat-card title="Payroll Bulan Ini" :value="'Rp '.number_format((int) $payrollMonthTotal, 0, ',', '.')" />
                <x-ui.stat-card title="Belum Dibayar" :value="$payrollUnpaidItems" />
                <x-ui.stat-card title="Dibayar Sebagian" :value="$payrollPartiallyPaidItems" />
                <x-ui.stat-card title="Penyesuaian Manual" :value="$payrollManualAdjustments" />
            </div>
        </section>
    @endcan

    @can('payslips.view-own')
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-bold text-emerald-950">Slip Gaji Saya</h2>
            <p class="text-sm text-slate-600">Ringkasan operasional e-Madrasah berdasarkan data madrasah terkini.</p>
            <p class="mt-3">{{ $employeeLatestPayslip?->payslip_number ?? 'Belum ada slip gaji final.' }} — {{ $employeeLatestPayslip?->payment_status ?? '-' }}</p>
            <a class="mt-3 inline-block rounded-xl bg-emerald-800 px-4 py-2 text-sm font-semibold text-white" href="{{ route('payroll.payslips.mine') }}">Lihat Slip</a>
        </section>
    @endcan

  <section class="rounded-3xl bg-emerald-950 p-6 text-white shadow-sm">
    <p class="text-sm text-emerald-100">Beranda e-Madrasah</p>
    <h2 class="mt-2 text-2xl font-black">{{ $profile->school_name }}</h2>
    <p class="mt-2 max-w-3xl text-sm text-emerald-50">{{ $moduleContext }}</p>
  </section>

  <section class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Tahun ajaran aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ $activeYear?->name ?? 'Belum ditetapkan' }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Semester aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ $activeSemester?->name ?? 'Belum ditetapkan' }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pengguna aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeUsers) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pegawai aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeEmployees) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('employees.index') }}">Buka Guru & Pegawai</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Siswa aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeStudents) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('students.index') }}">Buka Data Siswa</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Kelas aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeClassrooms) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('classrooms.index') }}">Buka Kelas & Rombel</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Kelas tanpa wali kelas</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($classroomsWithoutHomeroom) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Siswa belum ditempatkan</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeStudentsWithoutPlacement) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Kelas penuh/melebihi kapasitas</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($classroomsAtOrOverCapacity) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Mata pelajaran aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeSubjects) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('subjects.index') }}">Buka Mata Pelajaran</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Penugasan mengajar aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeTeachingAssignments) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('teaching-assignments.index') }}">Buka Penugasan</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Jadwal aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($activeSchedules) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('schedules.index') }}">Buka Jadwal</a></div></div>

    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pegawai hadir hari ini</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($employeesPresentToday) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('employee-attendances.index', ['date' => now()->toDateString()]) }}">Buka Kehadiran</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pegawai terlambat</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($employeesLateToday) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Belum check-out</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($employeesNotCheckedOutToday) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Izin/sakit hari ini</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($employeesOnLeaveToday) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Izin menunggu</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($pendingEmployeeLeaves) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('employee-leaves.approvals') }}">Buka Persetujuan</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Pegawai tanpa jadwal aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($employeesWithoutActiveWorkSchedule) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('work-schedules.index') }}">Atur Jadwal</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Jurnal menunggu verifikasi</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($teachingJournalPendingVerification) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('teaching-journals.index', ['status' => 'submitted']) }}">Buka Verifikasi</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Jurnal perlu perbaikan</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($teachingJournalNeedsRevision) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('teaching-journals.index', ['status' => 'rejected']) }}">Buka Jurnal</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Siswa hadir hari ini</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($studentsPresentToday) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('student-attendances.reports.index', ['status' => 'hadir', 'from' => now()->toDateString(), 'to' => now()->toDateString()]) }}">Buka laporan siswa</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Izin/Sakit/Alpha/Terlambat</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($studentsPermissionToday) }} / {{ number_format($studentsSickToday) }} / {{ number_format($studentsAlphaToday) }} / {{ number_format($studentsLateToday) }}</p></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Kelas belum mengisi absensi</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($studentAttendanceMissingClassesToday) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('student-attendances.missing') }}">Tinjau kelas</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Draft absensi siswa</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($studentAttendanceDraftsToday) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('student-attendances.index') }}">Buka absensi</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Jurnal hari ini belum diisi</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($teachingJournalUnfilledToday) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('teaching-journals.index') }}">Isi Jurnal</a></div></div>


    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Penugasan belum mengirim nilai</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($assessmentAssignmentsUnfilled) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('assessments.my-grades') }}">Buka Nilai Saya</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Nilai menunggu verifikasi</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($assessmentWaitingVerification) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('assessments.grade-verification') }}">Buka Verifikasi Nilai</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Rapor belum disusun</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($reportCardsUncompiled) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('assessments.report-class') }}">Buka Rapor Kelas Saya</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Rapor menunggu verifikasi/final</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($reportCardsWaitingVerification) }} / {{ number_format($reportCardsFinal) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('assessments.report-verification') }}">Buka Verifikasi Rapor</a></div></div>

    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Kelompok BTAQ aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($btaqActiveGroups) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('btaq-groups.index') }}">Buka Kelompok BTAQ</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Siswa BTAQ aktif</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($btaqActiveStudents) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('btaq-reports.index') }}">Buka Laporan BTAQ</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Sesi BTAQ hari ini</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($btaqSessionsToday) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('btaq-sessions.index') }}">Buka Jurnal BTAQ</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Sesi BTAQ belum diisi</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($btaqUnfilledSessions) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('btaq-reports.missing-sessions') }}">Tinjau Sesi</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Jurnal BTAQ menunggu verifikasi</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($btaqPendingVerification) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('btaq-verifications.index') }}">Verifikasi Jurnal</a></div></div>
    <div class="card"><div class="card-body"><p class="text-sm text-slate-500">Siswa perlu bimbingan BTAQ</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($btaqStudentsNeedGuidance) }}</p><a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('btaq.progress') }}">Buka Perkembangan</a></div></div>
  </section>

  <section class="card"><div class="card-body"><h2 class="text-lg font-bold text-emerald-950">Tindakan yang perlu diperhatikan</h2><div class="mt-4 grid gap-3">


    @if($btaqUnfilledSessions > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('btaq-reports.missing-sessions') }}">{{ $btaqUnfilledSessions }} sesi BTAQ belum diisi — Tinjau jadwal</a>@endif
    @if($btaqPendingVerification > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('btaq-verifications.index') }}">{{ $btaqPendingVerification }} jurnal BTAQ menunggu verifikasi — Verifikasi sekarang</a>@endif
    @if($btaqStudentsNeedGuidance > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('btaq.progress') }}">{{ $btaqStudentsNeedGuidance }} siswa memerlukan tindak lanjut BTAQ — Buka perkembangan</a>@endif
    @if($teachingJournalUnfilledToday > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('teaching-journals.index') }}">{{ $teachingJournalUnfilledToday }} jurnal hari ini belum diisi — Lengkapi jurnal</a>@endif
    @if($teachingJournalPendingVerification > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('teaching-journals.index', ['status' => 'submitted']) }}">{{ $teachingJournalPendingVerification }} jurnal menunggu verifikasi — Verifikasi sekarang</a>@endif
    @if($teachingJournalNeedsRevision > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('teaching-journals.index', ['status' => 'rejected']) }}">{{ $teachingJournalNeedsRevision }} jurnal perlu diperbaiki — Tindak lanjuti</a>@endif
    @if($studentAttendanceMissingClassesToday > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('student-attendances.missing') }}">{{ $studentAttendanceMissingClassesToday }} kelas belum mengisi absensi — Tindak lanjuti</a>@endif
    @if($studentAttendanceDraftsToday > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('student-attendances.index') }}">{{ $studentAttendanceDraftsToday }} absensi masih berupa draft — Finalisasi</a>@endif
    @if($studentsAlphaToday > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('student-attendances.reports.index', ['status' => 'alpha', 'from' => now()->toDateString(), 'to' => now()->toDateString()]) }}">{{ $studentsAlphaToday }} siswa alpha hari ini — Buka laporan</a>@endif
    @if($pendingEmployeeLeaves > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('employee-leaves.approvals') }}">{{ $pendingEmployeeLeaves }} pengajuan izin menunggu persetujuan — Proses sekarang</a>@endif
    @if($employeesNotCheckedOutToday > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('employee-attendances.index', ['date' => now()->toDateString()]) }}">{{ $employeesNotCheckedOutToday }} pegawai belum check-out — Pantau kehadiran</a>@endif
    @if($employeesWithoutActiveWorkSchedule > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('work-schedules.index') }}">{{ $employeesWithoutActiveWorkSchedule }} pegawai belum memiliki jadwal aktif — Atur jadwal kerja</a>@endif
    @if($unverifiedEmployeeAttendances > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('employee-attendances.index', ['verification_status' => 'pending']) }}">{{ $unverifiedEmployeeAttendances }} kehadiran menunggu verifikasi — Verifikasi kehadiran</a>@endif
    @unless($profileComplete)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('school-profile.edit') }}">Profil madrasah belum lengkap — Lengkapi sekarang</a>@endunless
    @unless($activeYear)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('academic-years.index') }}">Belum ada tahun ajaran aktif — Aktifkan tahun ajaran</a>@endunless
    @unless($activeSemester)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('semesters.index') }}">Belum ada semester aktif — Atur semester</a>@endunless
    @if($classroomsWithoutHomeroom > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('classrooms.index') }}">{{ $classroomsWithoutHomeroom }} kelas belum memiliki wali kelas — Atur wali kelas</a>@endif
    @if($activeStudentsWithoutPlacement > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('classrooms.index') }}">{{ $activeStudentsWithoutPlacement }} siswa belum ditempatkan — Lakukan penempatan kelas</a>@endif
    @if($subjectsWithoutTeacher > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('teaching-assignments.index') }}">{{ $subjectsWithoutTeacher }} mata pelajaran belum memiliki guru — Buat penugasan mengajar</a>@endif
    @if($assignmentsWithoutSchedule > 0)<a class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" href="{{ route('schedules.index') }}">{{ $assignmentsWithoutSchedule }} penugasan belum dijadwalkan — Susun jadwal pelajaran</a>@endif
    @if($profileComplete && $activeYear && $activeSemester && $classroomsWithoutHomeroom === 0 && $activeStudentsWithoutPlacement === 0 && $subjectsWithoutTeacher === 0 && $assignmentsWithoutSchedule === 0)<div class="empty-state">Tidak ada tindakan penting yang mendesak.</div>@endif
  </div></div></section>

  <section class="card"><div class="card-body"><h2 class="mb-4 text-lg font-bold text-emerald-950">Aktivitas fondasi terbaru</h2><div class="space-y-3">@forelse($latestActivities as $activity)<div class="rounded-xl bg-slate-50 p-3 text-sm"><p class="font-semibold text-slate-800">{{ $activity->description }}</p><p class="text-xs text-slate-500">{{ $activity->created_at?->diffForHumans() }}</p></div>@empty<div class="empty-state">Belum ada aktivitas perubahan fondasi.</div>@endforelse</div></div></section>
</div>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-bold text-emerald-950">Keuangan Operasional</h2>
            <p class="mt-1 text-sm text-slate-600">Ringkasan operasional e-Madrasah berdasarkan data madrasah terkini.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-4">
                <x-ui.stat-card title="Total Saldo Kas" :value="'Rp '.number_format((float) $operationalFinanceTotalBalance, 0, ',', '.')" />
                <x-ui.stat-card title="Pemasukan Hari Ini" :value="'Rp '.number_format((float) $operationalFinanceTodayIncome, 0, ',', '.')" />
                <x-ui.stat-card title="Pengeluaran Hari Ini" :value="'Rp '.number_format((float) $operationalFinanceTodayExpense, 0, ',', '.')" />
                <x-ui.stat-card title="Arus Bersih Bulan Ini" :value="'Rp '.number_format((float) $operationalFinanceMonthNet, 0, ',', '.')" />
            </div>
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <a class="rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-700" href="{{ route('operational-finance.approvals.index') }}">{{ $operationalFinancePendingApproval }} transaksi menunggu persetujuan</a>
                <a class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700" href="{{ route('operational-finance.reconciliations.index') }}">{{ $operationalFinanceCashDifferences }} akun kas memiliki selisih</a>
                <a class="rounded-full bg-rose-50 px-3 py-1 font-semibold text-rose-700" href="{{ route('operational-finance.reports.index') }}">{{ $operationalFinanceCancelled }} transaksi dibatalkan</a>
            </div>
        </section>

</x-app-layout>
