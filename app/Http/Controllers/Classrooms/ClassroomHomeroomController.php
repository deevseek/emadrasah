<?php

declare(strict_types=1);

namespace App\Http\Controllers\Classrooms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classrooms\SwapClassroomHomeroomRequest;
use App\Http\Requests\Classrooms\UpdateClassroomHomeroomRequest;
use App\Models\Classroom;
use App\Models\Personnel;
use App\Services\Classrooms\ClassroomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClassroomHomeroomController extends Controller
{
    public function edit(Classroom $classroom): View
    {
        request()->user()->can('classrooms.assign-homeroom') ?: abort(403);
        $personnel = Personnel::where('is_active', true)
            ->where(fn ($query) => $query->where('position', 'like', '%Guru%')
                ->orWhere('position', 'like', '%Kepala Madrasah%'))
            ->get();

        $swapClassrooms = Classroom::where('academic_year_id', $classroom->academic_year_id)
            ->where('is_active', true)
            ->whereKeyNot($classroom->id)
            ->whereNotNull('homeroom_personnel_id')
            ->with(['gradeLevel', 'homeroomPersonnel'])
            ->orderBy('grade_level_id')->orderBy('code')->get();

        return view('classrooms.homeroom', compact('classroom', 'personnel', 'swapClassrooms'));
    }

    public function update(UpdateClassroomHomeroomRequest $request, Classroom $classroom, ClassroomService $service): RedirectResponse
    {
        $homeroomPersonnelId = $request->filled('homeroom_personnel_id')
            ? $request->integer('homeroom_personnel_id')
            : null;

        $service->assignHomeroom($request->user(), $classroom, $homeroomPersonnelId);

        return redirect()->route('classrooms.show', $classroom)->with('success', 'Wali kelas berhasil diperbarui.');
    }

    public function swap(SwapClassroomHomeroomRequest $request, Classroom $classroom, ClassroomService $service): RedirectResponse
    {
        $service->swapHomerooms($request->user(), $classroom, $request->integer('target_classroom_id'));

        return redirect()->route('classrooms.homeroom.edit', $classroom)
            ->with('success', 'Wali kelas kedua rombel berhasil ditukar.');
    }
}
