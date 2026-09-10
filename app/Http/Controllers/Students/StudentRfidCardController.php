<?php
declare(strict_types=1);
namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\DeleteStudentRfidCardRequest;
use App\Http\Requests\Students\StoreStudentRfidCardRequest;
use App\Models\Student;
use App\Services\Students\StudentRfidCardService;
use Illuminate\Http\RedirectResponse;

class StudentRfidCardController extends Controller
{
    public function store(StoreStudentRfidCardRequest $request, Student $student, StudentRfidCardService $service): RedirectResponse
    {
        $service->register($student, $request->validated('uid'), $request->user());

        return back()->with('status', "Kartu RFID berhasil didaftarkan untuk {$student->full_name}.");
    }

    public function destroy(DeleteStudentRfidCardRequest $request, Student $student, StudentRfidCardService $service): RedirectResponse
    {
        $service->deleteActive($student, $request->user());

        return back()->with('status', 'Kartu RFID siswa berhasil dihapus.');
    }
}
