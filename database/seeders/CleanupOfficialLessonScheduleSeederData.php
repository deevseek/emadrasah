<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Employee;
use App\Models\LessonSchedule;
use App\Models\TeachingAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CleanupOfficialLessonScheduleSeederData extends Seeder
{
    private const CLASS_CODES = [
        'I-AS-SALAM', 'I-AR-RAHMAN', 'I-AR-RAHIM',
        'II-AL-MUMIN', 'II-AL-WAHHAB', 'II-AL-LATHIF',
        'III-AL-KHALIQ', 'III-AL-MAJID',
        'IV-AL-BASITH', 'IV-AL-KARIM',
        'V-AL-ALIM', 'VI-AL-MAJID',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            LessonSchedule::query()
                ->where('notes', 'like', 'Diimpor dari jadwal resmi MI Muslimat NU Demak TA 2026/2027 semester ganjil%')
                ->delete();

            TeachingAssignment::query()
                ->whereHas('employee', fn ($query) => $query->where('employee_number', 'like', 'WK-%'))
                ->whereHas('classroom', fn ($query) => $query->whereIn('code', self::CLASS_CODES))
                ->delete();

            Classroom::query()
                ->whereIn('code', self::CLASS_CODES)
                ->whereDoesntHave('studentEnrollments')
                ->update(['homeroom_teacher_id' => null]);

            Employee::query()
                ->where('employee_number', 'like', 'WK-%')
                ->whereNull('user_id')
                ->whereDoesntHave('teachingAssignments')
                ->delete();
        });
    }
}
