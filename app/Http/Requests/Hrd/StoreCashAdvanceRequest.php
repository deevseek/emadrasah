<?php
declare(strict_types=1);namespace App\Http\Requests\Hrd;use Illuminate\Foundation\Http\FormRequest;
class StoreCashAdvanceRequest extends FormRequest{public function authorize():bool{return $this->user()?->canAny(['personnel-cash-advance.create','personnel-cash-advance.request'])===true;}public function rules():array{return ['personnel_id'=>['nullable','exists:personnel,id'],'amount'=>['required','numeric','min:1'],'installment_amount'=>['required','numeric','min:1','lte:amount'],'reason'=>['required','string','max:2000'],'note'=>['nullable','string','max:2000']];}}
