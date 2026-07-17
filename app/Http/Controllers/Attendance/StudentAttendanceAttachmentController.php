<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendance;
use App\Services\Attendance\StudentAttendanceService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentAttendanceAttachmentController extends Controller
{
    public function __invoke(StudentAttendance $attendance, StudentAttendanceService $service): StreamedResponse
    { abort_unless(request()->user()->can('student-attendances.view-attachment') || $service->canAccessClassroom(request()->user(), $attendance->classroom), 403); abort_unless($attendance->attachment_path && Storage::disk('local')->exists($attendance->attachment_path), 404); return Storage::disk('local')->download($attendance->attachment_path); }
}
