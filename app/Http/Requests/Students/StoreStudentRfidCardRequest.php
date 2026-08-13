<?php
declare(strict_types=1);
namespace App\Http\Requests\Students;
use App\Models\StudentRfidCard;use Illuminate\Foundation\Http\FormRequest;use Illuminate\Validation\Rule;
class StoreStudentRfidCardRequest extends FormRequest {public function authorize():bool{return $this->user()?->can('rfid-cards.manage')===true;}protected function prepareForValidation():void{$this->merge(['uid'=>StudentRfidCard::normalizeUid((string)$this->input('uid'))]);}public function rules():array{return ['uid'=>['required','string','min:4','max:64','regex:/^[A-F0-9]+$/',Rule::unique('student_rfid_cards','uid')]];}public function messages():array{return ['uid.unique'=>'Kartu RFID sudah terdaftar pada siswa lain.'];}}
