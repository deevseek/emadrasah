<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\{DownloadStudentPhotosRequest, UpdateStudentPhotoRequest};
use App\Models\Student;
use App\Services\Students\{StudentPhotoArchiveService, StudentService};
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentPhotoController extends Controller
{
    public function show(Student $student): BinaryFileResponse
    {
        abort_unless($student->photo_path && Storage::disk('local')->exists($student->photo_path), 404);

        return response()->file(Storage::disk('local')->path($student->photo_path));
    }

    public function update(UpdateStudentPhotoRequest $request, Student $student, StudentService $service): RedirectResponse
    {
        $service->updatePhoto($student, $request->file('photo'), $request->user());

        return back()->with('status', 'Foto siswa berhasil disimpan.');
    }

    public function download(DownloadStudentPhotosRequest $request, StudentPhotoArchiveService $service): BinaryFileResponse|RedirectResponse
    {
        $classroom = $request->validated('classroom_label');
        try {
            $path = $service->create($classroom, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['classroom_label' => $exception->getMessage()]);
        }
        $safeClassroom = str($classroom)->slug('-')->value() ?: 'kelas';

        return response()->download($path, "foto-siswa-{$safeClassroom}.zip")->deleteFileAfterSend();
    }
}
