<?php
declare(strict_types=1); namespace App\Http\Requests\Personnel;
use Illuminate\Foundation\Http\FormRequest;
class ConfirmPersonnelImportRequest extends FormRequest {public function authorize():bool{return $this->user()->can('personnel.import');}public function rules():array{return ['batch_id'=>'required|integer|exists:personnel_import_batches,id'];}}
