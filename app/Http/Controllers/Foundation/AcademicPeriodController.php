<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Enums\SemesterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\StoreAcademicPeriodRequest;
use App\Http\Requests\Foundation\UpdateAcademicPeriodRequest;
use App\Models\AcademicYear;
use App\Services\Foundation\AcademicPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademicPeriodController extends Controller
{
    public function __construct(private AcademicPeriodService $service) {}

    public function index(): View
    {
        return view('foundation.academic-periods.index', ['title' => 'Tahun Ajaran & Semester', 'academicYears' => AcademicYear::query()->with('semesters')->orderByDesc('starts_at')->get(), 'activePeriod' => $this->service->current()]);
    }
    public function create(): View { return view('foundation.academic-periods.form', ['title' => 'Tambah Tahun Ajaran', 'academicYear' => new AcademicYear, 'editing' => false, 'odd' => null, 'even' => null]); }
    public function store(StoreAcademicPeriodRequest $request): RedirectResponse { $this->service->create($request->validated(), $request->user()); return redirect()->route('academic-periods.index')->with('status', 'Tahun ajaran dan dua semester berhasil ditambahkan.'); }
    public function edit(AcademicYear $academicYear): View { $academicYear->load('semesters'); return view('foundation.academic-periods.form', ['title' => 'Edit Tahun Ajaran', 'academicYear' => $academicYear, 'editing' => true, 'odd' => $academicYear->semesters->firstWhere('type', SemesterType::Ganjil), 'even' => $academicYear->semesters->firstWhere('type', SemesterType::Genap)]); }
    public function update(UpdateAcademicPeriodRequest $request, AcademicYear $academicYear): RedirectResponse { $this->service->update($academicYear, $request->validated(), $request->user()); return redirect()->route('academic-periods.index')->with('status', 'Tahun ajaran dan semester berhasil diperbarui.'); }
    public function destroy(AcademicYear $academicYear): RedirectResponse { $this->service->delete($academicYear, request()->user()); return redirect()->route('academic-periods.index')->with('status', 'Tahun ajaran berhasil dihapus.'); }
}
