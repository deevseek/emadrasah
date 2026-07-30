<?php
declare(strict_types=1); namespace App\Http\Requests\Students;
use Illuminate\Foundation\Http\FormRequest;
class ConfirmStudentImportRequest extends FormRequest {public function authorize():bool{return $this->user()->can('students.import');}public function rules():array{return ['batch_id'=>'required|integer|exists:student_import_batches,id'];}}
