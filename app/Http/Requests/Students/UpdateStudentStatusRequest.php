<?php
declare(strict_types=1); namespace App\Http\Requests\Students;
use Illuminate\Foundation\Http\FormRequest;
class UpdateStudentStatusRequest extends FormRequest {public function authorize():bool{return $this->user()->can('students.change-status');}public function rules():array{return ['status'=>'required|in:'.implode(',',array_keys(config('students.statuses'))),'note'=>'nullable|string|max:1000'];}public function messages():array{return ['status.required'=>'Status baru wajib dipilih.','status.in'=>'Status siswa tidak valid.','note.max'=>'Keterangan maksimal 1000 karakter.'];}}
