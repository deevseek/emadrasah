<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\SemesterRequest;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\ActivityLogger;
use App\Services\Foundation\AcademicPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function index(Request $request): View { $semesters = Semester::query()->with('academicYear')->when($request->filled('academic_year_id'), fn ($q) => $q->where('academic_year_id', $request->integer('academic_year_id')))->latest()->paginate(10)->withQueryString(); return view('foundation.semesters.index', ['semesters' => $semesters, 'years' => AcademicYear::orderByDesc('starts_on')->get()]); }
    public function create(): View { return view('foundation.semesters.form', ['semester' => new Semester, 'years' => AcademicYear::orderByDesc('starts_on')->get()]); }
    public function store(SemesterRequest $request, ActivityLogger $logger): RedirectResponse { $data = $request->validated(); $data['name'] = (int) $data['term'] === 1 ? 'Ganjil' : 'Genap'; $semester = Semester::create($data); $logger->log('semester.created', $semester, [], $semester->toArray()); return redirect()->route('semesters.show', $semester)->with('status', 'Semester berhasil dibuat.'); }
    public function show(Semester $semester): View { return view('foundation.semesters.show', ['semester' => $semester->load('academicYear')]); }
    public function edit(Semester $semester): View { return view('foundation.semesters.form', ['semester' => $semester, 'years' => AcademicYear::orderByDesc('starts_on')->get()]); }
    public function update(SemesterRequest $request, Semester $semester, ActivityLogger $logger): RedirectResponse { $old = $semester->toArray(); $data = $request->validated(); $data['name'] = (int) $data['term'] === 1 ? 'Ganjil' : 'Genap'; $semester->update($data); $logger->log('semester.updated', $semester, $old, $semester->fresh()->toArray()); return redirect()->route('semesters.show', $semester)->with('status', 'Semester berhasil diperbarui.'); }
    public function activate(Semester $semester, AcademicPeriodService $service, ActivityLogger $logger): RedirectResponse { $old = $semester->toArray(); $service->activateSemester($semester->load('academicYear')); $logger->log('semester.activated', $semester, $old, $semester->fresh()->toArray()); return back()->with('status', 'Semester berhasil diaktifkan.'); }
    public function deactivate(Semester $semester, ActivityLogger $logger): RedirectResponse { $old = $semester->toArray(); $semester->update(['is_active' => false]); $logger->log('semester.deactivated', $semester, $old, $semester->fresh()->toArray()); return back()->with('status', 'Semester berhasil dinonaktifkan.'); }
}
