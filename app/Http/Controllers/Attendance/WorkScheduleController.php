<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WorkScheduleController extends Controller
{
    public function index(): View { return view('attendance.work-schedules.index', ['schedules' => WorkSchedule::query()->latest()->paginate(15)]); }
    public function create(): View { return view('attendance.work-schedules.form', ['schedule' => new WorkSchedule(), 'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get()]); }
    public function store(Request $request): RedirectResponse { WorkSchedule::create($this->validated($request) + ['created_by' => $request->user()->id]); return redirect()->route('work-schedules.index')->with('status', 'Jadwal kerja berhasil ditambahkan.'); }
    public function show(WorkSchedule $workSchedule): View { return view('attendance.work-schedules.show', ['schedule' => $workSchedule]); }
    public function edit(WorkSchedule $workSchedule): View { return view('attendance.work-schedules.form', ['schedule' => $workSchedule, 'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get()]); }
    public function update(Request $request, WorkSchedule $workSchedule): RedirectResponse { $workSchedule->update($this->validated($request) + ['updated_by' => $request->user()->id]); return redirect()->route('work-schedules.show', $workSchedule)->with('status', 'Jadwal kerja berhasil diperbarui.'); }
    public function toggle(WorkSchedule $workSchedule): RedirectResponse { $workSchedule->update(['is_active' => ! $workSchedule->is_active, 'updated_by' => auth()->id()]); return back()->with('status', $workSchedule->is_active ? 'Jadwal kerja diaktifkan.' : 'Jadwal kerja dinonaktifkan.'); }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'], 'employee_type' => ['nullable', 'string', 'max:50'], 'working_days' => ['required', 'array', 'min:1'], 'working_days.*' => ['integer', 'between:1,7'],
            'check_in_time' => ['required', 'date_format:H:i'], 'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'], 'check_out_time' => ['required', 'date_format:H:i'],
            'earliest_check_in_time' => ['nullable', 'date_format:H:i'], 'earliest_check_out_time' => ['nullable', 'date_format:H:i'], 'is_active' => ['nullable', 'boolean'], 'description' => ['nullable', 'string', 'max:1000'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
