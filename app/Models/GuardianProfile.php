<?php
declare(strict_types=1); namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\{BelongsTo,BelongsToMany,HasMany};
class GuardianProfile extends Model { protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];} public function user():BelongsTo{return $this->belongsTo(User::class);} public function students():BelongsToMany{return $this->belongsToMany(Student::class,'student_guardians','guardian_id','student_id')->withPivot(['relationship','is_primary','can_view_academic','can_view_finance','can_request_leave']);} public function links():HasMany{return $this->hasMany(StudentGuardian::class,'guardian_id');} }
