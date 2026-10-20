<?php
declare(strict_types=1);
namespace App\Services\Academic;

use App\Models\{Classroom, User};
use App\Services\Personnel\ResolvePersonnelAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
class AcademicAccessService {
 public function __construct(private ResolvePersonnelAccount $personnelAccounts) {}
 public function classrooms(User $user):Builder { $query=Classroom::query()->where('is_active',true)->with(['gradeLevel','academicYear']); if($user->hasRole('guru')){$personnel=$this->personnelAccounts->handle($user); if(!$personnel)return $query->whereRaw('1=0'); $query->where('homeroom_personnel_id',$personnel->id);} return $query; }
 public function ensureClassroom(User $user,int $id):Classroom { $room=$this->classrooms($user)->find($id); if(!$room)throw ValidationException::withMessages(['classroom_id'=>$user->hasRole('guru')&&!$user->personnel?'Akun Anda belum terhubung dengan Data Personalia. Hubungi Operator Madrasah.':'Anda tidak berwenang mengelola rombel tersebut.']); return $room; }

 public function teachingJournalClassrooms(User $user, int $academicYearId, int $semesterId, ?int $academicSubjectId = null): Builder
 {
  $query = Classroom::query()
   ->where('is_active', true)
   ->where('academic_year_id', $academicYearId)
   ->with(['gradeLevel', 'academicYear']);

  if (! $user->hasRole('guru')) return $query;

  $personnel = $this->personnelAccounts->handle($user);
  if (! $personnel) return $query->whereRaw('1=0');

  return $query->where(function (Builder $rooms) use ($personnel, $academicYearId, $semesterId, $academicSubjectId): void {
   $rooms->where('homeroom_personnel_id', $personnel->id)
    ->orWhereExists(function ($schedules) use ($personnel, $academicYearId, $semesterId, $academicSubjectId): void {
     $schedules->selectRaw('1')
      ->from('lesson_schedules')
      ->whereColumn('lesson_schedules.classroom_id', 'classrooms.id')
      ->where('lesson_schedules.personnel_id', $personnel->id)
      ->where('lesson_schedules.academic_year_id', $academicYearId)
      ->where('lesson_schedules.semester_id', $semesterId)
      ->when($academicSubjectId, fn ($query) => $query->where('lesson_schedules.academic_subject_id', $academicSubjectId))
      ->where('lesson_schedules.active', true);
    });
  });
 }

 public function ensureTeachingJournalClassroom(User $user, int $id, int $academicYearId, int $semesterId, int $academicSubjectId): Classroom
 {
  $room = $this->teachingJournalClassrooms($user, $academicYearId, $semesterId, $academicSubjectId)->find($id);
  if (! $room) {
   throw ValidationException::withMessages([
    'classroom_id' => 'Rombel tidak tersedia dalam jadwal mengajar Anda pada semester yang dipilih.',
   ]);
  }

  return $room;
 }
}
