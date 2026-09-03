<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrd;

use App\Actions\Hrd\ProcessAttendancePayroll;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hrd\StorePayrollRequest;
use App\Models\{Personnel, PersonnelPayroll, SchoolProfile};
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class PersonnelPayrollController extends Controller
{
    public function index(Request $request, ApplicationSettingService $settings): View
    {
        $query = PersonnelPayroll::with('personnel')->latest('period_start');
        if (! $request->user()->can('personnel-payroll.view')) {
            $personnel = $request->user()->personnel;
            abort_unless($personnel, 403);
            $query->where('personnel_id', $personnel->id);
        }

        return view('hrd.payroll.index', [
            'title' => 'Payroll Berdasarkan Absensi',
            'payrolls' => $query->paginate(20)->withQueryString(),
            'personnel' => Personnel::where('is_active', true)->where('payroll_enabled', true)->orderBy('full_name')->get(),
            'attendancePayrollEnabled' => (bool) $settings->get('hrd_payroll_by_attendance_enabled', true),
        ]);
    }

    public function store(StorePayrollRequest $request, ProcessAttendancePayroll $action): RedirectResponse
    {
        $payroll = $action->handle(Personnel::findOrFail($request->integer('personnel_id')), $request->validated(), $request->user());

        return redirect()->route('hrd.payroll.show', $payroll)->with('status', 'Payroll berdasarkan absensi berhasil diproses.');
    }

    public function show(PersonnelPayroll $p, Request $request): View
    {
        $this->authorizeView($p, $request);

        return view('hrd.payroll.show', ['title' => 'Slip Gaji', 'payroll' => $p->load('personnel'), 'school' => SchoolProfile::query()->first()]);
    }

    public function print(PersonnelPayroll $p, Request $request): View
    {
        $this->authorizeView($p, $request);

        return view('hrd.payroll.print', ['title' => 'Slip Gaji', 'payroll' => $p->load('personnel'), 'school' => SchoolProfile::query()->first()]);
    }

    private function authorizeView(PersonnelPayroll $payroll, Request $request): void
    {
        if (! $request->user()->can('personnel-payroll.view') && $payroll->personnel_id !== $request->user()->personnel?->id) {
            abort(403);
        }
    }
}
