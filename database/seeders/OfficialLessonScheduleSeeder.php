<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ScheduleEntryType;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class OfficialLessonScheduleSeeder extends Seeder
{
    private const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

    public function run(): void
    {
        $this->ensureUnassignedSchedulesSupported();

        $year = AcademicYear::firstOrCreate(['name' => '2026/2027'], ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::firstOrCreate(['academic_year_id' => $year->id, 'term' => 1], ['name' => 'Ganjil', 'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'is_active' => true]);

        $subjects = $this->existingSubjects();
        $classrooms = collect($this->classSchedules())->mapWithKeys(fn (array $classData): array => [
            $classData['code'] => $this->existingClassroom($year, $classData),
        ]);

        DB::transaction(function () use ($year, $semester, $subjects, $classrooms): void {
            foreach ($this->classSchedules() as $classData) {
                $classroom = $classrooms[$classData['code']];

                foreach ($classData['slots'] as [$start, $end, $dailySubjects]) {
                    foreach (self::DAYS as $index => $day) {
                        $code = $dailySubjects[$index] ?? null;
                        if (! $code || str_starts_with($code, 'BREAK')) {
                            continue;
                        }

                        $activityName = $this->activityName($code);
                        $subject = $activityName === null ? $subjects[$code] : null;
                        LessonSchedule::firstOrCreate([
                            'semester_id' => $semester->id,
                            'classroom_id' => $classroom->id,
                            'day_of_week' => $day,
                            'starts_at' => $start,
                            'ends_at' => $end,
                        ], [
                            'teaching_assignment_id' => null,
                            'entry_type' => $activityName === null ? ScheduleEntryType::Lesson : ScheduleEntryType::Activity,
                            'activity_name' => $activityName,
                            'academic_year_id' => $year->id,
                            'subject_id' => $subject?->id,
                            'employee_id' => null,
                            'lesson_hours' => 1,
                            'counts_as_teaching_hour' => $activityName === null,
                            'room' => $classroom->room,
                            'is_active' => true,
                            'notes' => 'Diimpor dari jadwal resmi MI Muslimat NU Demak TA 2026/2027 semester ganjil berdasarkan tangkapan layar. Guru pengampu belum ditetapkan sesuai instruksi.',
                        ]);
                    }
                }
            }
        });
    }


    private function activityName(string $code): ?string
    {
        return match ($code) {
            'PAGI' => 'Pembiasaan Pagi/Sholat Dhuha',
            'TASMI' => 'Tasmi’',
            'IST' => 'Istigotsah',
            default => null,
        };
    }

    /**
     * @return \Illuminate\Support\Collection<string, Subject>
     */
    private function existingSubjects(): \Illuminate\Support\Collection
    {
        $subjects = Subject::query()->where('is_active', true)->get();
        $mapped = collect();
        $missing = collect();

        foreach ($this->subjectAliases() as $code => $aliases) {
            $subject = $subjects->first(fn (Subject $subject): bool => $this->matchesAny($subject, $aliases));

            if ($subject) {
                $mapped[$code] = $subject;
            } else {
                $missing->push($code);
            }
        }

        if ($missing->isNotEmpty()) {
            throw new \RuntimeException('Mata pelajaran belum tersedia atau kodenya belum sesuai: '.$missing->implode(', ').'. Seeder ini tidak membuat mata pelajaran baru.');
        }

        return $mapped;
    }

    private function existingClassroom(AcademicYear $year, array $classData): Classroom
    {
        $aliases = $this->classroomAliases($classData);
        $classroom = Classroom::query()
            ->where('academic_year_id', $year->id)
            ->get()
            ->first(fn (Classroom $classroom): bool => $this->matchesAny($classroom, $aliases));

        if (! $classroom) {
            throw new \RuntimeException('Kelas '.$classData['code'].' / '.$classData['name'].' belum tersedia. Seeder ini tidak membuat kelas baru.');
        }

        return $classroom;
    }

    private function matchesAny(Subject|Classroom $model, array $aliases): bool
    {
        $values = array_filter([$model->code ?? null, $model->name ?? null, $model->short_name ?? null]);

        foreach ($values as $value) {
            if (in_array($this->normalize((string) $value), $aliases, true)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = str($value)->lower()->ascii()->replace(["'", '’'], '')->toString();

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function normalizedAliases(array $aliases): array
    {
        return array_values(array_unique(array_map(fn (string $alias): string => $this->normalize($alias), $aliases)));
    }

    private function ensureUnassignedSchedulesSupported(): void
    {
        $column = collect(Schema::getColumns('lesson_schedules'))->firstWhere('name', 'employee_id');
        $nullable = $column['nullable'] ?? true;
        if ($nullable === true || $nullable === 'YES' || $nullable === 'yes') {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE lesson_schedules MODIFY employee_id BIGINT UNSIGNED NULL');
            return;
        }

        throw new \RuntimeException('Kolom lesson_schedules.employee_id belum nullable. Jalankan php artisan migrate sebelum menjalankan OfficialLessonScheduleSeeder.');
    }

    private function subjectAliases(): array
    {
        return collect([
            'BTAQ' => ['BTAQ'],
            'PKN' => ['PKN', 'Pendidikan Pancasila', 'Pend. Pancasila'],
            'QH' => ['QH', "Al-Qur'an Hadits", 'Al Quran Hadits', 'Al-Quran Hadits'],
            'PJOK' => ['PJOK'],
            'TIK' => ['TIK', 'Literasi Digital', 'Literasi Digital TIK Koding dan Kecerdasan Artifisial'],
            'BINDO' => ['BINDO', 'Bahasa Indonesia', 'B. Indonesia'],
            'MTK' => ['MTK', 'Matematika'],
            'BAR' => ['BAR', 'Bahasa Arab', 'B. Arab'],
            'KE-NU-AN' => ['KE-NU-AN', 'Ke-NU-an', 'KeNUan'],
            'AA' => ['AA', 'Aqidah Akhlaq', 'Akidah Akhlak'],
            'SBDP' => ['SBDP', 'SBdP', 'Seni Budaya dan Prakarya'],
            'FIQ' => ['FIQ', 'Fiqih', 'Fikih'],
            'BING' => ['BING', 'Bahasa Inggris', 'B. Inggris'],
            'BAJA' => ['BAJA', 'Bahasa Jawa', 'B. Jawa'],
            'NUM' => ['NUM', 'Numerasi'],
            'LIT' => ['LIT', 'Literasi'],
            'LA' => ['LA', 'Lughoh Arobiyah', 'Lughoh Arabiyah'],
            'STEAM' => ['STEAM', 'Science Technology Engineering Arts and Mathematics'],
            'IPAS' => ['IPAS'],
            'SKI' => ['SKI', 'Sejarah Kebudayaan Islam'],
            'TKA' => ['TKA'],
            'TAQ' => ['TAQ', "Takhassus Al-Qur'an", 'Takhassus Al-Qur’an'],
        ])->map(fn (array $aliases): array => $this->normalizedAliases($aliases))->all();
    }

    private function classroomAliases(array $classData): array
    {
        $code = $classData['code'];
        $name = $classData['name'];
        $withoutFullday = str_replace(['(Fullday)', 'Fullday', 'Full Day'], '', $name);
        $spacedCode = str_replace('-', ' ', $code);

        return $this->normalizedAliases([
            $code,
            $name,
            $withoutFullday,
            $spacedCode,
            'Kelas '.$name,
            'Kelas '.$withoutFullday,
            'Kelas '.$spacedCode,
        ]);
    }

    private function classSchedules(): array
    {
        $gradeOneSlots = [
            ['06:50', '07:15', ['PAGI','PAGI','PAGI','PAGI','PAGI','PAGI']],
            ['07:15', '07:50', ['BTAQ','BTAQ','BTAQ','BTAQ','TASMI','BTAQ']],
            ['07:50', '08:25', ['BTAQ','BTAQ','BTAQ','BTAQ','IST','BTAQ']],
            ['08:25', '09:00', ['PKN','QH','PKN','PJOK','TIK','BINDO']],
            ['09:00', '09:35', ['PKN','QH','PKN','PJOK','TIK','BINDO']],
            ['10:00', '10:35', ['BINDO','MTK','BINDO','MTK','BAR','KE-NU-AN']],
            ['10:35', '11:10', ['BINDO','MTK','BINDO','MTK','BAR','STEAM']],
            ['11:10', '11:45', ['AA','SBDP','FIQ','BING',null,null]],
            ['11:45', '12:10', ['AA','SBDP','FIQ','BAJA',null,null]],
        ];

        $lowerSlots = [
            ['06:50', '07:15', ['PAGI','PAGI','PAGI','PAGI','PAGI','PAGI']],
            ['07:15', '07:50', ['PKN','MTK','PJOK','IPAS','TASMI','PKN']],
            ['07:50', '08:25', ['PKN','MTK','PJOK','IPAS','IST','PKN']],
            ['08:25', '09:00', ['BTAQ','BTAQ','BTAQ','BTAQ','TIK','BTAQ']],
            ['09:00', '09:35', ['BTAQ','BTAQ','BTAQ','BTAQ','TIK','BTAQ']],
            ['10:00', '10:35', ['BINDO','MTK','BINDO','MTK','BAR','KE-NU-AN']],
            ['10:35', '11:10', ['BINDO','MTK','BINDO','MTK','BAR','STEAM']],
            ['11:10', '11:45', ['AA','SBDP','FIQ','BING',null,null]],
            ['11:45', '12:10', ['AA','SBDP','FIQ','MTK',null,null]],
            ['12:30', '13:05', ['IPAS','LIT','SKI','PKN',null,null]],
            ['13:05', '13:40', ['IPAS','LIT','SKI','PKN',null,null]],
        ];

        return [
            ['code' => 'I-AS-SALAM', 'name' => 'I As-Salam (Fullday)', 'slots' => array_merge($gradeOneSlots, [
                ['12:45', '13:20', ['QH','QH','QH','QH',null,null]], ['13:20', '13:55', ['QH','QH','QH','QH',null,null]], ['13:55', '14:30', ['NUM','LIT','LA','STEAM',null,null]], ['14:30', '15:05', ['NUM','LIT','LA','STEAM',null,null]],
            ])],
            ['code' => 'I-AR-RAHMAN', 'name' => 'I Ar-Rahman', 'slots' => $gradeOneSlots],
            ['code' => 'I-AR-RAHIM', 'name' => 'I Ar-Rahim', 'slots' => $gradeOneSlots],
            ['code' => 'II-AL-MUMIN', 'name' => "II Al-Mu'min", 'slots' => $lowerSlots],
            ['code' => 'II-AL-WAHHAB', 'name' => 'II Al-Wahhab', 'slots' => $lowerSlots],
            ['code' => 'III-AL-KHALIQ', 'name' => 'III Al-Khaliq', 'slots' => $lowerSlots],
            ['code' => 'III-AL-LATHIF', 'name' => 'III Al-Lathif', 'slots' => $lowerSlots],
            ['code' => 'IV-AL-BASITH', 'name' => 'IV Al-Basith', 'slots' => $lowerSlots],
            ['code' => 'IV-AL-KARIM', 'name' => 'IV Al-Karim', 'slots' => $lowerSlots],
            ['code' => 'V-AL-ALIM', 'name' => "V Al-'Alim", 'slots' => $lowerSlots],
            ['code' => 'V-AL-HAKIM', 'name' => 'V Al-Hakim', 'slots' => $lowerSlots],
            ['code' => 'VI-AL-MAJID', 'name' => 'VI Al-Majid', 'slots' => $lowerSlots],
        ];
    }
}
