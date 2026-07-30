<?php

declare(strict_types=1);

namespace App\Http\Controllers\Classrooms;

use App\Http\Controllers\Controller;
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

        return view('classrooms.homeroom', compact('classroom', 'personnel'));
    }

    public function update(UpdateClassroomHomeroomRequest $request, Classroom $classroom, ClassroomService $service): RedirectResponse
    {
        $homeroomPersonnelId = $request->filled('homeroom_personnel_id')
            ? $request->integer('homeroom_personnel_id')
            : null;

        $service->assignHomeroom($request->user(), $classroom, $homeroomPersonnelId);

        return redirect()->route('classrooms.show', $classroom)->with('success', 'Wali kelas berhasil diperbarui.');
    }
}
