<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdditionalDuty extends Model { protected $guarded=[]; protected function casts():array{return ['equivalent_periods'=>'integer'];} public function assignmentSet():BelongsTo{return $this->belongsTo(TeachingAssignmentSet::class);} public function academicYear():BelongsTo{return $this->belongsTo(AcademicYear::class);} public function personnel():BelongsTo{return $this->belongsTo(Personnel::class);} public function classroom():BelongsTo{return $this->belongsTo(Classroom::class);} }
