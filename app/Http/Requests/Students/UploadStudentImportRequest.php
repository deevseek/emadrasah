<?php
declare(strict_types=1); namespace App\Http\Requests\Students;
use Illuminate\Foundation\Http\FormRequest;
class UploadStudentImportRequest extends FormRequest {public function authorize():bool{return $this->user()->can('students.import');}public function rules():array{return ['file'=>'required|file|mimes:xlsx|max:10240','duplicate_strategy'=>'required|in:skip,update'];}public function messages():array{return ['file.required'=>'File XLSX wajib dipilih.','file.mimes'=>'File harus berformat XLSX.','duplicate_strategy.required'=>'Strategi data ganda wajib dipilih.'];}}
