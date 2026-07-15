<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentStatus; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StudentStatusHistory extends Model { protected $fillable=['student_id','previous_status','new_status','effective_date','reason','destination_school','document_number','changed_by']; protected function casts(): array { return ['previous_status'=>StudentStatus::class,'new_status'=>StudentStatus::class,'effective_date'=>'date']; } public function student(): BelongsTo { return $this->belongsTo(Student::class); } public function changedBy(): BelongsTo { return $this->belongsTo(User::class,'changed_by'); } }
