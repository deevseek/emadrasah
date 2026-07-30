<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class ClassroomPromotionBatch extends Model { protected $guarded=[]; protected function casts():array{return ['summary'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];} public function sourceAcademicYear():BelongsTo{return $this->belongsTo(AcademicYear::class,'source_academic_year_id');} public function targetAcademicYear():BelongsTo{return $this->belongsTo(AcademicYear::class,'target_academic_year_id');} public function rows():HasMany{return $this->hasMany(ClassroomPromotionRow::class,'batch_id');} }
