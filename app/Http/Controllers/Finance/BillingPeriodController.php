<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\BillingPeriodRequest;
use App\Models\AcademicYear;
use App\Models\Finance\BillingPeriod;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class BillingPeriodController extends Controller
{
    public function index(Request $request): View
    {
        $items = BillingPeriod::query()
            ->with(['academicYear', 'semester'])
            ->withCount('invoices')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('year', 'like', "%{$search}%");
                });
            })
            ->latest('year')
            ->latest('month')
            ->paginate(15)
            ->withQueryString();

        return view('finance.generic.index', [
            'title' => 'Periode Tagihan',
            'description' => 'Kelola periode penagihan bulanan, semester, atau insidental.',
            'items' => $items,
            'headers' => ['Nama', 'Tahun Ajaran', 'Semester', 'Bulan/Tahun', 'Tagihan', 'Status'],
            'rowBuilder' => static fn (BillingPeriod $period): array => [
                $period->name,
                $period->academicYear?->name ?? '-',
                $period->semester?->name ?? '-',
                ($period->month ? str_pad((string) $period->month, 2, '0', STR_PAD_LEFT).'/' : '').$period->year,
                (string) $period->invoices_count,
                [
                    'value' => $period->is_active ? 'Aktif' : 'Nonaktif',
                    'variant' => $period->is_active ? 'success' : 'muted',
                ],
            ],
            'createRoute' => route('finance.billing-periods.create'),
            'createLabel' => 'Tambah Periode',
            'showRouteName' => 'finance.billing-periods.show',
            'editRouteName' => 'finance.billing-periods.edit',
            'toggleRouteName' => 'finance.billing-periods.toggle',
            'destroyRouteName' => 'finance.billing-periods.destroy',
            'viewPermission' => 'billing-periods.view',
            'managePermission' => 'billing-periods.manage',
            'canDelete' => static fn (BillingPeriod $period): bool => $period->invoices_count === 0,
            'searchPlaceholder' => 'Nama atau tahun periode',
            'emptyTitle' => 'Belum ada periode tagihan',
        ]);
    }

    public function create(): View
    {
        return $this->form(new BillingPeriod);
    }

    public function store(BillingPeriodRequest $request): RedirectResponse
    {
        $period = BillingPeriod::create($request->validated());

        activity('student-finance')
            ->performedOn($period)
            ->causedBy($request->user())
            ->event('billing-period.created')
            ->log('Periode tagihan dibuat');

        return redirect()
            ->route('finance.billing-periods.show', $period)
            ->with('status', 'Periode tagihan dibuat.');
    }

    public function show(BillingPeriod $billingPeriod): View
    {
        $billingPeriod->load(['academicYear', 'semester'])->loadCount('invoices');

        return view('finance.generic.show', [
            'title' => 'Detail Periode Tagihan',
            'description' => $billingPeriod->name,
            'details' => [
                'Tahun Ajaran' => $billingPeriod->academicYear?->name,
                'Semester' => $billingPeriod->semester?->name ?? '-',
                'Bulan' => $billingPeriod->month ?? '-',
                'Tahun' => $billingPeriod->year,
                'Mulai' => $billingPeriod->starts_on?->format('d/m/Y') ?? '-',
                'Jatuh Tempo' => $billingPeriod->due_on?->format('d/m/Y') ?? '-',
                'Jumlah Tagihan' => $billingPeriod->invoices_count,
            ],
            'status' => [
                'label' => $billingPeriod->is_active ? 'Aktif' : 'Nonaktif',
                'variant' => $billingPeriod->is_active ? 'success' : 'muted',
            ],
            'indexRoute' => route('finance.billing-periods.index'),
            'editRoute' => route('finance.billing-periods.edit', $billingPeriod),
            'toggleRoute' => route('finance.billing-periods.toggle', $billingPeriod),
            'destroyRoute' => route('finance.billing-periods.destroy', $billingPeriod),
            'isActive' => $billingPeriod->is_active,
            'canDelete' => $billingPeriod->invoices_count === 0,
            'viewPermission' => 'billing-periods.view',
            'managePermission' => 'billing-periods.manage',
            'deleteConfirmation' => 'Hapus periode tagihan ini?',
        ]);
    }

    public function edit(BillingPeriod $billingPeriod): View
    {
        return $this->form($billingPeriod);
    }

    public function update(
        BillingPeriodRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $billingPeriod->update($request->validated());

        activity('student-finance')
            ->performedOn($billingPeriod)
            ->causedBy($request->user())
            ->event('billing-period.updated')
            ->log('Periode tagihan diperbarui');

        return redirect()
            ->route('finance.billing-periods.show', $billingPeriod)
            ->with('status', 'Periode tagihan diperbarui.');
    }

    public function toggle(Request $request, BillingPeriod $billingPeriod): RedirectResponse
    {
        abort_unless($request->user()?->can('billing-periods.manage'), 403);

        $billingPeriod->update(['is_active' => ! $billingPeriod->is_active]);

        activity('student-finance')
            ->performedOn($billingPeriod)
            ->causedBy($request->user())
            ->event($billingPeriod->is_active ? 'billing-period.activated' : 'billing-period.deactivated')
            ->log($billingPeriod->is_active ? 'Periode tagihan diaktifkan' : 'Periode tagihan dinonaktifkan');

        return back()->with('status', 'Status periode tagihan diperbarui.');
    }

    public function destroy(Request $request, BillingPeriod $billingPeriod): RedirectResponse
    {
        abort_unless($request->user()?->can('billing-periods.manage'), 403);

        if ($billingPeriod->invoices()->exists()) {
            throw ValidationException::withMessages([
                'billing_period' => 'Periode sudah digunakan oleh tagihan dan tidak dapat dihapus. Nonaktifkan sebagai gantinya.',
            ]);
        }

        activity('student-finance')
            ->performedOn($billingPeriod)
            ->causedBy($request->user())
            ->event('billing-period.deleted')
            ->log('Periode tagihan dihapus');

        $billingPeriod->delete();

        return redirect()
            ->route('finance.billing-periods.index')
            ->with('status', 'Periode tagihan dihapus.');
    }

    private function form(BillingPeriod $billingPeriod): View
    {
        $academicYears = AcademicYear::query()->latest('starts_on')->get();
        $semesters = Semester::query()->with('academicYear')->latest('starts_on')->get();

        return view('finance.generic.form', [
            'title' => $billingPeriod->exists ? 'Edit Periode Tagihan' : 'Tambah Periode Tagihan',
            'description' => 'Pastikan periode sesuai tahun ajaran dan semester yang berlaku.',
            'action' => $billingPeriod->exists
                ? route('finance.billing-periods.update', $billingPeriod)
                : route('finance.billing-periods.store'),
            'method' => $billingPeriod->exists ? 'PUT' : 'POST',
            'cancelRoute' => $billingPeriod->exists
                ? route('finance.billing-periods.show', $billingPeriod)
                : route('finance.billing-periods.index'),
            'submitLabel' => $billingPeriod->exists ? 'Simpan Perubahan' : 'Buat Periode',
            'fields' => [
                [
                    'name' => 'academic_year_id',
                    'label' => 'Tahun Ajaran',
                    'type' => 'select',
                    'required' => true,
                    'value' => $billingPeriod->academic_year_id,
                    'placeholder' => 'Pilih tahun ajaran',
                    'options' => $academicYears->map(fn (AcademicYear $year): array => [
                        'value' => $year->getKey(),
                        'label' => $year->name,
                    ])->all(),
                ],
                [
                    'name' => 'semester_id',
                    'label' => 'Semester',
                    'type' => 'select',
                    'value' => $billingPeriod->semester_id,
                    'placeholder' => 'Tanpa semester khusus',
                    'options' => $semesters->map(fn (Semester $semester): array => [
                        'value' => $semester->getKey(),
                        'label' => ($semester->academicYear?->name ?? '-').' — '.$semester->name,
                    ])->all(),
                ],
                [
                    'name' => 'month',
                    'label' => 'Bulan',
                    'type' => 'number',
                    'min' => 1,
                    'max' => 12,
                    'value' => $billingPeriod->month,
                    'help' => 'Kosongkan untuk periode nonbulanan.',
                ],
                [
                    'name' => 'year',
                    'label' => 'Tahun',
                    'type' => 'number',
                    'required' => true,
                    'min' => 2000,
                    'max' => 2100,
                    'value' => $billingPeriod->year ?? now()->year,
                ],
                [
                    'name' => 'name',
                    'label' => 'Nama Periode',
                    'required' => true,
                    'value' => $billingPeriod->name,
                    'span' => 2,
                ],
                [
                    'name' => 'starts_on',
                    'label' => 'Tanggal Mulai',
                    'type' => 'date',
                    'value' => $billingPeriod->starts_on?->format('Y-m-d'),
                ],
                [
                    'name' => 'due_on',
                    'label' => 'Jatuh Tempo',
                    'type' => 'date',
                    'value' => $billingPeriod->due_on?->format('Y-m-d'),
                ],
                [
                    'name' => 'is_active',
                    'label' => 'Periode Aktif',
                    'type' => 'checkbox',
                    'value' => $billingPeriod->exists ? $billingPeriod->is_active : true,
                    'help' => 'Periode aktif dapat dipilih saat membuat tagihan.',
                    'span' => 2,
                ],
            ],
        ]);
    }
}
