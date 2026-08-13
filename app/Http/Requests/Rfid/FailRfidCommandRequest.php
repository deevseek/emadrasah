<?php
declare(strict_types=1);
namespace App\Http\Requests\Rfid;
use Illuminate\Foundation\Http\FormRequest;
class FailRfidCommandRequest extends FormRequest {public function authorize():bool{return $this->attributes->has('rfid_device');} public function rules():array{return ['error_code'=>['required','string','in:CARD_WRITE_FAILED,CARD_REMOVED,VERIFY_FAILED,DEVICE_ERROR']];}}
