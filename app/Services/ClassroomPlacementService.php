<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\HomeroomAssignment;
use App\Models\PromotionBatch;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassroomPlacementService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function assignHomeroom(Classroom $classroom, Employee $employee, array $data): HomeroomAssignment
    {
        return DB::transaction(function () use ($classroom, $employee, $data): HomeroomAssignment {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            if (! $employee->is_active) throw ValidationException::withMessages(['employee_id' => 'Wali kelas harus pegawai aktif.']);
            if (! in_array($employee->employment_type, [EmploymentType::ClassTeacher, EmploymentType::Principal], true)) throw ValidationException::withMessages(['employee_id' => 'Wali kelas harus Guru Kelas atau Kepala Madrasah.']);
            $active = HomeroomAssignment::query()->where('classroom_id', $classroom->id)->where('is_active', true)->lockForUpdate()->first();
            if ($active && empty($data['reason'])) throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi saat mengganti wali kelas.']);
            $duplicate = HomeroomAssignment::query()->where('employee_id', $employee->id)->where('academic_year_id', $classroom->academic_year_id)->where('is_active', true)->where('classroom_id', '!=', $classroom->id)->lockForUpdate()->exists();
            if ($duplicate) throw ValidationException::withMessages(['employee_id' => 'Guru sudah menjadi wali kelas pada tahun ajaran yang sama.']);
            if ($active) $active->update(['is_active' => false, 'ended_at' => $data['started_at'] ?? now()->toDateString()]);
            $assignment = HomeroomAssignment::create(['classroom_id'=>$classroom->id,'academic_year_id'=>$classroom->academic_year_id,'employee_id'=>$employee->id,'started_at'=>$data['started_at'] ?? now()->toDateString(),'reason'=>$data['reason'] ?? null,'notes'=>$data['notes'] ?? null,'assigned_by'=>auth()->id(),'is_active'=>true]);
            $classroom->update(['homeroom_teacher_id' => $employee->id]);
            $this->logger->log('homeroom.assigned', $assignment, $active?->getAttributes() ?? [], $assignment->getAttributes(), 'Wali kelas ditetapkan.');
            return $assignment;
        });
    }

    public function releaseHomeroom(Classroom $classroom, array $data): void
    {
        DB::transaction(function () use ($classroom, $data): void {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            $active = HomeroomAssignment::query()->where('classroom_id', $classroom->id)->where('is_active', true)->lockForUpdate()->firstOrFail();
            $old = $active->getAttributes();
            $active->update(['is_active'=>false,'ended_at'=>$data['ended_at'] ?? now()->toDateString(),'reason'=>$data['reason'] ?? null,'notes'=>$data['notes'] ?? null]);
            $classroom->update(['homeroom_teacher_id' => null]);
            $this->logger->log('homeroom.released', $active, $old, $active->getAttributes(), 'Wali kelas dilepas.');
        });
    }

    public function place(Classroom $classroom, array $studentIds, array $data, bool $override = false): int
    {
        return DB::transaction(function () use ($classroom, $studentIds, $data, $override): int {
            $ids = array_values(array_unique(array_map('intval', $studentIds)));
            if (count($ids) !== count($studentIds)) throw ValidationException::withMessages(['student_ids' => 'Pilihan siswa tidak boleh duplikat.']);
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            $this->assertCapacity($classroom, count($ids), $override, $data['override_reason'] ?? null);
            $students = Student::query()->whereKey($ids)->lockForUpdate()->get();
            if ($students->count() !== count($ids)) throw ValidationException::withMessages(['student_ids' => 'Data siswa tidak valid.']);
            foreach ($students as $student) {
                $this->assertStudentEligible($student);
                $this->assertNoActiveEnrollment($student->id, $classroom->academic_year_id);
                $enrollment = StudentEnrollment::create(['student_id'=>$student->id,'academic_year_id'=>$classroom->academic_year_id,'classroom_id'=>$classroom->id,'enrolled_at'=>$data['enrolled_at'] ?? now()->toDateString(),'enrollment_status'=>EnrollmentStatus::Active,'source'=>$data['source'] ?? 'manual','notes'=>$data['notes'] ?? null,'processed_by'=>auth()->id()]);
                $this->logger->log('student.enrolled', $enrollment, [], $enrollment->getAttributes(), 'Siswa ditempatkan ke kelas.');
            }
            return count($ids);
        });
    }

    public function transfer(StudentEnrollment $current, Classroom $target, array $data): StudentEnrollment
    {
        return DB::transaction(function () use ($current, $target, $data): StudentEnrollment {
            $current = StudentEnrollment::query()->whereKey($current->id)->lockForUpdate()->firstOrFail();
            $target = Classroom::query()->whereKey($target->id)->lockForUpdate()->firstOrFail();
            if ($current->classroom_id === $target->id) throw ValidationException::withMessages(['target_classroom_id' => 'Kelas tujuan harus berbeda.']);
            if ((int) $current->academic_year_id !== (int) $target->academic_year_id) throw ValidationException::withMessages(['target_classroom_id' => 'Kelas tujuan harus pada tahun ajaran yang sama.']);
            $this->assertCapacity($target, 1, false);
            $old = $current->getAttributes();
            $current->update(['enrollment_status'=>EnrollmentStatus::Transferred,'completed_at'=>$data['effective_date'],'notes'=>$data['reason']]);
            $new = StudentEnrollment::create(['student_id'=>$current->student_id,'academic_year_id'=>$current->academic_year_id,'classroom_id'=>$target->id,'enrolled_at'=>$data['effective_date'],'enrollment_status'=>EnrollmentStatus::Active,'source'=>'transfer','notes'=>$data['notes'] ?? $data['reason'],'processed_by'=>auth()->id()]);
            $this->logger->log('student.transferred', $new, $old, $new->getAttributes(), 'Siswa dipindahkan antar kelas.');
            return $new;
        });
    }

    public function promote(Classroom $source, Classroom $target, array $decisions, array $data): PromotionBatch
    {
        return DB::transaction(function () use ($source, $target, $decisions, $data): PromotionBatch {
            $source = Classroom::query()->lockForUpdate()->findOrFail($source->id); $target = Classroom::query()->lockForUpdate()->findOrFail($target->id);
            if ($source->academic_year_id === $target->academic_year_id) throw ValidationException::withMessages(['target_classroom_id'=>'Tahun ajaran tujuan harus berbeda.']);
            $processable = collect($decisions)->filter(fn ($d) => in_array($d['decision'] ?? null, ['promoted','retained'], true));
            $this->assertCapacity($target, $processable->count(), false);
            $batch = PromotionBatch::create(['source_academic_year_id'=>$source->academic_year_id,'target_academic_year_id'=>$target->academic_year_id,'source_classroom_id'=>$source->id,'target_classroom_id'=>$target->id,'processed_by'=>auth()->id(),'processed_at'=>now(),'notes'=>$data['notes'] ?? null]);
            foreach ($processable as $studentId => $row) {
                if ($source->gradeLevel->level >= 6 && $row['decision'] === 'promoted') throw ValidationException::withMessages(['decisions'=>'Kelas 6 tidak diproses naik ke kelas 7 pada Modul 4.']);
                $expected = $row['decision'] === 'promoted' ? $source->gradeLevel->level + 1 : $source->gradeLevel->level;
                if ($target->gradeLevel->level !== $expected) throw ValidationException::withMessages(['target_classroom_id'=>'Tingkat kelas tujuan tidak sesuai keputusan.']);
                if ($row['decision'] === 'retained' && empty($row['reason'])) throw ValidationException::withMessages(['decisions'=>'Alasan tinggal kelas wajib diisi.']);
                $current = StudentEnrollment::query()->where('student_id', $studentId)->where('classroom_id', $source->id)->where('enrollment_status', EnrollmentStatus::Active)->lockForUpdate()->firstOrFail();
                $current->update(['enrollment_status' => $row['decision'] === 'promoted' ? EnrollmentStatus::Promoted : EnrollmentStatus::Retained, 'completed_at' => $data['effective_date'], 'notes' => $row['reason'] ?? null]);
                StudentEnrollment::create(['student_id'=>$studentId,'academic_year_id'=>$target->academic_year_id,'classroom_id'=>$target->id,'enrolled_at'=>$data['effective_date'],'enrollment_status'=>EnrollmentStatus::Active,'source'=>$row['decision'],'notes'=>$row['reason'] ?? null,'processed_by'=>auth()->id()]);
            }
            $this->logger->log('student.promotion.batch', $batch, [], $batch->getAttributes(), 'Batch kenaikan kelas diproses.');
            return $batch;
        });
    }

    private function assertStudentEligible(Student $student): void { if (! $student->is_active || $student->student_status !== StudentStatus::Active) throw ValidationException::withMessages(['student_ids'=>'Hanya siswa aktif yang dapat ditempatkan.']); }
    private function assertNoActiveEnrollment(int $studentId, int $yearId): void { if (StudentEnrollment::query()->where('student_id',$studentId)->where('academic_year_id',$yearId)->where('enrollment_status',EnrollmentStatus::Active)->lockForUpdate()->exists()) throw ValidationException::withMessages(['student_ids'=>'Siswa sudah memiliki penempatan aktif pada tahun ajaran ini.']); }
    private function assertCapacity(Classroom $classroom, int $additional, bool $override, ?string $reason = null): void { $count = StudentEnrollment::query()->where('classroom_id',$classroom->id)->where('enrollment_status',EnrollmentStatus::Active)->lockForUpdate()->count(); if ($classroom->capacity !== null && $count + $additional > $classroom->capacity && ! $override) throw ValidationException::withMessages(['student_ids'=>'Kapasitas kelas tidak mencukupi.']); if ($classroom->capacity !== null && $count + $additional > $classroom->capacity && $override && blank($reason)) throw ValidationException::withMessages(['override_reason'=>'Alasan override kapasitas wajib diisi.']); }
}
