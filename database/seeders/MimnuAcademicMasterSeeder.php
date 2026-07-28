<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\HomeroomAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class MimnuAcademicMasterSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $year = AcademicYear::query()->where('name', '2026/2027')->firstOrFail();
            $levels = collect(range(1, 6))->mapWithKeys(fn (int $level): array => [
                $level => GradeLevel::query()->updateOrCreate(
                    ['level' => $level],
                    ['name' => "Kelas {$level}", 'code' => "K{$level}", 'is_active' => true],
                ),
            ]);

            $employees = collect($this->employees())->mapWithKeys(function (array $data): array {
                $identity = ['employee_number' => $data['employee_number']];
                $employee = Employee::withTrashed()->firstOrNew($identity);
                $employee->fill($data + [
                    'employee_status' => EmployeeStatus::Other,
                    'is_active' => true,
                ]);
                $employee->deleted_at = null;
                $employee->save();

                return [$data['employee_number'] => $employee];
            });

            foreach ($this->classrooms() as $data) {
                $teacher = $employees[$data['teacher_code']];
                $classroom = Classroom::query()->updateOrCreate(
                    ['academic_year_id' => $year->id, 'code' => $data['code']],
                    [
                        'grade_level_id' => $levels[$data['level']]->id,
                        'name' => $data['name'],
                        'homeroom_teacher_id' => $teacher->id,
                        'is_active' => true,
                    ],
                );

                HomeroomAssignment::query()->updateOrCreate(
                    ['academic_year_id' => $year->id, 'classroom_id' => $classroom->id],
                    [
                        'employee_id' => $teacher->id,
                        'started_at' => $year->starts_on,
                        'ended_at' => $year->ends_on,
                        'is_active' => true,
                        'reason' => 'Pembagian tugas guru MI Muslimat NU Demak tahun ajaran 2026/2027.',
                    ],
                );
            }
        });
    }

    private function employees(): array
    {
        return [
            $this->employee('1', 'USWATUN KHASANAH', 'S.Pd.I., M.Pd.', Gender::Female, EmploymentType::Principal, 'Kepala Madrasah', 24),
            $this->employee('2', 'ZUMALA LAILI', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas I Ar-Rahman / Wali Kelas', 40),
            $this->employee('3', 'AYU SURYANINGSIH', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas I Ar-Rahim / Wali Kelas', 40),
            $this->employee('4', 'FARISA AUFI SAPUTRI', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas I As-Salam / Wali Kelas', 44),
            $this->employee('5', 'MAWADATUZZAHRO', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, "Guru Kelas II Al-Mu'min / Wali Kelas", 40),
            $this->employee('6', "RO'IS RO'DATUL URBAH", 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas II Al-Wahhab / Wali Kelas', 40),
            $this->employee('7', 'DEWI SHOFIYAH', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas III Al-Khaliq / Wali Kelas', 47),
            $this->employee('8', 'UMMI AL IVADAH', null, Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas III Al-Lathif / Wali Kelas', 47),
            $this->employee('9', 'HAMBALI', 'S.Pd.I.', Gender::Male, EmploymentType::ClassTeacher, 'Guru Kelas IV Al-Basith / Wali Kelas', 45),
            $this->employee('10', 'NUR LAILA AZIZAH', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas IV Al-Karim / Wali Kelas', 45),
            $this->employee('11', 'DYAH AYU FEBRIANI', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, "Guru Kelas V Al-'Alim / Wali Kelas", 45),
            $this->employee('12', 'SITI MUNIROH', 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas V Al-Hakim / Wali Kelas', 45),
            $this->employee('13', "LAILY RIZQI 'AMALIAH", 'S.Pd.', Gender::Female, EmploymentType::ClassTeacher, 'Guru Kelas VI Al-Majid / Wali Kelas', 45),
            $this->employee('14', 'NUSROH MUFIDAH', 'AH.', Gender::Female, EmploymentType::BtaqTeacher, 'Guru BTAQ Kelas I Ar-Rahman dan I Ar-Rahim', 10),
            $this->employee('15', 'NUR ROHMAH', 'S.Ag., M.Pd.', Gender::Female, EmploymentType::BtaqTeacher, 'Guru BTAQ Kelas I As-Salam', 10),
            $this->employee('16', 'IDA SALMA', 'AH.', Gender::Female, EmploymentType::BtaqTeacher, "Guru BTAQ Kelas II Al-Mu'min dan II Al-Wahhab", 10),
            $this->employee('17', 'WINDY LESTARI', 'AH.', Gender::Female, EmploymentType::BtaqTeacher, 'Guru BTAQ Kelas III Al-Khaliq dan III Al-Lathif', 10),
            $this->employee('18', 'KHOIRIYAH', 'AH.', Gender::Female, EmploymentType::BtaqTeacher, 'Guru BTAQ Kelas IV Al-Basith dan IV Al-Karim', 10),
            $this->employee('19', 'NUR AINI MUFAROKAH', 'AH.', Gender::Female, EmploymentType::BtaqTeacher, "Guru BTAQ Kelas V Al-'Alim dan V Al-Hakim", 10),
            $this->employee('20', 'ALFA INAYATIS SANIYAH', 'AH.', Gender::Female, EmploymentType::BtaqTeacher, 'Guru BTAQ Kelas VI Al-Majid', 10),
            $this->employee('21', 'QONITA NASYIATUL WAHDAH', 'S.Ag.', Gender::Female, EmploymentType::FullDayTeacher, 'Guru Full Day Kelas I As-Salam', 6),
            $this->employee('22', 'MUHAMMAD SYAFIUL UMAM', 'S.Pd.', Gender::Male, EmploymentType::SubjectTeacher, 'Guru Bahasa Arab Kelas IV, V, dan VI', 2),
            $this->employee('23', 'IKA RISNAWATI', 'S.Psi.', Gender::Female, EmploymentType::Operator, 'Operator Madrasah', 0),
            $this->employee('24', 'MILATUL AZKAH', 'S.Ag.', Gender::Female, EmploymentType::Administration, 'Staf TU', 0),
        ];
    }

    private function employee(string $code, string $name, ?string $title, Gender $gender, EmploymentType $type, string $position, int $hours): array
    {
        return [
            'employee_number' => $code,
            'name' => $name,
            'back_title' => $title,
            'gender' => $gender,
            'employment_type' => $type,
            'position' => $position,
            'weekly_teaching_hours' => $hours,
        ];
    }

    private function classrooms(): array
    {
        return [
            ['code' => 'I-AR-RAHMAN', 'name' => 'I Ar-Rahman', 'level' => 1, 'teacher_code' => '2'],
            ['code' => 'I-AR-RAHIM', 'name' => 'I Ar-Rahim', 'level' => 1, 'teacher_code' => '3'],
            ['code' => 'I-AS-SALAM', 'name' => 'I As-Salam', 'level' => 1, 'teacher_code' => '4'],
            ['code' => 'II-AL-MUMIN', 'name' => "II Al-Mu'min", 'level' => 2, 'teacher_code' => '5'],
            ['code' => 'II-AL-WAHHAB', 'name' => 'II Al-Wahhab', 'level' => 2, 'teacher_code' => '6'],
            ['code' => 'III-AL-KHALIQ', 'name' => 'III Al-Khaliq', 'level' => 3, 'teacher_code' => '7'],
            ['code' => 'III-AL-LATHIF', 'name' => 'III Al-Lathif', 'level' => 3, 'teacher_code' => '8'],
            ['code' => 'IV-AL-BASITH', 'name' => 'IV Al-Basith', 'level' => 4, 'teacher_code' => '9'],
            ['code' => 'IV-AL-KARIM', 'name' => 'IV Al-Karim', 'level' => 4, 'teacher_code' => '10'],
            ['code' => 'V-AL-ALIM', 'name' => "V Al-'Alim", 'level' => 5, 'teacher_code' => '11'],
            ['code' => 'V-AL-HAKIM', 'name' => 'V Al-Hakim', 'level' => 5, 'teacher_code' => '12'],
            ['code' => 'VI-AL-MAJID', 'name' => 'VI Al-Majid', 'level' => 6, 'teacher_code' => '13'],
        ];
    }
}
