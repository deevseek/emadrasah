<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\AcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\ActivityLogger;
use App\Services\Foundation\AcademicPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(Request $request): View
    {
        $years = AcademicYear::query()->withCount('semesters')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->latest()->paginate(10)->withQueryString();
        return view('foundation.academic-years.index', compact('years'));
    }
    public function create(): View { return view('foundation.academic-years.form', ['year' => new AcademicYear]); }
    public function store(AcademicYearRequest $request, ActivityLogger $logger): RedirectResponse { $year = AcademicYear::create($request->validated()); $logger->log('academic_year.created', $year, [], $year->toArray()); return redirect()->route('academic-years.show', $year)->with('status', 'Tahun ajaran berhasil dibuat.'); }
    public function show(AcademicYear $academicYear): View { return view('foundation.academic-years.show', ['year' => $academicYear->load('semesters')]); }
    public function edit(AcademicYear $academicYear): View { return view('foundation.academic-years.form', ['year' => $academicYear]); }
    public function update(AcademicYearRequest $request, AcademicYear $academicYear, ActivityLogger $logger): RedirectResponse { $old = $academicYear->toArray(); $academicYear->update($request->validated()); $logger->log('academic_year.updated', $academicYear, $old, $academicYear->fresh()->toArray()); return redirect()->route('academic-years.show', $academicYear)->with('status', 'Tahun ajaran berhasil diperbarui.'); }
    public function activate(AcademicYear $academicYear, AcademicPeriodService $service, ActivityLogger $logger): RedirectResponse { $old = $academicYear->toArray(); $service->activateYear($academicYear); $logger->log('academic_year.activated', $academicYear, $old, $academicYear->fresh()->toArray()); return back()->with('status', 'Tahun ajaran berhasil diaktifkan.'); }
    public function deactivate(AcademicYear $academicYear, AcademicPeriodService $service, ActivityLogger $logger): RedirectResponse { $old = $academicYear->toArray(); $service->deactivateYear($academicYear); $logger->log('academic_year.deactivated', $academicYear, $old, $academicYear->fresh()->toArray()); return back()->with('status', 'Tahun ajaran berhasil dinonaktifkan.'); }
}
