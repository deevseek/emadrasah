<?php
declare(strict_types=1);
namespace App\Services\Academic;
use App\Models\{Classroom,User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
class AcademicAccessService {
 public function classrooms(User $user):Builder { $query=Classroom::query()->where('is_active',true)->with(['gradeLevel','academicYear']); if($user->hasRole('guru')){$personnel=$user->personnel; if(!$personnel)return $query->whereRaw('1=0'); $query->where('homeroom_personnel_id',$personnel->id);} return $query; }
 public function ensureClassroom(User $user,int $id):Classroom { $room=$this->classrooms($user)->find($id); if(!$room)throw ValidationException::withMessages(['classroom_id'=>$user->hasRole('guru')&&!$user->personnel?'Akun Anda belum terhubung dengan Data Personalia. Hubungi Operator Madrasah.':'Anda tidak berwenang mengelola rombel tersebut.']); return $room; }
}
