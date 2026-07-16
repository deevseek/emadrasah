<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\EmployeePayroll;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmployeePayrollController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $employeeId = $user?->employee?->getKey();

        abort_if(
            ! $user?->can('payrolls.view')
            && (! $user?->can('payrolls.view-own') || $employeeId === null),
            403,
        );

        $payrolls = EmployeePayroll::query()
            ->with(['employee', 'period'])
            ->when(
                ! $user->can('payrolls.view'),
                fn (Builder $query): Builder => $query->where('employee_id', $employeeId),
            )
            ->when($request->string('search')->toString(), function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('employee', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('period', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(
                $request->string('status')->toString(),
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance.payrolls.index', compact('payrolls'));
    }

    public function show(Request $request, EmployeePayroll $employeePayroll): View
    {
        $this->authorizePayrollAccess($request, $employeePayroll);
        $payroll = $this->loadPayroll($employeePayroll);

        return view('finance.payrolls.show', compact('payroll'));
    }

    public function slip(Request $request, EmployeePayroll $employeePayroll): View
    {
        $this->authorizePayrollAccess($request, $employeePayroll);
        $payroll = $this->loadPayroll($employeePayroll);

        return view('finance.payrolls.slip', compact('payroll'));
    }

    private function authorizePayrollAccess(
        Request $request,
        EmployeePayroll $employeePayroll,
    ): void {
        $user = $request->user();

        if ($user?->can('payrolls.view')) {
            return;
        }

        abort_unless(
            $user?->can('payrolls.view-own')
            && $user->employee !== null
            && (int) $user->employee->getKey() === (int) $employeePayroll->employee_id,
            403,
        );
    }

    private function loadPayroll(EmployeePayroll $employeePayroll): EmployeePayroll
    {
        return $employeePayroll->load([
            'employee',
            'period',
            'items.salaryComponent',
            'reviewer',
            'approver',
            'financialTransaction',
        ]);
    }
}
