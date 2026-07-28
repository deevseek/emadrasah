<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MimnuTeachingAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $year = AcademicYear::query()->where('name', '2026/2027')->with('semesters')->firstOrFail();
            $employees = Employee::query()->whereIn('employee_number', range(1, 24))->get()->keyBy('employee_number');
            $classrooms = Classroom::query()->where('academic_year_id', $year->id)->get()->keyBy('code');
            $subjects = Subject::query()->get()->keyBy('code');

            foreach ($this->assignments() as [$employeeCode, $classroomCodes, $subjectCode, $hours]) {
                $employee = $employees[(string) $employeeCode] ?? throw new RuntimeException("Guru kode {$employeeCode} tidak ditemukan.");
                $subject = $subjects[$subjectCode] ?? throw new RuntimeException("Mata pelajaran {$subjectCode} tidak ditemukan.");

                foreach ($year->semesters as $semester) {
                    foreach ($classroomCodes as $classroomCode) {
                        $classroom = $classrooms[$classroomCode] ?? throw new RuntimeException("Kelas {$classroomCode} tidak ditemukan.");
                        TeachingAssignment::query()->updateOrCreate(
                            [
                                'academic_year_id' => $year->id,
                                'semester_id' => $semester->id,
                                'employee_id' => $employee->id,
                                'classroom_id' => $classroom->id,
                                'subject_id' => $subject->id,
                            ],
                            [
                                'weekly_hours' => $hours,
                                'is_active' => true,
                                'starts_on' => $semester->starts_on,
                                'ends_on' => $semester->ends_on,
                                'notes' => 'Pembagian tugas guru MI Muslimat NU Demak tahun ajaran 2026/2027.',
                                'source_reference' => 'PEMBAGIAN TUGAS GURU MIMNU 2026-2027(1).xlsx',
                            ],
                        );
                    }
                }
            }
        });
    }

    private function assignments(): array
    {
        $lower = ['QH' => 2, 'AA' => 2, 'FIQ' => 2, 'BAR' => 2, 'PKN' => 4, 'BINDO' => 6, 'MTK' => 4, 'PJOK' => 2, 'SBDP' => 2, 'BING' => 1, 'BAJA' => 1, 'TIK' => 2, 'KE-NU-AN' => 2, 'STEAM' => 2];
        $middle = ['QH' => 2, 'AA' => 2, 'FIQ' => 2, 'SKI' => 2, 'PKN' => 4, 'BINDO' => 6, 'MTK' => 4, 'IPAS' => 5, 'PJOK' => 2, 'SBDP' => 2, 'BING' => 1, 'BAJA' => 1, 'TIK' => 2, 'KE-NU-AN' => 2, 'STEAM' => 2];
        $upper = ['QH' => 2, 'AA' => 2, 'FIQ' => 2, 'SKI' => 2, 'PKN' => 4, 'BINDO' => 6, 'MTK' => 4, 'IPAS' => 5, 'PJOK' => 2, 'SBDP' => 2, 'BING' => 1, 'BAJA' => 1, 'TIK' => 2, 'KE-NU-AN' => 2];
        $rows = [];

        foreach ([['2', 'I-AR-RAHMAN'], ['3', 'I-AR-RAHIM'], ['4', 'I-AS-SALAM'], ['5', 'II-AL-MUMIN'], ['6', 'II-AL-WAHHAB']] as [$employee, $classroom]) {
            $this->append($rows, $employee, [$classroom], $lower);
        }
        $this->append($rows, '4', ['I-AS-SALAM'], ['NUM' => 2, 'LIT' => 2]);

        foreach ([['7', 'III-AL-KHALIQ'], ['8', 'III-AL-LATHIF']] as [$employee, $classroom]) {
            $this->append($rows, $employee, [$classroom], $middle);
        }

        foreach ([['9', 'IV-AL-BASITH'], ['10', 'IV-AL-KARIM'], ['11', 'V-AL-ALIM'], ['12', 'V-AL-HAKIM']] as [$employee, $classroom]) {
            $this->append($rows, $employee, [$classroom], $upper);
        }
        $this->append($rows, '13', ['VI-AL-MAJID'], $upper + ['TKA' => 2]);

        foreach ([
            ['14', ['I-AR-RAHMAN', 'I-AR-RAHIM']],
            ['15', ['I-AS-SALAM']],
            ['16', ['II-AL-MUMIN', 'II-AL-WAHHAB']],
            ['17', ['III-AL-KHALIQ', 'III-AL-LATHIF']],
            ['18', ['IV-AL-BASITH', 'IV-AL-KARIM']],
            ['19', ['V-AL-ALIM', 'V-AL-HAKIM']],
            ['20', ['VI-AL-MAJID']],
        ] as [$employee, $classrooms]) {
            $rows[] = [$employee, $classrooms, 'BTAQ', 10];
        }

        $this->append($rows, '21', ['I-AS-SALAM'], ['QH' => 2, 'TAQ' => 2, 'LA' => 2]);
        $rows[] = ['22', ['IV-AL-BASITH', 'IV-AL-KARIM', 'V-AL-ALIM', 'V-AL-HAKIM', 'VI-AL-MAJID'], 'BAR', 2];

        return $rows;
    }

    private function append(array &$rows, string $employee, array $classrooms, array $subjects): void
    {
        foreach ($subjects as $subject => $hours) {
            $rows[] = [$employee, $classrooms, $subject, $hours];
        }
    }
}
