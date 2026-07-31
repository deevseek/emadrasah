<?php

declare(strict_types=1);
namespace App\Services\TeachingAssignments;
use App\Models\Classroom;use Illuminate\Support\Collection;
class WorkbookClassroomParser
{
 private const ROMAN=[1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI'];
 public function __construct(private WorkbookNameMatcher $matcher){}
 public function match(?string $label,iterable $classrooms):array
 {
  $source=$this->parseLabel($label);$candidates=Collection::make($classrooms)->filter(fn(Classroom $room)=>$room->is_active!==false);$sameGrade=$source['grade']===null?$candidates:$candidates->filter(fn(Classroom $room)=>$this->gradeNumber($room)===$source['grade']);$matches=$sameGrade->filter(function(Classroom $room)use($source,$label){$stored=$this->parseLabel($room->name);if($source['grade']!==null&&$source['name']!==''&&$this->normalizeName($stored['name'])===$this->normalizeName($source['name']))return true;$needle=$this->normalizeLabel($label);return collect([$room->code,$room->name,$room->display_name,trim(($room->gradeLevel?->name??'').' '.($room->name??'')),trim(($room->gradeLevel?->name??'').' '.$room->code)])->filter()->contains(fn($value)=>$this->normalizeLabel((string)$value)===$needle); })->values();
  return match($matches->count()){0=>['status'=>'unmatched','match'=>null,'matches'=>$matches,'parsed'=>$source,'reason'=>'Rombel belum tersedia pada tahun ajaran yang dipilih.'],1=>['status'=>'matched','match'=>$matches->first(),'matches'=>$matches,'parsed'=>$source,'reason'=>null],default=>['status'=>'selection','match'=>null,'matches'=>$matches,'parsed'=>$source,'reason'=>'Ditemukan lebih dari satu rombel pada tingkat dan nama yang sama.']};
 }
 public function parseLabel(?string $label):array
 {
  $value=trim(preg_replace('/\s+/u',' ',(string)$label));$grade=null;$name=$value;if(preg_match('/^(?:kelas\s+)?(VI|IV|V|III|II|I|[1-6])(?:\s*[-–—]\s*|\s+)(.+)$/iu',$value,$match)){$grade=$this->gradeValue($match[1]);$name=trim($match[2]);}return ['grade'=>$grade,'name'=>$name,'normalized_name'=>$this->normalizeName($name)];
 }
 public function suggestProgram(?string $label):string{$parsed=$this->parseLabel($label);return $parsed['grade']===1&&$parsed['normalized_name']===$this->normalizeName('As-Salam')?'full_day':'regular';}
 public function expand(string $text,iterable $classrooms):Collection{$classrooms=Collection::make($classrooms);if(!preg_match('/,|&|\bdan\b/iu',$text))return $this->match($text,$classrooms)['matches'];$numbers=$this->gradeNumbers($text);if(str_contains($text,',')&&$numbers!==[])return $classrooms->filter(fn(Classroom $room)=>in_array($this->gradeNumber($room),$numbers,true))->values();$parts=preg_split('/\s*(?:&|\bdan\b)\s*/iu',trim($text));return collect($parts)->flatMap(fn(string $part)=>$this->match($part,$classrooms)['matches'])->unique('id')->values();}
 public function normalize(?string $value):string{return $this->normalizeLabel($value);}
 private function normalizeName(?string $value):string{$value=str_replace(["’","‘","`","´"],"'",trim((string)$value));$value=preg_replace('/\s*[-‐‑‒–—]\s*/u','-',$value);$value=preg_replace('/\s+/u',' ',$value);return mb_strtolower(trim($value));}
 private function normalizeLabel(?string $value):string{$parsed=$this->parseLabel($value);return ($parsed['grade']??'').'|'.$parsed['normalized_name'];}
 private function gradeNumber(Classroom $room):?int{if($room->gradeLevel?->number)return (int)$room->gradeLevel->number;$parsed=$this->parseLabel($room->gradeLevel?->name??$room->name??$room->code);return $parsed['grade'];}
 private function gradeValue(string $grade):?int{$grade=mb_strtoupper($grade);if(is_numeric($grade))return (int)$grade;return array_search($grade,self::ROMAN,true)?:null;}
 private function gradeNumbers(string $text):array{preg_match_all('/\b(?:VI|IV|V|III|II|I|[1-6])\b/iu',$text,$matches);return collect($matches[0])->map(fn($grade)=>$this->gradeValue($grade))->filter()->unique()->values()->all();}
}
