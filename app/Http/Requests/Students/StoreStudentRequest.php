<?php
declare(strict_types=1); namespace App\Http\Requests\Students;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class StoreStudentRequest extends FormRequest
{
 public function authorize():bool{return $this->user()->can('students.create');}
 public function rules():array{return $this->rulesFor();}
 protected function rulesFor(?int $ignore=null):array{return ['full_name'=>'required|string|max:200','nisn'=>['nullable','required_with:photo','string','max:30','regex:/^[0-9]+$/',Rule::unique('students')->ignore($ignore)],'nik'=>['nullable','string','max:30',Rule::unique('students')->ignore($ignore)],'birth_place'=>'nullable|string|max:150','birth_date'=>'nullable|date|before_or_equal:today','classroom_label'=>'nullable|string|max:200','status'=>'nullable|in:'.implode(',',array_keys(config('students.statuses'))),'gender'=>'required|in:male,female','photo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120','address'=>'nullable|string|max:1500','phone'=>'nullable|string|max:30','special_needs'=>'nullable|string|max:200','disability'=>'nullable|string|max:200','kip_pip_number'=>['nullable','string','max:100',Rule::unique('students')->ignore($ignore)],'father_name'=>'nullable|string|max:200','mother_name'=>'nullable|string|max:200','guardian_name'=>'nullable|string|max:200'];}
 public function messages():array{return ['required'=>':attribute wajib diisi.','nisn.required_with'=>'NISN wajib diisi ketika foto siswa ditambahkan.','nisn.regex'=>'NISN hanya boleh berisi angka.','unique'=>':attribute sudah digunakan.','before_or_equal'=>'Tanggal lahir tidak boleh di masa depan.','in'=>':attribute tidak valid.','image'=>'Foto siswa harus berupa gambar.','mimes'=>'Foto siswa harus berformat JPG, JPEG, PNG, atau WEBP.','max'=>':attribute terlalu panjang.','photo.max'=>'Ukuran foto siswa maksimal 5 MB.'];}
 protected function prepareForValidation():void{$data=$this->all();foreach($data as $key=>$value)if(is_string($value)&&($value===''||in_array(mb_strtoupper(trim($value)),config('students.empty_placeholders'),true)))$data[$key]=null;$data['status']=$data['status']??'active';$this->replace($data);}
}
