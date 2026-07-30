<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class GradeLevel extends Model { protected $guarded=[]; protected function casts():array{return ['number'=>'integer','sort_order'=>'integer','is_active'=>'boolean'];} public function classrooms():HasMany{return $this->hasMany(Classroom::class);} }
