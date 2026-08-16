<?php
declare(strict_types=1); namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StudentGuardian extends Model { protected $guarded=[]; protected function casts():array{return ['is_primary'=>'boolean','can_view_academic'=>'boolean','can_view_finance'=>'boolean','can_request_leave'=>'boolean'];} public function guardian():BelongsTo{return $this->belongsTo(GuardianProfile::class,'guardian_id');} public function student():BelongsTo{return $this->belongsTo(Student::class);} }
