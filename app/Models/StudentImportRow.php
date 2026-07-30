<?php
declare(strict_types=1); namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StudentImportRow extends Model { protected $guarded=[]; protected function casts():array{return ['raw_data'=>'array','normalized_data'=>'array','messages'=>'array'];} public function batch():BelongsTo{return $this->belongsTo(StudentImportBatch::class,'batch_id');} public function matchedStudent():BelongsTo{return $this->belongsTo(Student::class,'matched_student_id');} }
