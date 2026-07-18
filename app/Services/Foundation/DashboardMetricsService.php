<?php

declare(strict_types=1);

namespace App\Services\Foundation;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\StudentScore;
use App\Models\ReportCard;
use App\Models\AssessmentComponent;
use App\Models\BtaqGroupStudent;
use App\Models\BtaqProgressHistory;
use App\Models\BtaqStudentProgress;
use App\Models\BtaqSession;
use App\Models\BtaqSchedule;
use App\Models\BtaqGroup;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeLeaveRequest;
use App\Models\WorkSchedule;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceVerificationStatus;
use App\Enums\LeaveStatus;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceSession;
use App\Models\StudentEnrollment;
use App\Enums\StudentAttendanceSessionStatus;
use App\Enums\StudentAttendanceStatus;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TeachingJournal;
use App\Enums\TeachingJournalStatus;
use App\Models\User;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use App\Models\Finance\CashAccount;
use App\Models\OperationalFinance\OperationalTransaction;
use App\Models\OperationalFinance\CashReconciliation;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardMetricsService
{
    public function summary(SchoolProfileService $profiles): array
    {
        $profile = $profiles->current();
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $activeSemester = Semester::query()->with('academicYear')->where('is_active', true)->first();
        $activeYearId = $activeYear?->id;
        $activeSemesterId = $activeSemester?->id;

        $classesAtCapacity = 0;
        if ($activeYearId !== null) {
            $classesAtCapacity = Classroom::query()
                ->where('academic_year_id', $activeYearId)
                ->where('is_active', true)
                ->whereNotNull('capacity')
                ->where('capacity', '>', 0)
                ->whereHas('activeStudentEnrollments')
                ->withCount('activeStudentEnrollments')
                ->get()
                ->filter(fn (Classroom $classroom): bool => (int) $classroom->active_student_enrollments_count >= (int) $classroom->capacity)
                ->count();
        }

        return [
            'profile' => $profile,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'inactiveUsers' => User::query()->where('is_active', false)->count(),
            'profileComplete' => $profiles->isComplete($profile),
            'latestActivities' => Activity::query()->where('log_name', 'foundation')->latest()->limit(5)->get(),
            'activeEmployees' => Employee::query()->where('is_active', true)->count(),
            'activeStudents' => Student::query()->where('is_active', true)->count(),
            'activeClassrooms' => Classroom::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->where('is_active', true)->count(),
            'classroomsWithoutHomeroom' => Classroom::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->where('is_active', true)->whereNull('homeroom_teacher_id')->count(),
            'activeStudentsWithoutPlacement' => Student::query()->where('is_active', true)->when($activeYearId, fn ($query) => $query->whereDoesntHave('enrollments', fn ($enrollment) => $enrollment->where('academic_year_id', $activeYearId)->where('enrollment_status', EnrollmentStatus::Active->value)))->count(),
            'classroomsAtOrOverCapacity' => $classesAtCapacity,
            'activeSubjects' => Subject::query()->where('is_active', true)->count(),
            'activeTeachingAssignments' => TeachingAssignment::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->where('is_active', true)->count(),
            'assignmentsWithoutSchedule' => TeachingAssignment::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->where('is_active', true)->whereDoesntHave('schedules', fn ($schedule) => $schedule->where('is_active', true))->count(),
            'activeSchedules' => LessonSchedule::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->where('is_active', true)->count(),

            'studentsPresentToday' => StudentAttendance::query()->whereDate('attendance_date', today())->where('status', StudentAttendanceStatus::Present->value)->count(),
            'studentsPermissionToday' => StudentAttendance::query()->whereDate('attendance_date', today())->where('status', StudentAttendanceStatus::Permission->value)->count(),
            'studentsSickToday' => StudentAttendance::query()->whereDate('attendance_date', today())->where('status', StudentAttendanceStatus::Sick->value)->count(),
            'studentsAlphaToday' => StudentAttendance::query()->whereDate('attendance_date', today())->where('status', StudentAttendanceStatus::Alpha->value)->count(),
            'studentsLateToday' => StudentAttendance::query()->whereDate('attendance_date', today())->where('status', StudentAttendanceStatus::Late->value)->count(),
            'studentAttendanceDraftsToday' => StudentAttendanceSession::query()->whereDate('attendance_date', today())->where('status', StudentAttendanceSessionStatus::Draft->value)->count(),
            'studentAttendanceMissingClassesToday' => Classroom::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->where('is_active', true)->whereHas('activeStudentEnrollments')->whereDoesntHave('studentAttendanceSessions', fn ($query) => $query->whereDate('attendance_date', today())->where('status', StudentAttendanceSessionStatus::Final->value))->count(),

            'employeesPresentToday' => EmployeeAttendance::query()->whereDate('attendance_date', today())->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value])->count(),
            'employeesLateToday' => EmployeeAttendance::query()->whereDate('attendance_date', today())->where('status', AttendanceStatus::Late->value)->count(),
            'employeesNotCheckedOutToday' => EmployeeAttendance::query()->whereDate('attendance_date', today())->whereNotNull('checked_in_at')->whereNull('checked_out_at')->count(),
            'employeesOnLeaveToday' => EmployeeAttendance::query()->whereDate('attendance_date', today())->whereIn('status', [AttendanceStatus::Leave->value, AttendanceStatus::Sick->value])->count(),
            'pendingEmployeeLeaves' => EmployeeLeaveRequest::query()->where('status', LeaveStatus::Pending->value)->count(),
            'employeesWithoutActiveWorkSchedule' => Employee::query()->where('is_active', true)->whereDoesntHave('workScheduleAssignments', fn ($assignment) => $assignment->where('is_active', true))->count(),
            'unverifiedEmployeeAttendances' => EmployeeAttendance::query()->where('verification_status', AttendanceVerificationStatus::Pending->value)->count(),
            'teachingJournalPendingVerification' => TeachingJournal::query()->where('status', TeachingJournalStatus::Submitted->value)->count(),
            'teachingJournalNeedsRevision' => TeachingJournal::query()->where('status', TeachingJournalStatus::Rejected->value)->count(),
            'teachingJournalDraftMine' => TeachingJournal::query()->where('employee_id', auth()->user()?->employee?->id)->where('status', TeachingJournalStatus::Draft->value)->count(),
            'teachingJournalRevisionMine' => TeachingJournal::query()->where('employee_id', auth()->user()?->employee?->id)->where('status', TeachingJournalStatus::Rejected->value)->count(),
            'teachingJournalUnfilledToday' => max(0, LessonSchedule::query()->where('is_active', true)->where('day_of_week', strtolower(today()->locale('id')->dayName))->count() - TeachingJournal::query()->whereDate('journal_date', today())->count()),
            'btaqActiveGroups' => BtaqGroup::query()->where('is_active', true)->count(),
            'btaqActiveStudents' => BtaqGroupStudent::query()->where('status', 'active')->distinct('student_id')->count('student_id'),
            'btaqSessionsToday' => BtaqSession::query()->whereDate('session_date', today())->count(),
            'btaqUnfilledSessions' => BtaqSchedule::query()->where('is_active', true)->count(),
            'btaqPendingVerification' => BtaqSession::query()->where('status', 'submitted')->count(),
            'btaqStudentsNeedGuidance' => BtaqStudentProgress::query()->where('achievement_status', 'perlu_bimbingan')->distinct('student_id')->count('student_id'),
            'btaqLevelPromotionsThisMonth' => BtaqProgressHistory::query()->whereMonth('created_at', today()->month)->count(),
            'assessmentAssignmentsUnfilled' => max(0, TeachingAssignment::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->where('is_active', true)->count() - AssessmentComponent::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->distinct('teaching_assignment_id')->count('teaching_assignment_id')),
            'assessmentDraftScores' => StudentScore::query()->whereHas('component', fn ($query) => $query->where('status', 'draft'))->count(),
            'assessmentNeedsRevision' => AssessmentComponent::query()->where('status', 'revision')->count(),
            'assessmentWaitingVerification' => AssessmentComponent::query()->where('status', 'published')->count(),
            'reportCardsUncompiled' => max(0, StudentEnrollment::query()->where('enrollment_status', 'active')->count() - ReportCard::query()->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->count()),
            'reportCardsWaitingVerification' => ReportCard::query()->where('status', 'submitted')->count(),
            'reportCardsNeedsRevision' => ReportCard::query()->where('status', 'reopened')->count(),
            'reportCardsFinal' => ReportCard::query()->whereIn('status', ['approved', 'locked'])->count(),
            'studentFinanceTodayPayments' => StudentPayment::query()->where('status', 'posted')->whereDate('payment_date', today())->sum('total_amount'),
            'studentFinanceMonthPayments' => StudentPayment::query()->where('status', 'posted')->whereYear('payment_date', today()->year)->whereMonth('payment_date', today()->month)->sum('total_amount'),
            'studentFinanceOutstanding' => StudentInvoice::query()->where('outstanding_amount', '>', 0)->where('status', '!=', 'cancelled')->sum('outstanding_amount'),
            'studentFinanceArrearStudents' => StudentInvoice::query()->where('outstanding_amount', '>', 0)->whereDate('due_on', '<', today())->where('status', '!=', 'cancelled')->distinct('student_id')->count('student_id'),
            'studentFinanceDueToday' => StudentInvoice::query()->where('outstanding_amount', '>', 0)->whereDate('due_on', today())->count(),
            'studentFinanceDueSevenDays' => StudentInvoice::query()->where('outstanding_amount', '>', 0)->whereBetween('due_on', [today(), today()->addDays(7)])->count(),
            'studentFinanceCancelledMonth' => StudentPayment::query()->where('status', 'cancelled')->whereYear('cancelled_at', today()->year)->whereMonth('cancelled_at', today()->month)->count(),
            'operationalFinanceTotalBalance' => CashAccount::query()->sum('current_balance'),
            'operationalFinanceTodayIncome' => OperationalTransaction::query()->where('status', 'posted')->where('transaction_type', 'income')->whereDate('transaction_date', today())->sum('amount'),
            'operationalFinanceTodayExpense' => OperationalTransaction::query()->where('status', 'posted')->where('transaction_type', 'expense')->whereDate('transaction_date', today())->sum('amount'),
            'operationalFinanceMonthNet' => OperationalTransaction::query()->where('status', 'posted')->whereYear('transaction_date', today()->year)->whereMonth('transaction_date', today()->month)->selectRaw("sum(case when transaction_type='income' then amount when transaction_type='expense' then -amount else 0 end) as total")->value('total') ?? 0,
            'operationalFinancePendingApproval' => OperationalTransaction::query()->where('status', 'submitted')->count(),
            'operationalFinanceCancelled' => OperationalTransaction::query()->where('status', 'cancelled')->count(),
            'operationalFinanceCashDifferences' => CashReconciliation::query()->where('difference', '!=', 0)->count(),
            'subjectsWithoutTeacher' => Subject::query()->where('is_active', true)->whereNotExists(function ($query) use ($activeYearId, $activeSemesterId): void {
                $query->select(DB::raw(1))->from('teaching_assignments')->whereColumn('teaching_assignments.subject_id', 'subjects.id')->where('teaching_assignments.is_active', true);
                if ($activeYearId !== null) { $query->where('teaching_assignments.academic_year_id', $activeYearId); }
                if ($activeSemesterId !== null) { $query->where('teaching_assignments.semester_id', $activeSemesterId); }
            })->count(),
        ];
    }
}
