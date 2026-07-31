<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subjects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subjects\UpdateGradeLoadsRequest;
use App\Models\{Subject, SubjectGradeLoad};
use App\Services\Subjects\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectGradeLoadController extends Controller
{
    public function edit(Request $request, SubjectService $service): View
    {
        abort_unless($request->user()->can('subjects.view-loads'), 403);
        return view('subjects.loads', ['subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get(), 'columns' => $service->matrixColumns(), 'loads' => SubjectGradeLoad::all()->keyBy(fn ($load) => $load->grade_level_id.'_'.$load->program_type->value.'_'.$load->subject_id)]);
    }
    public function update(UpdateGradeLoadsRequest $request, SubjectService $service): RedirectResponse { $service->updateLoads($request->user(), $request->validated('loads', [])); return back()->with('success', 'Struktur JP berhasil diperbarui.'); }
}
