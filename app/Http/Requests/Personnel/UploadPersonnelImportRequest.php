<?php
declare(strict_types=1); namespace App\Http\Requests\Personnel;
use Illuminate\Foundation\Http\FormRequest;
class UploadPersonnelImportRequest extends FormRequest {public function authorize():bool{return $this->user()->can('personnel.import');}public function rules():array{return ['file'=>'required|file|mimes:xlsx|max:10240','duplicate_strategy'=>'required|in:skip,update'];}public function messages():array{return ['file.mimes'=>'File harus berformat XLSX.','file.max'=>'Ukuran file maksimal 10 MB.'];}}
