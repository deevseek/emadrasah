<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\AdmissionType;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Enums\SubjectCategory;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\PredicateRange;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;

trait CreatesAcademicTestData
{
    protected function uniqueSuffix(string $prefix = 'T'): string
    {
        return $prefix.'-'.str_replace('.', '', uniqid('', true));
    }

    protected function createActiveAcademicPeriod(?string $suffix = null): array
    {
        $suffix ??= $this->uniqueSuffix('PER');

        $academicYear = AcademicYear::query()->create([
            'name' => '2026/2027-'.$suffix,
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);

        $semester = Semester::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'term' => 1,
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
            'is_active' => true,
        ]);

        return [$academicYear, $semester];
    }

    protected function createGradeLevel(?string $suffix = null): GradeLevel
    {
        $suffix ??= $this->uniqueSuffix('GL');

        return GradeLevel::query()->create([
            'name' => 'Tingkat '.$suffix,
            'code' => 'GL-'.$suffix,
            'level' => random_int(1000, 9999),
            'is_active' => true,
        ]);
    }

    protected function createTeacher(?User $user = null, EmploymentType $type = EmploymentType::SubjectTeacher, ?string $suffix = null): Employee
    {
        $suffix ??= $this->uniqueSuffix('EMP');

        return Employee::query()->create([
            'user_id' => $user?->id,
            'employee_number' => 'EMP-'.$suffix,
            'name' => 'Guru Pengujian',
            'gender' => Gender::Male,
            'employment_type' => $type,
            'employee_status' => EmployeeStatus::Permanent,
            'is_active' => true,
        ]);
    }

    protected function createClassroom(AcademicYear $academicYear, GradeLevel $gradeLevel, ?Employee $homeroomTeacher = null, ?string $suffix = null): Classroom
    {
        $suffix ??= $this->uniqueSuffix('CLS');

        return Classroom::query()->create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas '.$suffix,
            'code' => 'KLS-'.$suffix,
            'homeroom_teacher_id' => $homeroomTeacher?->id,
            'is_active' => true,
        ]);
    }

    protected function createSubject(?string $suffix = null): Subject
    {
        $suffix ??= $this->uniqueSuffix('SUB');

        return Subject::query()->create([
            'code' => 'MAPEL-'.$suffix,
            'name' => 'Mata Pelajaran Pengujian',
            'category' => SubjectCategory::General,
            'minimum_passing_grade' => 75,
            'is_active' => true,
        ]);
    }

    protected function createTeachingAssignment(AcademicYear $year, Semester $semester, Employee $teacher, Classroom $classroom, Subject $subject): TeachingAssignment
    {
        return TeachingAssignment::query()->create([
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'employee_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);
    }

    protected function createActiveStudent(?string $suffix = null): Student
    {
        $suffix ??= $this->uniqueSuffix('STU');

        return Student::query()->create([
            'student_number' => 'NIS-'.$suffix,
            'name' => 'Siswa Pengujian',
            'gender' => Gender::Male,
            'admission_type' => AdmissionType::NewStudent,
            'student_status' => StudentStatus::Active,
            'is_active' => true,
        ]);
    }

    protected function createEnrollment(Student $student, AcademicYear $year, Classroom $classroom): StudentEnrollment
    {
        return StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'enrollment_status' => EnrollmentStatus::Active,
        ]);
    }

    protected function createPredicateRanges(): void
    {
        PredicateRange::query()->create(['code' => 'A', 'label' => 'A', 'minimum_score' => 90, 'maximum_score' => 100, 'sequence' => 1, 'is_active' => true]);
        PredicateRange::query()->create(['code' => 'B', 'label' => 'B', 'minimum_score' => 80, 'maximum_score' => 89.99, 'sequence' => 2, 'is_active' => true]);
        PredicateRange::query()->create(['code' => 'C', 'label' => 'C', 'minimum_score' => 0, 'maximum_score' => 79.99, 'sequence' => 3, 'is_active' => true]);
    }
}
