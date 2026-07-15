<?php

declare(strict_types=1);
namespace App\Services\StudentAffairs;
use App\Models\Student; use App\Services\ActivityLogger; use Illuminate\Validation\ValidationException; use Illuminate\Support\Facades\DB;
class GuardianAssignmentService { public function __construct(private ActivityLogger $logger) {} public function attach(Student $student, array $data): void { DB::transaction(function() use($student,$data){ if($student->guardians()->whereKey($data['guardian_id'])->exists()) throw ValidationException::withMessages(['guardian_id'=>'Wali sudah terhubung dengan siswa ini.']); if(!empty($data['is_primary'])) $student->guardians()->wherePivot('is_primary', true)->update(['guardian_student.is_primary' => false]); if(!empty($data['is_emergency_contact'])) $student->guardians()->wherePivot('is_emergency_contact', true)->update(['guardian_student.is_emergency_contact' => false]); $student->guardians()->attach($data['guardian_id'], collect($data)->except('guardian_id')->all()); $this->logger->log('student.guardian.attached',$student,[], $data,'Wali siswa ditautkan.'); }); } }
