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
use RuntimeException;

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
                $legacyCode = $data['legacy_code'];
                unset($data['legacy_code']);

                $employee = Employee::withTrashed()->where('employee_number', $data['employee_number'])->first()
                    ?? Employee::withTrashed()->where('employee_number', $legacyCode)->first()
                    ?? Employee::withTrashed()->get()->first(
                        fn (Employee $candidate): bool => $this->normalize($candidate->name) === $this->normalize($data['name']),
                    )
                    ?? new Employee;

                $conflict = Employee::withTrashed()->where('employee_number', $data['employee_number'])->whereKeyNot($employee->getKey())->exists();
                if ($conflict) {
                    throw new RuntimeException("NIY {$data['employee_number']} telah digunakan pegawai lain.");
                }

                $employee->fill($data + ['is_active' => true]);
                $employee->deleted_at = null;
                $employee->save();

                return [$legacyCode => $employee];
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
            $this->employee('1', '620.0720.001', 'USWATUN KHASANAH', 'S.Pd.I., M.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::Principal, 'Kepala Madrasah', 'Demak', '1993-08-26', '20367380193001', 'S2', 24, 'BRI', '001601028750535', '089681929596', 'uswahasna82@gmail.com'),
            $this->employee('2', '620.0725.040', 'ZUMALA LAILI', 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 1', 'Demak', '2003-07-09', '20367380103001', 'D4 / S1', 32, 'BRI', '787801013979533', '085740829508', 'zumalalaili14@gmail.com'),
            $this->employee('3', '620.0421.006', 'AYU SURYANINGSIH', 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 1', 'Demak', '1996-05-30', '20367380196001', 'D4 / S1', 32, 'BANK JATENG', '2031398773', '081325443526', 'ayusuryaning30@gmail.com'),
            $this->employee('4', '620.0726.047', 'FARISA AUFI SAPUTRI', 'S.Pd.', Gender::Female, EmployeeStatus::NonPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 1', 'Demak', '2001-10-01', null, 'D4 / S1', 42, 'BRI', '001601108516500', '089790996167', 'frsaufi1@gmail.com'),
            $this->employee('5', '620.0725.043', 'MAWADATUZ ZAHRO', 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 2', 'Demak', '2001-07-19', '20367380101001', 'D4 / S1', 32, 'BRI', '304301033622539', '083108672227', 'mawadatuzzahro019@gmail.com'),
            $this->employee('6', '620.0723.022', "RO'IS RO'DATUL URBAH", 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 2', 'Demak', '1997-11-14', '20367380197004', 'D4 / S1', 32, 'BRI', '001601048852537', '088233182624', 'roisurbah12r@gmail.com'),
            $this->employee('7', '620.0124.029', 'DEWI SHOFIYAH', 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 3', 'Demak', '1997-02-01', '20367380197005', 'D4 / S1', 40, 'BRI', '588201043282537', '089685666660', 'dewishopia97@gmail.com'),
            $this->employee('8', '620.0725.042', 'UMMI AL IVADAH', null, Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 3', 'Demak', '2003-05-30', '20367380103002', 'D4 / S1', 40, 'SEABANK', '901777543765', '089529622038', 'ummialvdh@gmail.com'),
            $this->employee('9', '620.0124.028', 'HAMBALI', 'S.Pd.I.', Gender::Male, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 4', 'Demak', '1983-03-29', '91000083124692', 'D4 / S1', 40, 'BRI', '001601097768507', '087709692980', 'chanbalijina@gmail.com', 'Guru Pertama, Penata Muda / IIIa', 'Sertifikasi Impassing', 'Guru Kelas'),
            $this->employee('10', '620.0726.051', 'NUR LAILA AZIZAH', 'S.Pd.', Gender::Female, EmployeeStatus::NonPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 4', 'Demak', '2004-09-27', null, 'D4 / S1', 40, 'SEABANK', '901933805499', '0878282394697', 'zizilailaazi@gmail.com'),
            $this->employee('11', '620.0725.041', 'DYAH AYU FEBRIANI', 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 5', 'Demak', '2001-02-11', '20367380101002', 'D4 / S1', 40, 'BRI', '588601042928500', '088227620818', 'dyahayufebriani11@gmail.com'),
            $this->employee('12', '620.0321.005', 'SITI MUNIROH', 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 5', 'Demak', '1998-03-13', '20367380198001', 'D4 / S1', 40, 'MANDIRI', '1350021641665', '085712538476', 'sitimuniroh0105@gmail.com'),
            $this->employee('13', '620.0724.037', "LAILY RIZQI 'AMALIAH", 'S.Pd.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::ClassTeacher, 'Guru Kelas 6', 'Demak', '2001-04-15', '20319712101001', 'D4 / S1', 40, 'MANDIRI', '1350021386535', '088806033362', 'laily.rizqi21@gmail.com'),
            $this->employee('14', '620.0824.038', 'NUSROH MUFIDAH', 'AH.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::BtaqTeacher, 'Guru BTAQ', 'Demak', '1997-03-07', null, 'SMA/Sederajat', 12, 'BRI', '588601081035534', '087854166820', 'mufidah070397@gmail.com'),
            $this->employee('15', '620.0726.049', 'NUR ROHMAH', 'S.Ag., M.Pd.', Gender::Female, EmployeeStatus::NonPermanentTeacher, EmploymentType::BtaqTeacher, 'Guru BTAQ', 'Demak', '1999-06-05', null, 'S2', 12, 'BRI', '228301003279503', '08995427977', 'nrohmah865@gmail.com'),
            $this->employee('16', '620.0523.019', 'IDA SALMA', 'AH.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::BtaqTeacher, 'Guru BTAQ', 'Jepara', '2000-02-10', null, 'SMA/Sederajat', 12, 'BRI', '588901029775536', '085800369099', 'idasalma9@gmail.com'),
            $this->employee('17', '620.0726.050', 'WINDY LESTARI', 'AH.', Gender::Female, EmployeeStatus::NonPermanentTeacher, EmploymentType::BtaqTeacher, 'Guru BTAQ', 'Demak', '2003-02-03', null, 'SMA/Sederajat', 12, 'SEABANK', '901833038154', '085602734571', 'windilestari7790@gmail.com'),
            $this->employee('18', '620.0725.045', 'KHOIRIYAH', 'AH.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::BtaqTeacher, 'Guru BTAQ', 'Demak', '1998-04-15', null, 'SMA/Sederajat', 12, 'BRI', '001601039173530', '085191224326', 'khoirilaili15@gmail.com'),
            $this->employee('19', '620.0323.017', 'NUR AINI MUFAROKAH', 'AH.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::BtaqTeacher, 'Guru BTAQ', 'Demak', '1992-02-02', null, 'SMA/Sederajat', 12, 'BCA', '5515020915', '081225725821', 'nurainimufarokah@gmail.com'),
            $this->employee('20', '620.0124.030', 'ALFA INAYATIS SANIYAH', 'AH.', Gender::Female, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::BtaqTeacher, 'Guru BTAQ', 'Demak', '1999-07-25', null, 'SMA/Sederajat', 12, 'SEABANK', '901848564232', '085727114966', 'wildaninda18@gmail.com'),
            $this->employee('21', '620.0726.048', 'QONITA NASYIATUL WAHDAH', 'S.Ag.', Gender::Female, EmployeeStatus::NonPermanentTeacher, EmploymentType::SubjectTeacher, 'Guru Mapel PAI', 'Demak', '2000-09-07', null, 'D4 / S1', 40, 'BRI', '787801010405535', '087862789599', 'nasyiatulqonita@gmail.com'),
            $this->employee('22', '620.0923.027', 'MUHAMMAD SYAFIUL UMAM', 'S.Pd.', Gender::Male, EmployeeStatus::FoundationPermanentTeacher, EmploymentType::SubjectTeacher, 'Guru Mapel Bahasa Arab', 'Jombang', '1994-03-22', '20503692194001', 'D4 / S1', 7, 'BRI', '001601041534536', '085648212224', 'muhammadsyafiulumam2203@gmail.com'),
            $this->employee('23', '620.0724.032', 'IKA RISNAWATI', 'S.Psi.', Gender::Female, EmployeeStatus::FoundationPermanentEmployee, EmploymentType::Administration, 'Tata Usaha (TU)', 'Jepara', '1995-01-09', '20367380195002', 'D4 / S1', null, 'MANDIRI', '1840001075777', '082237065549', 'ikarisna240@gmail.com'),
            $this->employee('24', '620.0726.052', 'MILATUL AZKAH', 'S.Ag.', Gender::Female, EmployeeStatus::NonPermanentEmployee, EmploymentType::Administration, 'Tata Usaha (TU)', 'Demak', '2001-12-14', null, 'D4 / S1', null, 'BNI', '204466806', '0895336789786', 'milatzka@gmail.com'),
            $this->employee('25', '620.0722.046', 'SURYADI', null, Gender::Male, EmployeeStatus::FoundationPermanentEmployee, EmploymentType::EducationStaff, 'Petugas Kebersihan', 'Demak', '1964-11-15', null, 'SMP/Sederajat', null, null, null, null, null),
        ];
    }

    private function employee(
        string $legacyCode, string $employeeNumber, string $name, ?string $title, Gender $gender,
        EmployeeStatus $status, EmploymentType $type, string $position, string $birthPlace,
        string $birthDate, ?string $pegId, string $education, ?int $hours, ?string $bank,
        ?string $account, ?string $phone, ?string $email, ?string $rankGrade = null,
        string $certification = 'Non Sertifikasi', ?string $certificationSubject = null,
    ): array {
        return [
            'legacy_code' => $legacyCode,
            'employee_number' => $employeeNumber,
            'name' => $name,
            'back_title' => $title,
            'gender' => $gender,
            'birth_place' => $birthPlace,
            'birth_date' => $birthDate,
            'employment_type' => $type,
            'employee_status' => $status,
            'position' => $position,
            'rank_grade' => $rankGrade,
            'peg_id' => $pegId,
            'certification_status' => $certification,
            'certification_subject' => $certificationSubject,
            'weekly_teaching_hours' => $hours,
            'bank_name' => $bank,
            'bank_account_number' => $account,
            'phone' => $phone,
            'whatsapp' => $phone,
            'email' => $email,
            'last_education' => $education,
        ];
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', str($value)->lower()->ascii()->toString()) ?? '';
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
