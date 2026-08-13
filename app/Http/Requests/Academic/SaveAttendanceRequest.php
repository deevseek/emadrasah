<?php
declare(strict_types=1);
namespace App\Http\Requests\Academic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SaveAttendanceRequest extends FormRequest { public function authorize():bool{return $this->user()->can('academic-attendance.manage');} public function rules():array{return ['academic_year_id'=>['required','exists:academic_years,id'],'semester_id'=>['required',Rule::exists('semesters','id')->where('academic_year_id',$this->integer('academic_year_id'))],'classroom_id'=>['required','exists:classrooms,id'],'attendance_date'=>['required','date'],'attendances'=>['required','array','min:1'],'attendances.*.student_id'=>['required','distinct','exists:students,id'],'attendances.*.status'=>['required',Rule::in(['present','sick','permitted','absent'])],'attendances.*.notes'=>['nullable','string','max:1000']];} }
