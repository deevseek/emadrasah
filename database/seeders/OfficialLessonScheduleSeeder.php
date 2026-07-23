<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\SubjectCategory;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Database\Seeder;

final class OfficialLessonScheduleSeeder extends Seeder
{
    private const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

    public function run(): void
    {
        $year = AcademicYear::firstOrCreate(['name' => '2026/2027'], ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::firstOrCreate(['academic_year_id' => $year->id, 'term' => 1], ['name' => 'Ganjil', 'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'is_active' => true]);

        $subjects = collect($this->subjects())->mapWithKeys(fn (array $subject) => [
            $subject[0] => Subject::updateOrCreate(['code' => $subject[0]], [
                'name' => $subject[1],
                'short_name' => $subject[2],
                'category' => $subject[3]->value,
                'minimum_passing_grade' => 75,
                'default_weekly_hours' => 2,
                'is_active' => true,
            ]),
        ]);

        foreach ($this->classSchedules() as $classData) {
            $grade = GradeLevel::firstOrCreate(['level' => $classData['grade']], ['name' => 'Kelas '.$classData['grade'], 'code' => 'K'.$classData['grade'], 'is_active' => true]);
            $homeroomTeacher = Employee::updateOrCreate(['employee_number' => 'WK-'.str($classData['code'])->slug('-')->upper()], [
                'name' => $classData['homeroom'],
                'gender' => Gender::Female->value,
                'employment_type' => EmploymentType::ClassTeacher->value,
                'employee_status' => EmployeeStatus::Permanent->value,
                'is_active' => true,
            ]);
            $classroom = Classroom::updateOrCreate(['academic_year_id' => $year->id, 'code' => $classData['code']], [
                'grade_level_id' => $grade->id,
                'name' => $classData['name'],
                'capacity' => 28,
                'homeroom_teacher_id' => $homeroomTeacher->id,
                'room' => 'Ruang '.$classData['name'],
                'is_active' => true,
            ]);

            foreach ($classData['slots'] as [$start, $end, $dailySubjects]) {
                foreach (self::DAYS as $index => $day) {
                    $code = $dailySubjects[$index] ?? null;
                    if (! $code || str_starts_with($code, 'BREAK')) {
                        continue;
                    }
                    $subject = $subjects[$code];
                    LessonSchedule::updateOrCreate([
                        'semester_id' => $semester->id,
                        'classroom_id' => $classroom->id,
                        'day_of_week' => $day,
                        'starts_at' => $start,
                        'ends_at' => $end,
                    ], [
                        'teaching_assignment_id' => null,
                        'academic_year_id' => $year->id,
                        'subject_id' => $subject->id,
                        'employee_id' => null,
                        'lesson_hours' => 1,
                        'room' => $classroom->room,
                        'is_active' => true,
                        'notes' => 'Diimpor dari jadwal resmi MI Muslimat NU Demak TA 2026/2027 semester ganjil. Guru pengampu belum ditetapkan karena dokumen jadwal hanya memuat mata pelajaran.',
                    ]);
                }
            }
        }
    }

    private function subjects(): array
    {
        return [
            ['PAGI', 'Pembiasaan Pagi dan Sholat Dhuha', 'Pembiasaan', SubjectCategory::SelfDevelopment],
            ['BTAQ', 'BTAQ', 'BTAQ', SubjectCategory::Btaq],
            ['PP', 'Pendidikan Pancasila', 'Pend. Pancasila', SubjectCategory::General],
            ['QH', "Al-Qur'an Hadits", "Al-Qur'an Hadits", SubjectCategory::Religion],
            ['PJOK', 'PJOK', 'PJOK', SubjectCategory::General],
            ['TASMI', "Tasmi'", "Tasmi'", SubjectCategory::Btaq],
            ['IST', 'Istigotsah', 'Istigotsah', SubjectCategory::Religion],
            ['LD', 'Literasi Digital (TIK, Koding dan Kecerdasan Artifisial)', 'Literasi Digital', SubjectCategory::General],
            ['BIN', 'Bahasa Indonesia', 'B. Indonesia', SubjectCategory::General],
            ['MTK', 'Matematika', 'Matematika', SubjectCategory::General],
            ['BAR', 'Bahasa Arab', 'B. Arab', SubjectCategory::Religion],
            ['KNU', 'Ke-NU-an', 'Ke-NU-an', SubjectCategory::LocalContent],
            ['AA', 'Aqidah Akhlaq', 'Aqidah Akhlaq', SubjectCategory::Religion],
            ['SBDP', 'SBDP', 'SBDP', SubjectCategory::General],
            ['FIQ', 'Fiqih', 'Fiqih', SubjectCategory::Religion],
            ['BIG', 'Bahasa Inggris', 'B. Inggris', SubjectCategory::General],
            ['BJW', 'Bahasa Jawa', 'B. Jawa', SubjectCategory::LocalContent],
            ['NUM', 'Numerasi', 'Numerasi', SubjectCategory::General],
            ['LIT', 'Literasi', 'Literasi', SubjectCategory::General],
            ['LUG', 'Lughoh Arobiyah', 'Lughoh Arobiyah', SubjectCategory::Religion],
            ['STEAM', 'Science, Technology, Engineering, Arts, and Mathematics (STEAM)', 'STEAM', SubjectCategory::General],
            ['IPAS', 'IPAS', 'IPAS', SubjectCategory::General],
            ['SKI', 'SKI', 'SKI', SubjectCategory::Religion],
            ['TKA', 'TKA', 'TKA', SubjectCategory::Other],
        ];
    }

    private function classSchedules(): array
    {
        $gradeOneSlots = [
            ['06:50', '07:15', ['PAGI','PAGI','PAGI','PAGI','PAGI','PAGI']],
            ['07:15', '07:50', ['BTAQ','BTAQ','BTAQ','BTAQ','TASMI','BTAQ']],
            ['07:50', '08:25', ['BTAQ','BTAQ','BTAQ','BTAQ','IST','BTAQ']],
            ['08:25', '09:00', ['PP','QH','PP','PJOK','LD','BIN']],
            ['09:00', '09:35', ['PP','QH','PP','PJOK','LD','BIN']],
            ['10:00', '10:35', ['BIN','MTK','BIN','MTK','BAR','KNU']],
            ['10:35', '11:10', ['BIN','MTK','BIN','MTK','BAR','STEAM']],
            ['11:10', '11:45', ['AA','SBDP','FIQ','BIG',null,null]],
            ['11:45', '12:10', ['AA','SBDP','FIQ','BJW',null,null]],
        ];

        $lowerSlots = [
            ['06:50', '07:15', ['PAGI','PAGI','PAGI','PAGI','PAGI','PAGI']],
            ['07:15', '07:50', ['PP','MTK','PJOK','IPAS','TASMI','PP']],
            ['07:50', '08:25', ['PP','MTK','PJOK','IPAS','IST','PP']],
            ['08:25', '09:00', ['BTAQ','BTAQ','BTAQ','BTAQ','LD','BTAQ']],
            ['09:00', '09:35', ['BTAQ','BTAQ','BTAQ','BTAQ','LD','BTAQ']],
            ['10:00', '10:35', ['BIN','MTK','BIN','MTK','BAR','KNU']],
            ['10:35', '11:10', ['BIN','MTK','BIN','MTK','BAR','STEAM']],
            ['11:10', '11:45', ['AA','SBDP','FIQ','BIG',null,null]],
            ['11:45', '12:10', ['AA','SBDP','FIQ','MTK',null,null]],
            ['12:30', '13:05', ['IPAS','LIT','SKI','PP',null,null]],
            ['13:05', '13:40', ['IPAS','LIT','SKI','PP',null,null]],
        ];

        return [
            ['grade' => 1, 'code' => 'I-AS-SALAM', 'name' => 'I As-Salam (Fullday)', 'homeroom' => 'Farisa Aufi Saputri, S.Pd.', 'slots' => array_merge($gradeOneSlots, [
                ['12:45', '13:20', ['QH','QH','QH','QH',null,null]], ['13:20', '13:55', ['QH','QH','QH','QH',null,null]], ['13:55', '14:30', ['NUM','LIT','LUG','STEAM',null,null]], ['14:30', '15:05', ['NUM','LIT','LUG','STEAM',null,null]],
            ])],
            ['grade' => 1, 'code' => 'I-AR-RAHMAN', 'name' => 'I Ar-Rahman', 'homeroom' => 'Zumaja Laili, S.Pd.', 'slots' => $gradeOneSlots],
            ['grade' => 1, 'code' => 'I-AR-RAHIM', 'name' => 'I Ar-Rahim', 'homeroom' => 'Ayu Suryaningsih, S.Pd.', 'slots' => $gradeOneSlots],
            ['grade' => 2, 'code' => 'II-AL-MUMIN', 'name' => "II Al-Mu'min", 'homeroom' => 'Mawadatuz Zahro, S.Pd.', 'slots' => $lowerSlots],
            ['grade' => 2, 'code' => 'II-AL-WAHHAB', 'name' => 'II Al-Wahhab', 'homeroom' => "Ro'is Ro'datul Urbah, S.Pd.", 'slots' => $lowerSlots],
            ['grade' => 2, 'code' => 'II-AL-LATHIF', 'name' => 'II Al-Lathif', 'homeroom' => 'Ummi Al Ivadah, S.Pd.', 'slots' => $lowerSlots],
            ['grade' => 3, 'code' => 'III-AL-KHALIQ', 'name' => 'III Al-Khaliq', 'homeroom' => 'Dewi Shofiyah, S.Pd.', 'slots' => $lowerSlots],
            ['grade' => 3, 'code' => 'III-AL-MAJID', 'name' => 'III Al-Majid', 'homeroom' => "Laily Rizqi Amaliah, S.Pd.", 'slots' => $lowerSlots],
            ['grade' => 4, 'code' => 'IV-AL-BASITH', 'name' => 'IV Al-Basith', 'homeroom' => 'Hambali, S.Pd.I.', 'slots' => $lowerSlots],
            ['grade' => 4, 'code' => 'IV-AL-KARIM', 'name' => 'IV Al-Karim', 'homeroom' => 'Qonita Nasyiatul Wahdah, S.Ag.', 'slots' => $lowerSlots],
            ['grade' => 5, 'code' => 'V-AL-ALIM', 'name' => "V Al-'Alim", 'homeroom' => 'Dyah Ayu Febriani, S.Pd.', 'slots' => $lowerSlots],
            ['grade' => 6, 'code' => 'VI-AL-MAJID', 'name' => 'VI Al-Majid', 'homeroom' => "Laily Rizqi Amaliah, S.Pd.", 'slots' => $lowerSlots],
        ];
    }
}
