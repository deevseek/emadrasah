<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\PayrollApprovalRequest;
use App\Http\Requests\Finance\PayrollCalculationRequest;
use App\Http\Requests\Finance\PayrollPaymentRequest;
use App\Http\Requests\Finance\PayrollPeriodRequest;
use App\Http\Requests\Finance\PayrollReopenRequest;
use App\Models\Finance\CashAccount;
use App\Models\Finance\PayrollPeriod;
use App\Services\Finance\PayrollCalculationService;
use App\Services\Finance\PayrollWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PayrollPeriodController extends Controller
{
    public function index(Request $request): View
    {
        $items = PayrollPeriod::query()
            ->withCount('payrolls')
            ->withSum('payrolls', 'net_salary')
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
            'title' => 'Periode Penggajian',
            'description' => 'Kelola periode dan jalankan workflow payroll secara berurutan.',
            'items' => $items,
            'headers' => ['Periode', 'Bulan/Tahun', 'Rentang', 'Pegawai', 'Total Netto', 'Status'],
            'rowBuilder' => fn (PayrollPeriod $period): array => [
                $period->name,
                str_pad((string) $period->month, 2, '0', STR_PAD_LEFT).'/'.$period->year,
                $period->starts_on?->format('d/m/Y').' - '.$period->ends_on?->format('d/m/Y'),
                (string) $period->payrolls_count,
                'Rp '.number_format((float) ($period->payrolls_sum_net_salary ?? 0), 0, ',', '.'),
                [
                    'value' => str($period->status)->title()->toString(),
                    'variant' => $this->statusVariant($period->status),
                ],
            ],
            'createRoute' => route('finance.payroll-periods.create'),
            'createLabel' => 'Tambah Periode',
            'showRouteName' => 'finance.payroll-periods.show',
            'editRouteName' => 'finance.payroll-periods.edit',
            'destroyRouteName' => 'finance.payroll-periods.destroy',
            'viewPermission' => 'payroll-periods.view',
            'managePermission' => 'payroll-periods.manage',
            'canEdit' => static fn (PayrollPeriod $period): bool => $period->status === PayrollStatus::Draft->value,
            'canDelete' => static fn (PayrollPeriod $period): bool => $period->status === PayrollStatus::Draft->value
                && $period->payrolls_count === 0,
            'searchPlaceholder' => 'Nama atau tahun payroll',
            'emptyTitle' => 'Belum ada periode payroll',
        ]);
    }

    public function create(): View
    {
        return $this->form(new PayrollPeriod);
    }

    public function store(PayrollPeriodRequest $request): RedirectResponse
    {
        $period = PayrollPeriod::create([
            ...$request->validated(),
            'status' => PayrollStatus::Draft->value,
            'created_by' => $request->user()->getKey(),
        ]);

        activity('payroll')
            ->performedOn($period)
            ->causedBy($request->user())
            ->event('payroll-period.created')
            ->log('Periode payroll dibuat');

        return redirect()
            ->route('finance.payroll-periods.show', $period)
            ->with('status', 'Periode payroll berhasil dibuat.');
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        $payrollPeriod->load([
            'creator',
            'payrolls.employee',
            'payrolls.reviewer',
            'payrolls.approver',
        ])->loadSum('payrolls', 'net_salary');

        $cashAccounts = CashAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('finance.payroll-periods.show', compact(
            'payrollPeriod',
            'cashAccounts',
        ));
    }

    public function edit(PayrollPeriod $payrollPeriod): View
    {
        $this->ensureDraft($payrollPeriod);

        return $this->form($payrollPeriod);
    }

    public function update(
        PayrollPeriodRequest $request,
        PayrollPeriod $payrollPeriod,
    ): RedirectResponse {
        $this->ensureDraft($payrollPeriod);
        $payrollPeriod->update($request->validated());

        activity('payroll')
            ->performedOn($payrollPeriod)
            ->causedBy($request->user())
            ->event('payroll-period.updated')
            ->log('Periode payroll diperbarui');

        return redirect()
            ->route('finance.payroll-periods.show', $payrollPeriod)
            ->with('status', 'Periode payroll berhasil diperbarui.');
    }

    public function destroy(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        abort_unless($request->user()?->can('payroll-periods.manage'), 403);
        $this->ensureDraft($payrollPeriod);

        if ($payrollPeriod->payrolls()->exists()) {
            throw ValidationException::withMessages([
                'period' => 'Periode yang sudah memiliki hasil payroll tidak dapat dihapus.',
            ]);
        }

        activity('payroll')
            ->performedOn($payrollPeriod)
            ->causedBy($request->user())
            ->event('payroll-period.deleted')
            ->log('Periode payroll dihapus');

        $payrollPeriod->delete();

        return redirect()
            ->route('finance.payroll-periods.index')
            ->with('status', 'Periode payroll berhasil dihapus.');
    }

    public function calculate(
        PayrollCalculationRequest $request,
        PayrollPeriod $payrollPeriod,
        PayrollCalculationService $service,
    ): RedirectResponse {
        $service->calculate($payrollPeriod);

        return back()->with('status', 'Payroll berhasil dihitung.');
    }

    public function review(
        PayrollApprovalRequest $request,
        PayrollPeriod $payrollPeriod,
        PayrollWorkflowService $service,
    ): RedirectResponse {
        $service->review($payrollPeriod);

        return back()->with('status', 'Payroll berhasil direview.');
    }

    public function approve(
        PayrollApprovalRequest $request,
        PayrollPeriod $payrollPeriod,
        PayrollWorkflowService $service,
    ): RedirectResponse {
        $service->approve($payrollPeriod);

        return back()->with('status', 'Payroll berhasil disetujui.');
    }

    public function pay(
        PayrollPaymentRequest $request,
        PayrollPeriod $payrollPeriod,
        PayrollWorkflowService $service,
    ): RedirectResponse {
        $service->markPaid(
            $payrollPeriod,
            (int) $request->validated('cash_account_id'),
        );

        return back()->with('status', 'Payroll berhasil dibayar dan jurnal kas keluar dibuat.');
    }

    public function close(
        PayrollApprovalRequest $request,
        PayrollPeriod $payrollPeriod,
        PayrollWorkflowService $service,
    ): RedirectResponse {
        $service->close($payrollPeriod);

        return back()->with('status', 'Payroll berhasil ditutup.');
    }

    public function reopen(
        PayrollReopenRequest $request,
        PayrollPeriod $payrollPeriod,
        PayrollWorkflowService $service,
    ): RedirectResponse {
        $service->reopen(
            $payrollPeriod,
            $request->validated('reason'),
        );

        return back()->with('status', 'Payroll dibuka kembali ke status paid.');
    }

    private function form(PayrollPeriod $payrollPeriod): View
    {
        return view('finance.generic.form', [
            'title' => $payrollPeriod->exists ? 'Edit Periode Payroll' : 'Tambah Periode Payroll',
            'description' => 'Periode hanya dapat diubah selama masih berstatus draft.',
            'action' => $payrollPeriod->exists
                ? route('finance.payroll-periods.update', $payrollPeriod)
                : route('finance.payroll-periods.store'),
            'method' => $payrollPeriod->exists ? 'PUT' : 'POST',
            'cancelRoute' => $payrollPeriod->exists
                ? route('finance.payroll-periods.show', $payrollPeriod)
                : route('finance.payroll-periods.index'),
            'submitLabel' => $payrollPeriod->exists ? 'Simpan Perubahan' : 'Buat Periode',
            'fields' => [
                [
                    'name' => 'name',
                    'label' => 'Nama Periode',
                    'required' => true,
                    'value' => $payrollPeriod->name,
                    'span' => 2,
                ],
                [
                    'name' => 'month',
                    'label' => 'Bulan',
                    'type' => 'number',
                    'required' => true,
                    'min' => 1,
                    'max' => 12,
                    'value' => $payrollPeriod->month ?? now()->month,
                ],
                [
                    'name' => 'year',
                    'label' => 'Tahun',
                    'type' => 'number',
                    'required' => true,
                    'min' => 2000,
                    'max' => 2100,
                    'value' => $payrollPeriod->year ?? now()->year,
                ],
                [
                    'name' => 'starts_on',
                    'label' => 'Tanggal Mulai',
                    'type' => 'date',
                    'required' => true,
                    'value' => $payrollPeriod->starts_on?->format('Y-m-d'),
                ],
                [
                    'name' => 'ends_on',
                    'label' => 'Tanggal Selesai',
                    'type' => 'date',
                    'required' => true,
                    'value' => $payrollPeriod->ends_on?->format('Y-m-d'),
                ],
                [
                    'name' => 'payment_date',
                    'label' => 'Rencana Tanggal Bayar',
                    'type' => 'date',
                    'value' => $payrollPeriod->payment_date?->format('Y-m-d'),
                    'span' => 2,
                ],
            ],
        ]);
    }

    private function ensureDraft(PayrollPeriod $payrollPeriod): void
    {
        if ($payrollPeriod->status !== PayrollStatus::Draft->value) {
            throw ValidationException::withMessages([
                'period' => 'Periode payroll hanya dapat diubah atau dihapus saat berstatus draft.',
            ]);
        }
    }

    private function statusVariant(string $status): string
    {
        return match ($status) {
            PayrollStatus::Paid->value,
            PayrollStatus::Closed->value => 'success',
            PayrollStatus::Approved->value,
            PayrollStatus::Reviewed->value => 'info',
            PayrollStatus::Calculated->value => 'warning',
            PayrollStatus::Cancelled->value => 'danger',
            default => 'muted',
        };
    }
}
