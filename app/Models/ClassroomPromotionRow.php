<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClassroomPromotionRow extends Model { protected $guarded=[]; protected function casts():array{return ['messages'=>'array'];} public function batch():BelongsTo{return $this->belongsTo(ClassroomPromotionBatch::class);} public function student():BelongsTo{return $this->belongsTo(Student::class);} public function sourceClassroom():BelongsTo{return $this->belongsTo(Classroom::class,'source_classroom_id');} public function targetClassroom():BelongsTo{return $this->belongsTo(Classroom::class,'target_classroom_id');} }
