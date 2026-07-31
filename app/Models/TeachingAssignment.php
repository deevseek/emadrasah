<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TeachingAssignment extends Model { protected $guarded=[]; protected function casts():array{return ['weekly_periods'=>'integer','is_primary'=>'boolean'];} public function assignmentSet():BelongsTo{return $this->belongsTo(TeachingAssignmentSet::class);} public function academicYear():BelongsTo{return $this->belongsTo(AcademicYear::class);} public function classroom():BelongsTo{return $this->belongsTo(Classroom::class);} public function subject():BelongsTo{return $this->belongsTo(Subject::class);} public function personnel():BelongsTo{return $this->belongsTo(Personnel::class);} }
