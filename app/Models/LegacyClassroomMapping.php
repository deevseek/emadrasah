<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LegacyClassroomMapping extends Model { protected $guarded=[]; protected function casts():array{return ['mapped_at'=>'datetime','mapped_students_count'=>'integer'];} public function academicYear():BelongsTo{return $this->belongsTo(AcademicYear::class);} public function classroom():BelongsTo{return $this->belongsTo(Classroom::class);} public function mappedBy():BelongsTo{return $this->belongsTo(User::class,'mapped_by');} }
