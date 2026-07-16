<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\EmployeeSalaryComponentRequest;
use App\Models\Employee;
use App\Models\Finance\EmployeePayroll;
use App\Models\Finance\EmployeeSalaryComponent;
use App\Models\Finance\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class EmployeeSalaryComponentController extends Controller
{
    public function index(Request $request): View
    {
        $items = EmployeeSalaryComponent::query()
            ->with(['employee', 'salaryComponent'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereHas('employee', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('salaryComponent', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('effective_from')
            ->paginate(20)
            ->withQueryString();

        return view('finance.generic.index', [
            'title' => 'Struktur Gaji Pegawai',
            'description' => 'Atur komponen gaji per pegawai beserta masa berlakunya.',
            'items' => $items,
            'headers' => ['Pegawai', 'Komponen', 'Nilai', 'Mulai', 'Berakhir', 'Status'],
            'rowBuilder' => static fn (EmployeeSalaryComponent $structure): array => [
                $structure->employee?->name ?? '-',
                ($structure->salaryComponent?->code ?? '-').' — '.($structure->salaryComponent?->name ?? '-'),
                $structure->amount !== null
                    ? 'Rp '.number_format((float) $structure->amount, 0, ',', '.')
                    : rtrim(rtrim((string) $structure->percentage, '0'), '.').' %',
                $structure->effective_from?->format('d/m/Y'),
                $structure->effective_until?->format('d/m/Y') ?? '-',
                [
                    'value' => $structure->is_active ? 'Aktif' : 'Nonaktif',
                    'variant' => $structure->is_active ? 'success' : 'muted',
                ],
            ],
            'createRoute' => route('finance.employee-salaries.create'),
            'createLabel' => 'Tambah Struktur',
            'showRouteName' => 'finance.employee-salaries.show',
            'editRouteName' => 'finance.employee-salaries.edit',
            'toggleRouteName' => 'finance.employee-salaries.toggle',
            'destroyRouteName' => 'finance.employee-salaries.destroy',
            'viewPermission' => 'employee-salaries.view',
            'managePermission' => 'employee-salaries.manage',
            'canDelete' => static fn (EmployeeSalaryComponent $structure): bool => ! self::hasPayrollHistory($structure),
            'searchPlaceholder' => 'Pegawai atau komponen gaji',
            'emptyTitle' => 'Belum ada struktur gaji pegawai',
        ]);
    }

    public function create(): View
    {
        return $this->form(new EmployeeSalaryComponent);
    }

    public function store(EmployeeSalaryComponentRequest $request): RedirectResponse
    {
        $structure = EmployeeSalaryComponent::create($request->validated());

        activity('payroll')
            ->performedOn($structure)
            ->causedBy($request->user())
            ->event('employee-salary.created')
            ->log('Struktur gaji pegawai dibuat');

        return redirect()
            ->route('finance.employee-salaries.show', $structure)
            ->with('status', 'Struktur gaji berhasil dibuat.');
    }

    public function show(EmployeeSalaryComponent $employeeSalary): View
    {
        $employeeSalary->load(['employee', 'salaryComponent']);

        return view('finance.generic.show', [
            'title' => 'Detail Struktur Gaji',
            'description' => $employeeSalary->employee?->name,
            'details' => [
                'Pegawai' => $employeeSalary->employee?->name,
                'Komponen' => ($employeeSalary->salaryComponent?->code ?? '-').' — '.($employeeSalary->salaryComponent?->name ?? '-'),
                'Nominal' => $employeeSalary->amount !== null
                    ? 'Rp '.number_format((float) $employeeSalary->amount, 0, ',', '.')
                    : '-',
                'Persentase' => $employeeSalary->percentage !== null
                    ? rtrim(rtrim((string) $employeeSalary->percentage, '0'), '.').' %'
                    : '-',
                'Mulai Berlaku' => $employeeSalary->effective_from?->format('d/m/Y'),
                'Akhir Berlaku' => $employeeSalary->effective_until?->format('d/m/Y') ?? '-',
                'Catatan' => $employeeSalary->notes ?? '-',
            ],
            'status' => [
                'label' => $employeeSalary->is_active ? 'Aktif' : 'Nonaktif',
                'variant' => $employeeSalary->is_active ? 'success' : 'muted',
            ],
            'indexRoute' => route('finance.employee-salaries.index'),
            'editRoute' => route('finance.employee-salaries.edit', $employeeSalary),
            'toggleRoute' => route('finance.employee-salaries.toggle', $employeeSalary),
            'destroyRoute' => route('finance.employee-salaries.destroy', $employeeSalary),
            'isActive' => $employeeSalary->is_active,
            'canDelete' => ! self::hasPayrollHistory($employeeSalary),
            'viewPermission' => 'employee-salaries.view',
            'managePermission' => 'employee-salaries.manage',
            'deleteConfirmation' => 'Hapus struktur gaji ini?',
        ]);
    }

    public function edit(EmployeeSalaryComponent $employeeSalary): View
    {
        return $this->form($employeeSalary);
    }

    public function update(
        EmployeeSalaryComponentRequest $request,
        EmployeeSalaryComponent $employeeSalary,
    ): RedirectResponse {
        $employeeSalary->update($request->validated());

        activity('payroll')
            ->performedOn($employeeSalary)
            ->causedBy($request->user())
            ->event('employee-salary.updated')
            ->log('Struktur gaji pegawai diperbarui');

        return redirect()
            ->route('finance.employee-salaries.show', $employeeSalary)
            ->with('status', 'Struktur gaji berhasil diperbarui.');
    }

    public function toggle(Request $request, EmployeeSalaryComponent $employeeSalary): RedirectResponse
    {
        abort_unless($request->user()?->can('employee-salaries.manage'), 403);
        $employeeSalary->update(['is_active' => ! $employeeSalary->is_active]);

        activity('payroll')
            ->performedOn($employeeSalary)
            ->causedBy($request->user())
            ->event($employeeSalary->is_active ? 'employee-salary.activated' : 'employee-salary.deactivated')
            ->log($employeeSalary->is_active ? 'Struktur gaji diaktifkan' : 'Struktur gaji dinonaktifkan');

        return back()->with('status', 'Status struktur gaji diperbarui.');
    }

    public function destroy(Request $request, EmployeeSalaryComponent $employeeSalary): RedirectResponse
    {
        abort_unless($request->user()?->can('employee-salaries.manage'), 403);

        if (self::hasPayrollHistory($employeeSalary)) {
            throw ValidationException::withMessages([
                'employee_salary' => 'Struktur sudah memiliki riwayat payroll dan tidak dapat dihapus. Nonaktifkan sebagai gantinya.',
            ]);
        }

        activity('payroll')
            ->performedOn($employeeSalary)
            ->causedBy($request->user())
            ->event('employee-salary.deleted')
            ->log('Struktur gaji pegawai dihapus');

        $employeeSalary->delete();

        return redirect()
            ->route('finance.employee-salaries.index')
            ->with('status', 'Struktur gaji berhasil dihapus.');
    }

    private function form(EmployeeSalaryComponent $employeeSalary): View
    {
        $employees = Employee::query()->where('is_active', true)->orderBy('name')->get();
        $components = SalaryComponent::query()
            ->where(function ($query) use ($employeeSalary): void {
                $query->where('is_active', true);

                if ($employeeSalary->salary_component_id) {
                    $query->orWhereKey($employeeSalary->salary_component_id);
                }
            })
            ->orderBy('code')
            ->get();

        return view('finance.generic.form', [
            'title' => $employeeSalary->exists ? 'Edit Struktur Gaji' : 'Tambah Struktur Gaji',
            'description' => 'Isi nominal atau persentase dan tentukan periode efektifnya.',
            'action' => $employeeSalary->exists
                ? route('finance.employee-salaries.update', $employeeSalary)
                : route('finance.employee-salaries.store'),
            'method' => $employeeSalary->exists ? 'PUT' : 'POST',
            'cancelRoute' => $employeeSalary->exists
                ? route('finance.employee-salaries.show', $employeeSalary)
                : route('finance.employee-salaries.index'),
            'submitLabel' => $employeeSalary->exists ? 'Simpan Perubahan' : 'Buat Struktur',
            'fields' => [
                [
                    'name' => 'employee_id',
                    'label' => 'Pegawai',
                    'type' => 'select',
                    'required' => true,
                    'value' => $employeeSalary->employee_id,
                    'placeholder' => 'Pilih pegawai',
                    'options' => $employees->map(fn (Employee $employee): array => [
                        'value' => $employee->getKey(),
                        'label' => $employee->name,
                    ])->all(),
                ],
                [
                    'name' => 'salary_component_id',
                    'label' => 'Komponen Gaji',
                    'type' => 'select',
                    'required' => true,
                    'value' => $employeeSalary->salary_component_id,
                    'placeholder' => 'Pilih komponen',
                    'options' => $components->map(fn (SalaryComponent $component): array => [
                        'value' => $component->getKey(),
                        'label' => $component->code.' — '.$component->name,
                    ])->all(),
                ],
                [
                    'name' => 'amount',
                    'label' => 'Nominal',
                    'type' => 'number',
                    'min' => 0,
                    'step' => '0.01',
                    'value' => $employeeSalary->amount,
                    'help' => 'Wajib diisi bila persentase kosong.',
                ],
                [
                    'name' => 'percentage',
                    'label' => 'Persentase',
                    'type' => 'number',
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.0001',
                    'value' => $employeeSalary->percentage,
                    'help' => 'Wajib diisi bila nominal kosong.',
                ],
                [
                    'name' => 'effective_from',
                    'label' => 'Mulai Berlaku',
                    'type' => 'date',
                    'required' => true,
                    'value' => $employeeSalary->effective_from?->format('Y-m-d') ?? now()->toDateString(),
                ],
                [
                    'name' => 'effective_until',
                    'label' => 'Akhir Berlaku',
                    'type' => 'date',
                    'value' => $employeeSalary->effective_until?->format('Y-m-d'),
                ],
                [
                    'name' => 'notes',
                    'label' => 'Catatan',
                    'type' => 'textarea',
                    'value' => $employeeSalary->notes,
                    'span' => 2,
                ],
                [
                    'name' => 'is_active',
                    'label' => 'Struktur Aktif',
                    'type' => 'checkbox',
                    'value' => $employeeSalary->exists ? $employeeSalary->is_active : true,
                    'span' => 2,
                ],
            ],
        ]);
    }

    private static function hasPayrollHistory(EmployeeSalaryComponent $structure): bool
    {
        return EmployeePayroll::query()
            ->where('employee_id', $structure->employee_id)
            ->whereHas('period', function ($query) use ($structure): void {
                $query->where('ends_on', '>=', $structure->effective_from);
            })
            ->exists();
    }
}
