<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StudentGrade extends Model { protected $guarded=[]; protected function casts():array{return ['score'=>'decimal:2'];} public function student(){return $this->belongsTo(Student::class);} public function subject(){return $this->belongsTo(AcademicSubject::class,'academic_subject_id');} public function getPredicateAttribute():?string{if($this->score===null)return null; return self::predicate((float)$this->score);} public static function predicate(float $score):string{return $score>=90?'A':($score>=80?'B':($score>=70?'C':'D'));} }
