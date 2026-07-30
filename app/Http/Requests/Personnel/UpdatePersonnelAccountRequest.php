<?php
declare(strict_types=1); namespace App\Http\Requests\Personnel;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class UpdatePersonnelAccountRequest extends FormRequest {public function authorize():bool{return $this->user()->can('personnel.manage-account');}public function rules():array{return ['user_id'=>['required','integer',Rule::exists('users','id'),Rule::unique('personnel','user_id')->ignore($this->route('personnel')->id)]];} public function messages():array{return ['user_id.unique'=>'Akun sudah terhubung dengan personalia lain.'];}}
