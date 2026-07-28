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

    private const SOURCE_PREFIX = 'jadwal-resmi-mi-muslimat-2026-2027-ganjil';

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
            // Bersihkan data versi lama yang dibuat seeder ini sebelum source_reference digunakan.
            LessonSchedule::query()
                ->where('semester_id', $semester->id)
                ->whereIn('classroom_id', $classrooms->pluck('id'))
                ->whereNull('source_reference')
                ->where('notes', 'like', 'Diimpor dari jadwal resmi MI Muslimat NU Demak%')
                ->delete();

            $sourceReferences = [];

            foreach ($this->classSchedules() as $classData) {
                $classroom = $classrooms[$classData['code']];

                foreach ($classData['slots'] as [$start, $end, $dailySubjects]) {
                    foreach (self::DAYS as $index => $day) {
                        $code = $dailySubjects[$index] ?? null;
                        if (! $code) {
                            continue;
                        }

                        [$entryType, $activityName] = $this->entryDetails($code);
                        $subject = $entryType === ScheduleEntryType::Lesson ? $subjects[$code] : null;
                        $sourceReference = implode(':', [self::SOURCE_PREFIX, $classData['code'], $day, $start, $end]);
                        $sourceReferences[] = $sourceReference;

                        LessonSchedule::updateOrCreate(['source_reference' => $sourceReference], [
                            'semester_id' => $semester->id,
                            'classroom_id' => $classroom->id,
                            'day_of_week' => $day,
                            'starts_at' => $start,
                            'ends_at' => $end,
                            'teaching_assignment_id' => null,
                            'entry_type' => $entryType,
                            'activity_name' => $activityName,
                            'academic_year_id' => $year->id,
                            'subject_id' => $subject?->id,
                            'employee_id' => null,
                            'lesson_hours' => 1,
                            'counts_as_teaching_hour' => $entryType === ScheduleEntryType::Lesson,
                            'room' => $classroom->room,
                            'is_active' => true,
                            'notes' => 'Diimpor dari jadwal resmi MI Muslimat NU Demak TA 2026/2027 semester ganjil berdasarkan tangkapan layar. Guru pengampu belum ditetapkan sesuai instruksi.',
                            'source_reference' => $sourceReference,
                        ]);
                    }
                }
            }

            LessonSchedule::query()
                ->where('source_reference', 'like', self::SOURCE_PREFIX.':%')
                ->whereNotIn('source_reference', $sourceReferences)
                ->delete();
        });
    }

    /** @return array{ScheduleEntryType, ?string} */
    private function entryDetails(string $code): array
    {
        if (str_starts_with($code, 'BREAK:')) {
            return [ScheduleEntryType::Break, substr($code, 6)];
        }

        $activityName = match ($code) {
            'UPACARA_PAGI' => 'Upacara + Pembiasaan Pagi + Sholat Dhuha',
            'PAGI' => 'Pembiasaan Pagi + Sholat Dhuha',
            'TASMI' => 'Tasmi’',
            'IST' => 'Istigotsah',
            'MISSION' => 'Mission Accomplished — “Tutup Buku, Buka Cerita”',
            default => null,
        };

        return [$activityName === null ? ScheduleEntryType::Lesson : ScheduleEntryType::Activity, $activityName];
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
        $morning = ['06:50', '07:15', ['UPACARA_PAGI', 'PAGI', 'PAGI', 'PAGI', 'PAGI', 'PAGI']];
        $morningBreak = ['09:35', '10:00', array_fill(0, 6, 'BREAK:Istirahat')];
        $regularClosingBreaks = [
            ['12:10', '12:30', ['BREAK:Istirahat/Sholat/Pulang', 'BREAK:Istirahat/Sholat/Pulang', 'BREAK:Istirahat/Sholat/Pulang', 'BREAK:Istirahat/Sholat/Pulang', null, null]],
            ['11:10', '11:45', [null, null, null, null, 'BREAK:Istirahat/Sholat/Pulang', 'BREAK:Istirahat/Sholat/Pulang']],
        ];

        $gradeOne = [
            $morning,
            ['07:15', '07:50', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TASMI', 'BTAQ']],
            ['07:50', '08:25', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'IST', 'BTAQ']],
            ['08:25', '09:00', ['PKN', 'QH', 'PKN', 'PJOK', 'TIK', 'BINDO']],
            ['09:00', '09:35', ['PKN', 'QH', 'PKN', 'PJOK', 'TIK', 'BINDO']],
            ['10:00', '10:35', ['BINDO', 'MTK', 'BINDO', 'MTK', 'BAR', 'KE-NU-AN']],
            ['10:35', '11:10', ['BINDO', 'MTK', 'BINDO', 'MTK', 'BAR', 'STEAM']],
            ['11:10', '11:45', ['AA', 'SBDP', 'FIQ', 'BING', null, null]],
            ['11:45', '12:10', ['AA', 'SBDP', 'FIQ', 'BAJA', null, null]],
        ];

        $gradeOneArRahim = [
            $morning,
            ['07:15', '07:50', ['PKN', 'QH', 'PKN', 'PJOK', 'TASMI', 'BINDO']],
            ['07:50', '08:25', ['PKN', 'QH', 'PKN', 'PJOK', 'IST', 'BINDO']],
            ['08:25', '09:00', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TIK', 'BTAQ']],
            ['09:00', '09:35', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TIK', 'BTAQ']],
            ...array_slice($gradeOne, 5),
        ];

        $gradeOneFullday = $gradeOne;
        $gradeOneFullday[6][2][5] = 'KE-NU-AN';
        $gradeOneFullday[7][2][4] = 'TAQ';
        $gradeOneFullday[7][2][5] = 'TAQ';
        $gradeOneFullday[8][2][5] = 'TAQ';

        $gradeTwo = [
            $morning,
            ['07:15', '07:50', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TASMI', 'BTAQ']],
            ['07:50', '08:25', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'IST', 'BTAQ']],
            ['08:25', '09:00', ['PKN', 'QH', 'PKN', 'BINDO', 'TIK', 'PJOK']],
            ['09:00', '09:35', ['PKN', 'QH', 'PKN', 'BINDO', 'TIK', 'PJOK']],
            ['10:00', '10:35', ['BINDO', 'MTK', 'BINDO', 'MTK', 'BAR', 'KE-NU-AN']],
            ['10:35', '11:10', ['BINDO', 'MTK', 'BINDO', 'MTK', 'BAR', 'STEAM']],
            ['11:10', '11:45', ['AA', 'SBDP', 'FIQ', 'BING', null, null]],
            ['11:45', '12:10', ['AA', 'SBDP', 'FIQ', 'BAJA', null, null]],
        ];

        $gradeThree = [
            $morning,
            ['07:15', '07:50', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TASMI', 'BTAQ']],
            ['07:50', '08:25', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'IST', 'BTAQ']],
            ['08:25', '09:00', ['PKN', 'PJOK', 'IPAS', 'AA', 'BAJA', 'SBDP']],
            ['09:00', '09:35', ['PKN', 'PJOK', 'IPAS', 'AA', 'BING', 'SBDP']],
            ['10:00', '10:35', ['BINDO', 'MTK', 'BINDO', 'IPAS', 'BINDO', 'KE-NU-AN']],
            ['10:35', '11:10', ['BINDO', 'MTK', 'BINDO', 'MTK', 'BINDO', 'STEAM']],
            ['11:10', '11:45', ['IPAS', 'BAR', 'FIQ', 'MTK', null, null]],
            ['11:45', '12:10', ['IPAS', 'BAR', 'FIQ', 'MTK', null, null]],
            ['12:30', '13:05', ['QH', 'TIK', 'SKI', 'PKN', null, null]],
            ['13:05', '13:40', ['QH', 'TIK', 'SKI', 'PKN', null, null]],
        ];

        $gradeFourBasith = [
            $morning,
            ['07:15', '07:50', ['PKN', 'MTK', 'PJOK', 'IPAS', 'TASMI', 'PKN']],
            ['07:50', '08:25', ['PKN', 'MTK', 'PJOK', 'IPAS', 'IST', 'PKN']],
            ['08:25', '09:00', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'BAJA', 'BTAQ']],
            ['09:00', '09:35', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'BING', 'BTAQ']],
            ['10:00', '10:35', ['BINDO', 'BINDO', 'BINDO', 'IPAS', 'BAR', 'KE-NU-AN']],
            ['10:35', '11:10', ['BINDO', 'BINDO', 'BINDO', 'MTK', 'BAR', 'STEAM']],
            ['11:10', '11:45', ['IPAS', 'QH', 'FIQ', 'MTK', null, null]],
            ['11:45', '12:10', ['IPAS', 'QH', 'FIQ', 'MTK', null, null]],
            ['12:30', '13:05', ['AA', 'TIK', 'SKI', 'SBDP', null, null]],
            ['13:05', '13:40', ['AA', 'TIK', 'SKI', 'SBDP', null, null]],
        ];

        $gradeFourKarim = [
            $morning,
            ['07:15', '07:50', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TASMI', 'BTAQ']],
            ['07:50', '08:25', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'IST', 'BTAQ']],
            ['08:25', '09:00', ['PKN', 'MTK', 'PJOK', 'IPAS', 'BAJA', 'PKN']],
            ['09:00', '09:35', ['PKN', 'MTK', 'PJOK', 'IPAS', 'BING', 'PKN']],
            ['10:00', '10:35', ['BINDO', 'QH', 'BINDO', 'IPAS', 'BAR', 'KE-NU-AN']],
            ['10:35', '11:10', ['BINDO', 'QH', 'BINDO', 'MTK', 'BAR', 'STEAM']],
            ...array_slice($gradeFourBasith, 7),
        ];

        $gradeFiveAlim = [
            $morning,
            ['07:15', '07:50', ['PKN', 'FIQ', 'IPAS', 'PJOK', 'TASMI', 'BINDO']],
            ['07:50', '08:25', ['PKN', 'FIQ', 'IPAS', 'PJOK', 'IST', 'BINDO']],
            ['08:25', '09:00', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TIK', 'BTAQ']],
            ['09:00', '09:35', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TIK', 'BTAQ']],
            ['10:00', '10:35', ['BINDO', 'MTK', 'BINDO', 'MTK', 'BAR', 'KE-NU-AN']],
            ['10:35', '11:10', ['BINDO', 'MTK', 'BINDO', 'MTK', 'BAR', 'STEAM']],
            ['11:10', '11:45', ['AA', 'SBDP', 'QH', 'MTK', null, null]],
            ['11:45', '12:10', ['AA', 'SBDP', 'QH', 'BAJA', null, null]],
            ['12:30', '13:05', ['IPAS', 'IPAS', 'SKI', 'PKN', null, null]],
            ['13:05', '13:40', ['IPAS', 'BING', 'SKI', 'PKN', null, null]],
        ];

        $gradeFiveHakim = $gradeFiveAlim;
        $gradeFiveHakim[1][2] = ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TASMI', 'BTAQ'];
        $gradeFiveHakim[2][2] = ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'IST', 'BTAQ'];
        $gradeFiveHakim[3][2] = ['PKN', 'FIQ', 'IPAS', 'PJOK', 'TIK', 'BINDO'];
        $gradeFiveHakim[4][2] = ['BTAQ', 'FIQ', 'IPAS', 'PJOK', 'TIK', 'BINDO'];

        $gradeSix = [
            $morning,
            ['07:15', '07:50', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'TASMI', 'BTAQ']],
            ['07:50', '08:25', ['BTAQ', 'BTAQ', 'BTAQ', 'BTAQ', 'IST', 'BTAQ']],
            ['08:25', '09:00', ['PKN', 'MTK', 'QH', 'PJOK', 'BINDO', 'TKA']],
            ['09:00', '09:35', ['PKN', 'MTK', 'QH', 'PJOK', 'BINDO', 'TKA']],
            ['10:00', '10:35', ['BINDO', 'MTK', 'IPAS', 'BING', 'TIK', 'SBDP']],
            ['10:35', '11:10', ['BINDO', 'BAJA', 'IPAS', 'KE-NU-AN', 'TIK', 'SBDP']],
            ['11:10', '11:45', ['AA', 'SKI', 'FIQ', 'MTK', null, null]],
            ['11:45', '12:10', ['AA', 'SKI', 'FIQ', 'MTK', null, null]],
            ['12:30', '13:05', ['IPAS', 'BAR', 'BINDO', 'PKN', null, null]],
            ['13:05', '13:40', ['IPAS', 'BAR', 'BINDO', 'PKN', null, null]],
        ];

        return [
            ['code' => 'I-AS-SALAM', 'name' => 'I As-Salam (Fullday)', 'slots' => [
                ...$gradeOneFullday,
                $morningBreak,
                ['12:10', '12:45', ['BREAK:Ishoma', 'BREAK:Ishoma', 'BREAK:Ishoma', 'BREAK:Ishoma', null, 'BREAK:Ishoma/Pulang']],
                ['11:45', '12:25', [null, null, null, null, 'BREAK:Ishoma/Pulang', null]],
                ['12:45', '13:20', ['TAQ', 'TAQ', 'TAQ', 'TAQ', null, null]],
                ['13:20', '13:55', ['TAQ', 'TAQ', 'TAQ', 'TAQ', null, null]],
                ['13:55', '14:30', ['NUM', 'LIT', 'LA', 'STEAM', null, null]],
                ['14:30', '15:05', ['NUM', 'LIT', 'LA', 'STEAM', null, null]],
                ['12:25', '13:00', [null, null, null, null, 'TAQ', null]],
                ['13:00', '13:20', [null, null, null, null, 'MISSION', null]],
                ['15:05', '15:15', ['BREAK:Istirahat/Sholat/Pulang', 'BREAK:Istirahat/Sholat/Pulang', 'BREAK:Istirahat/Sholat/Pulang', 'BREAK:Istirahat/Sholat/Pulang', null, null]],
            ]],
            ['code' => 'I-AR-RAHMAN', 'name' => 'I Ar-Rahman', 'slots' => [...$gradeOne, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'I-AR-RAHIM', 'name' => 'I Ar-Rahim', 'slots' => [...$gradeOneArRahim, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'II-AL-MUMIN', 'name' => "II Al-Mu'min", 'slots' => [...$gradeTwo, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'II-AL-WAHHAB', 'name' => 'II Al-Wahhab', 'slots' => [...$gradeTwo, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'III-AL-KHALIQ', 'name' => 'III Al-Khaliq', 'slots' => [...$gradeThree, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'III-AL-LATHIF', 'name' => 'III Al-Lathif', 'slots' => [...$gradeThree, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'IV-AL-BASITH', 'name' => 'IV Al-Basith', 'slots' => [...$gradeFourBasith, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'IV-AL-KARIM', 'name' => 'IV Al-Karim', 'slots' => [...$gradeFourKarim, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'V-AL-ALIM', 'name' => "V Al-'Alim", 'slots' => [...$gradeFiveAlim, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'V-AL-HAKIM', 'name' => 'V Al-Hakim', 'slots' => [...$gradeFiveHakim, $morningBreak, ...$regularClosingBreaks]],
            ['code' => 'VI-AL-MAJID', 'name' => 'VI Al-Majid', 'slots' => [...$gradeSix, $morningBreak, ...$regularClosingBreaks]],
        ];
    }
}
