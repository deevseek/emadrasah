<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AcademicSubject extends Model { protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean','sort_order'=>'integer'];} public function teachingJournals(){return $this->hasMany(TeachingJournal::class);} }
