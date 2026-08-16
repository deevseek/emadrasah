<?php

declare(strict_types=1);

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\StudentAttendance;
use App\Models\StudentLeaveRequest;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use App\Services\Academic\LessonScheduleService;
use App\Services\ParentPortal\GuardianAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GuardianAccessService $access, LessonScheduleService $lessons): View
    {
        $section = match ($request->route()?->getName()) {
            'parent.children' => 'children',
            'parent.schedule' => 'schedule',
            'parent.attendance' => 'attendance',
            'parent.finance' => 'finance',
            'parent.profile' => 'profile',
            default => 'dashboard',
        };

        $guardian = $access->guardianOrNull($request->user());
        $children = collect();
        $student = null;
        $attendance = null;
        $schedule = ['current' => null, 'next' => null, 'timeline' => collect()];
        $leaveRequests = collect();
        $invoices = collect();
        $payments = collect();

        if ($guardian !== null) {
            $children = $guardian->students()
                ->with('activeClassroomMembership.classroom.academicYear')
                ->orderBy('full_name')
                ->get();

            $requestedStudentId = (int) $request->query('student');
            $student = $requestedStudentId > 0
                ? $children->firstWhere('id', $requestedStudentId)
                : $children->first();

            if ($student !== null) {
                $student = $access->student($request->user(), $student->id);
                $student->load('activeClassroomMembership.classroom.academicYear');

                $membership = $student->activeClassroomMembership;
                if ($membership !== null) {
                    $semester = Semester::query()
                        ->where('academic_year_id', $membership->classroom->academic_year_id)
                        ->where('is_active', true)
                        ->first();

                    if ($semester !== null) {
                        $schedule = $lessons->currentAndNext($membership->classroom_id, $semester->id);
                    }
                }

                $attendance = StudentAttendance::query()
                    ->where('student_id', $student->id)
                    ->whereDate('attendance_date', now('Asia/Jakarta')->toDateString())
                    ->first();

                $leaveRequests = StudentLeaveRequest::query()
                    ->where('guardian_id', $guardian->id)
                    ->where('student_id', $student->id)
                    ->latest('submitted_at')
                    ->limit(20)
                    ->get();

                $invoices = StudentInvoice::query()
                    ->where('student_id', $student->id)
                    ->latest('issue_date')
                    ->limit(24)
                    ->get();

                $payments = StudentPayment::query()
                    ->where('student_id', $student->id)
                    ->latest('paid_at')
                    ->limit(20)
                    ->get();
            }
        }

        return view('parent.dashboard', compact(
            'section',
            'guardian',
            'children',
            'student',
            'attendance',
            'schedule',
            'leaveRequests',
            'invoices',
            'payments',
        ));
    }
}
