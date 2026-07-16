<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\SalaryCalculationType;
use App\Enums\Finance\SalaryComponentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SalaryComponentRequest;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class SalaryComponentController extends Controller
{
    public function index(Request $request): View
    {
        $items = SalaryComponent::query()
            ->with(['expenseAccount', 'payableAccount'])
            ->withCount(['employeeComponents', 'payrollItems'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('finance.generic.index', [
            'title' => 'Komponen Gaji',
            'description' => 'Kelola komponen pendapatan dan potongan yang membentuk payroll pegawai.',
            'items' => $items,
            'headers' => ['Kode', 'Nama', 'Jenis', 'Perhitungan', 'Nilai Default', 'Pegawai', 'Status'],
            'rowBuilder' => static fn (SalaryComponent $component): array => [
                $component->code,
                $component->name,
                $component->component_type === SalaryComponentType::Earning->value ? 'Pendapatan' : 'Potongan',
                str($component->calculation_type)->title()->toString(),
                $component->calculation_type === SalaryCalculationType::Percentage->value
                    ? rtrim(rtrim((string) $component->percentage, '0'), '.').' %'
                    : 'Rp '.number_format((float) $component->default_amount, 0, ',', '.'),
                (string) $component->employee_components_count,
                [
                    'value' => $component->is_active ? 'Aktif' : 'Nonaktif',
                    'variant' => $component->is_active ? 'success' : 'muted',
                ],
            ],
            'createRoute' => route('finance.salary-components.create'),
            'createLabel' => 'Tambah Komponen',
            'showRouteName' => 'finance.salary-components.show',
            'editRouteName' => 'finance.salary-components.edit',
            'toggleRouteName' => 'finance.salary-components.toggle',
            'destroyRouteName' => 'finance.salary-components.destroy',
            'viewPermission' => 'salary-components.view',
            'managePermission' => 'salary-components.manage',
            'canDelete' => static fn (SalaryComponent $component): bool => $component->employee_components_count === 0
                && $component->payroll_items_count === 0,
            'searchPlaceholder' => 'Kode atau nama komponen',
            'emptyTitle' => 'Belum ada komponen gaji',
        ]);
    }

    public function create(): View
    {
        return $this->form(new SalaryComponent);
    }

    public function store(SalaryComponentRequest $request): RedirectResponse
    {
        $component = SalaryComponent::create($request->validated());

        activity('payroll')
            ->performedOn($component)
            ->causedBy($request->user())
            ->event('salary-component.created')
            ->log('Komponen gaji dibuat');

        return redirect()
            ->route('finance.salary-components.show', $component)
            ->with('status', 'Komponen gaji berhasil dibuat.');
    }

    public function show(SalaryComponent $salaryComponent): View
    {
        $salaryComponent->load(['expenseAccount', 'payableAccount'])->loadCount([
            'employeeComponents',
            'payrollItems',
        ]);

        return view('finance.generic.show', [
            'title' => 'Detail Komponen Gaji',
            'description' => $salaryComponent->code.' — '.$salaryComponent->name,
            'details' => [
                'Kode' => $salaryComponent->code,
                'Nama' => $salaryComponent->name,
                'Jenis' => $salaryComponent->component_type === SalaryComponentType::Earning->value
                    ? 'Pendapatan'
                    : 'Potongan',
                'Metode Perhitungan' => str($salaryComponent->calculation_type)->title(),
                'Nominal Default' => 'Rp '.number_format((float) $salaryComponent->default_amount, 0, ',', '.'),
                'Persentase' => $salaryComponent->percentage
                    ? rtrim(rtrim((string) $salaryComponent->percentage, '0'), '.').' %'
                    : '-',
                'Berbasis Absensi' => $salaryComponent->is_attendance_based ? 'Ya' : 'Tidak',
                'Kena Pajak' => $salaryComponent->taxable ? 'Ya' : 'Tidak',
                'Akun Beban' => $salaryComponent->expenseAccount
                    ? $salaryComponent->expenseAccount->code.' — '.$salaryComponent->expenseAccount->name
                    : '-',
                'Akun Utang' => $salaryComponent->payableAccount
                    ? $salaryComponent->payableAccount->code.' — '.$salaryComponent->payableAccount->name
                    : '-',
                'Struktur Pegawai' => $salaryComponent->employee_components_count,
                'Item Payroll Historis' => $salaryComponent->payroll_items_count,
            ],
            'status' => [
                'label' => $salaryComponent->is_active ? 'Aktif' : 'Nonaktif',
                'variant' => $salaryComponent->is_active ? 'success' : 'muted',
            ],
            'indexRoute' => route('finance.salary-components.index'),
            'editRoute' => route('finance.salary-components.edit', $salaryComponent),
            'toggleRoute' => route('finance.salary-components.toggle', $salaryComponent),
            'destroyRoute' => route('finance.salary-components.destroy', $salaryComponent),
            'isActive' => $salaryComponent->is_active,
            'canDelete' => $salaryComponent->employee_components_count === 0
                && $salaryComponent->payroll_items_count === 0,
            'viewPermission' => 'salary-components.view',
            'managePermission' => 'salary-components.manage',
            'deleteConfirmation' => 'Hapus komponen gaji ini?',
        ]);
    }

    public function edit(SalaryComponent $salaryComponent): View
    {
        return $this->form($salaryComponent);
    }

    public function update(
        SalaryComponentRequest $request,
        SalaryComponent $salaryComponent,
    ): RedirectResponse {
        $salaryComponent->update($request->validated());

        activity('payroll')
            ->performedOn($salaryComponent)
            ->causedBy($request->user())
            ->event('salary-component.updated')
            ->log('Komponen gaji diperbarui');

        return redirect()
            ->route('finance.salary-components.show', $salaryComponent)
            ->with('status', 'Komponen gaji berhasil diperbarui.');
    }

    public function toggle(Request $request, SalaryComponent $salaryComponent): RedirectResponse
    {
        abort_unless($request->user()?->can('salary-components.manage'), 403);
        $salaryComponent->update(['is_active' => ! $salaryComponent->is_active]);

        activity('payroll')
            ->performedOn($salaryComponent)
            ->causedBy($request->user())
            ->event($salaryComponent->is_active ? 'salary-component.activated' : 'salary-component.deactivated')
            ->log($salaryComponent->is_active ? 'Komponen gaji diaktifkan' : 'Komponen gaji dinonaktifkan');

        return back()->with('status', 'Status komponen gaji diperbarui.');
    }

    public function destroy(Request $request, SalaryComponent $salaryComponent): RedirectResponse
    {
        abort_unless($request->user()?->can('salary-components.manage'), 403);

        if ($salaryComponent->employeeComponents()->exists() || $salaryComponent->payrollItems()->exists()) {
            throw ValidationException::withMessages([
                'salary_component' => 'Komponen sudah digunakan dan tidak dapat dihapus. Nonaktifkan sebagai gantinya.',
            ]);
        }

        activity('payroll')
            ->performedOn($salaryComponent)
            ->causedBy($request->user())
            ->event('salary-component.deleted')
            ->log('Komponen gaji dihapus');

        $salaryComponent->delete();

        return redirect()
            ->route('finance.salary-components.index')
            ->with('status', 'Komponen gaji berhasil dihapus.');
    }

    private function form(SalaryComponent $salaryComponent): View
    {
        $expenseAccounts = ChartAccount::query()
            ->where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $payableAccounts = ChartAccount::query()
            ->where('account_type', 'liability')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('finance.generic.form', [
            'title' => $salaryComponent->exists ? 'Edit Komponen Gaji' : 'Tambah Komponen Gaji',
            'description' => 'Tentukan jenis, metode perhitungan, dan akun jurnal komponen.',
            'action' => $salaryComponent->exists
                ? route('finance.salary-components.update', $salaryComponent)
                : route('finance.salary-components.store'),
            'method' => $salaryComponent->exists ? 'PUT' : 'POST',
            'cancelRoute' => $salaryComponent->exists
                ? route('finance.salary-components.show', $salaryComponent)
                : route('finance.salary-components.index'),
            'submitLabel' => $salaryComponent->exists ? 'Simpan Perubahan' : 'Buat Komponen',
            'fields' => [
                [
                    'name' => 'code',
                    'label' => 'Kode Komponen',
                    'required' => true,
                    'value' => $salaryComponent->code,
                ],
                [
                    'name' => 'name',
                    'label' => 'Nama Komponen',
                    'required' => true,
                    'value' => $salaryComponent->name,
                ],
                [
                    'name' => 'component_type',
                    'label' => 'Jenis Komponen',
                    'type' => 'select',
                    'required' => true,
                    'value' => $salaryComponent->component_type ?? SalaryComponentType::Earning->value,
                    'options' => [
                        ['value' => SalaryComponentType::Earning->value, 'label' => 'Pendapatan'],
                        ['value' => SalaryComponentType::Deduction->value, 'label' => 'Potongan'],
                    ],
                ],
                [
                    'name' => 'calculation_type',
                    'label' => 'Metode Perhitungan',
                    'type' => 'select',
                    'required' => true,
                    'value' => $salaryComponent->calculation_type ?? SalaryCalculationType::Fixed->value,
                    'options' => collect(SalaryCalculationType::cases())->map(fn (SalaryCalculationType $type): array => [
                        'value' => $type->value,
                        'label' => str($type->value)->title()->toString(),
                    ])->all(),
                ],
                [
                    'name' => 'default_amount',
                    'label' => 'Nominal Default',
                    'type' => 'number',
                    'min' => 0,
                    'step' => '0.01',
                    'value' => $salaryComponent->default_amount,
                ],
                [
                    'name' => 'percentage',
                    'label' => 'Persentase',
                    'type' => 'number',
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.0001',
                    'value' => $salaryComponent->percentage,
                ],
                [
                    'name' => 'expense_account_id',
                    'label' => 'Akun Beban',
                    'type' => 'select',
                    'value' => $salaryComponent->expense_account_id,
                    'placeholder' => 'Pilih akun beban',
                    'options' => $expenseAccounts->map(fn (ChartAccount $account): array => [
                        'value' => $account->getKey(),
                        'label' => $account->code.' — '.$account->name,
                    ])->all(),
                ],
                [
                    'name' => 'payable_account_id',
                    'label' => 'Akun Utang',
                    'type' => 'select',
                    'value' => $salaryComponent->payable_account_id,
                    'placeholder' => 'Pilih akun utang',
                    'options' => $payableAccounts->map(fn (ChartAccount $account): array => [
                        'value' => $account->getKey(),
                        'label' => $account->code.' — '.$account->name,
                    ])->all(),
                ],
                [
                    'name' => 'taxable',
                    'label' => 'Kena Pajak',
                    'type' => 'checkbox',
                    'value' => $salaryComponent->taxable,
                ],
                [
                    'name' => 'is_attendance_based',
                    'label' => 'Berbasis Absensi',
                    'type' => 'checkbox',
                    'value' => $salaryComponent->is_attendance_based,
                ],
                [
                    'name' => 'is_active',
                    'label' => 'Komponen Aktif',
                    'type' => 'checkbox',
                    'value' => $salaryComponent->exists ? $salaryComponent->is_active : true,
                    'span' => 2,
                ],
            ],
        ]);
    }
}
