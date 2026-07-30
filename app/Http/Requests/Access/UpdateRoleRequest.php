<?php
namespace App\Http\Requests\Access;
use Illuminate\Foundation\Http\FormRequest;
class UpdateRoleRequest extends FormRequest { public function authorize():bool{return $this->user()?->can('roles.update')===true;} public function rules():array{return ['display_name'=>['required','string','max:100'],'description'=>['nullable','string','max:500'],'permissions'=>['array'],'permissions.*'=>['string','exists:permissions,name']];} }
