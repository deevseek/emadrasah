<?php
declare(strict_types=1); namespace App\Http\Requests\Students;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class DownloadStudentPhotosRequest extends FormRequest { public function authorize():bool{return $this->user()->can('students.export');} public function rules():array{return ['classroom_label'=>['required','string','max:200',Rule::exists('students','classroom_label')]];} public function messages():array{return ['classroom_label.required'=>'Kelas wajib dipilih.','classroom_label.exists'=>'Kelas yang dipilih tidak ditemukan.'];} }
