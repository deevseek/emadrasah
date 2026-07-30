<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClassroomMembership extends Model { protected $guarded=[]; protected function casts():array{return ['joined_at'=>'date','left_at'=>'date'];} public function student():BelongsTo{return $this->belongsTo(Student::class);} public function classroom():BelongsTo{return $this->belongsTo(Classroom::class);} public function academicYear():BelongsTo{return $this->belongsTo(AcademicYear::class);} public function createdBy():BelongsTo{return $this->belongsTo(User::class,'created_by');} public function updatedBy():BelongsTo{return $this->belongsTo(User::class,'updated_by');} public function getStatusLabelAttribute():string{return config("classrooms.membership_statuses.{$this->status}",$this->status);} public function getIsActiveAttribute():bool{return $this->status==='active';} }
