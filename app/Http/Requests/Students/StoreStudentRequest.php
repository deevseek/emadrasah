<?php
declare(strict_types=1); namespace App\Http\Requests\Students;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class StoreStudentRequest extends FormRequest
{
 public function authorize():bool{return $this->user()->can('students.create');}
 public function rules():array{return $this->rulesFor();}
 protected function rulesFor(?int $ignore=null):array{return ['full_name'=>'required|string|max:200','nisn'=>['nullable','string','max:30',Rule::unique('students')->ignore($ignore)],'nik'=>['nullable','string','max:30',Rule::unique('students')->ignore($ignore)],'birth_place'=>'nullable|string|max:150','birth_date'=>'nullable|date|before_or_equal:today','classroom_label'=>'nullable|string|max:200','status'=>'nullable|in:'.implode(',',array_keys(config('students.statuses'))),'gender'=>'required|in:male,female','address'=>'nullable|string|max:1500','phone'=>'nullable|string|max:30','special_needs'=>'nullable|string|max:200','disability'=>'nullable|string|max:200','kip_pip_number'=>['nullable','string','max:100',Rule::unique('students')->ignore($ignore)],'father_name'=>'nullable|string|max:200','mother_name'=>'nullable|string|max:200','guardian_name'=>'nullable|string|max:200'];}
 public function messages():array{return ['required'=>':attribute wajib diisi.','unique'=>':attribute sudah digunakan.','before_or_equal'=>'Tanggal lahir tidak boleh di masa depan.','in'=>':attribute tidak valid.','max'=>':attribute terlalu panjang.'];}
 protected function prepareForValidation():void{$data=$this->all();foreach($data as $key=>$value)if(is_string($value)&&($value===''||in_array(mb_strtoupper(trim($value)),config('students.empty_placeholders'),true)))$data[$key]=null;$data['status']=$data['status']??'active';$this->replace($data);}
}
