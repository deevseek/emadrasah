<?php

declare(strict_types=1);

namespace App\Services\Academic\Imports;

use App\Models\{Classroom,Employee,Subject};
use Illuminate\Database\Eloquent\Collection;

final class ImportMatcher
{
    public const OFFICIAL_CLASSROOMS = ["I As-Salam (Fullday)",'I Ar-Rahman','I Ar-Rahim',"II Al-Mu'min",'II Al-Wahhab','III Al-Khaliq','III Al-Lathif','IV Al-Basith','IV Al-Karim',"V Al-'Alim",'V Al-Hakim','VI Al-Majid'];
    private const SUBJECT_ALIASES = ['al quran hadis'=>'al quran hadits','aqidah akhlaq'=>'akidah akhlak','fiqih'=>'fikih','ke nu an'=>'kenuan','b indonesia'=>'bahasa indonesia','b arab'=>'bahasa arab','b inggris'=>'bahasa inggris','b jawa'=>'bahasa jawa','tik'=>'literasi digital'];
    public function normalize(?string $value): string
    {
        $v=mb_strtolower(trim((string)$value));
        $v=str_replace(['’','‘',"'",'-','.', '(',')'],['','','',' ',' ',' ',' '],$v);
        $v=trim(preg_replace('/\s+/u',' ',$v)??'');
        return str_replace(['full day','al quran hadits'],['fullday','al quran hadis'],$v);
    }
    public function employee(?string $number, ?string $name): array
    { $q=Employee::where('is_active',true);if(filled($number)){$found=$q->where('employee_number',trim((string)$number))->get();}else{$target=$this->normalize($name);$found=$q->get()->filter(fn($e)=>$this->normalize($e->name)===$target);}return $this->result($found,'employee'); }
    public function classroom(int $year, ?string $code, ?string $name): array
    { $q=Classroom::where('academic_year_id',$year)->where('is_active',true);$found=filled($code)?$q->where('code',trim((string)$code))->get():$q->get()->filter(fn($c)=>$this->normalize($c->name)===$this->normalize($name));return $this->result($found,'classroom'); }
    public function subject(?string $code, ?string $name, ?int $gradeLevelId=null): array
    { $q=Subject::with('gradeLevels')->where('is_active',true);if(filled($code))$found=$q->where('code',trim((string)$code))->get();else{$target=$this->normalize($name);$target=self::SUBJECT_ALIASES[$target]??$target;$found=$q->get()->filter(function($s)use($target){foreach([$s->name,$s->short_name]as$n){$x=$this->normalize($n);if((self::SUBJECT_ALIASES[$x]??$x)===$target)return true;}return false;});}if($gradeLevelId)$found=$found->filter(fn($s)=>$s->gradeLevels->isEmpty()||$s->gradeLevels->contains($gradeLevelId));return $this->result($found,'subject'); }
    private function result(Collection $items,string $type): array { return $items->count()===1?['model'=>$items->first(),'status'=>null]:['model'=>null,'status'=>$items->isEmpty()?"{$type}_not_found":($type==='employee'?'employee_ambiguous':"{$type}_not_found")]; }
}
