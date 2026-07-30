<?php
declare(strict_types=1); namespace App\Http\Requests\Personnel;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class StorePersonnelRequest extends FormRequest
{
 public function authorize():bool{return $this->user()->can('personnel.create');}
 public function rules():array{return $this->rulesFor();}
 protected function rulesFor(?int $ignore=null):array{return ['full_name'=>'required|string|max:200','gender'=>'required|in:male,female','birth_place'=>'nullable|string|max:150','birth_date'=>'nullable|date|before_or_equal:today','employment_status'=>'required|string|max:50','foundation_employee_number'=>['nullable','string','max:50',Rule::unique('personnel')->ignore($ignore)],'nip'=>['nullable','string','max:50',Rule::unique('personnel')->ignore($ignore)],'rank_grade'=>'nullable|string|max:200','external_employee_id'=>['nullable','string','max:100',Rule::unique('personnel')->ignore($ignore)],'last_education'=>'nullable|string|max:100','position'=>'required|string|max:150','certification_status'=>'nullable|string|max:100','certification_subject'=>'nullable|string|max:150','weekly_teaching_hours'=>'nullable|integer|between:0,100','bank_name'=>'nullable|string|max:100','bank_account_number'=>'nullable|string|max:100','phone'=>'nullable|string|max:30','email'=>['nullable','email','max:200',Rule::unique('personnel')->ignore($ignore)],'is_active'=>'required|boolean','user_id'=>['nullable','integer',Rule::exists('users','id'),Rule::unique('personnel')->ignore($ignore)]];}
 public function messages():array{return ['required'=>':attribute wajib diisi.','unique'=>':attribute sudah digunakan.','email'=>'Email harus berupa alamat email yang valid.','before_or_equal'=>'Tanggal lahir tidak boleh di masa depan.','between'=>'Jumlah JPL harus antara 0 sampai 100.'];}
 protected function prepareForValidation():void{$this->merge(['email'=>$this->email?strtolower(trim($this->email)):null,'is_active'=>$this->boolean('is_active')]);}
}
