<?php
declare(strict_types=1);
namespace App\Http\Requests\Rfid;
use Illuminate\Foundation\Http\FormRequest;
class CompleteRfidCommandRequest extends FormRequest { public function authorize():bool{return $this->attributes->has('rfid_device');} public function rules():array{return ['success'=>['required','accepted'],'verified'=>['required','accepted'],'uid'=>['required','string','regex:/^[A-Fa-f0-9:-]{4,100}$/'],'card_token'=>['required','string','size:32','regex:/^[A-F0-9]{32}$/']];} }
