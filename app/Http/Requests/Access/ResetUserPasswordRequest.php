<?php
namespace App\Http\Requests\Access;
use Illuminate\Foundation\Http\FormRequest;
class ResetUserPasswordRequest extends FormRequest { public function authorize(): bool{return $this->user()?->can('users.reset-password')===true;} public function rules():array{return ['password'=>['required','string','min:8','confirmed']];} public function messages():array{return ['password.min'=>'Password minimal terdiri dari 8 karakter.','password.confirmed'=>'Konfirmasi password tidak sesuai.'];} }
