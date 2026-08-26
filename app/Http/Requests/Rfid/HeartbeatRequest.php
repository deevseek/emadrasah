<?php
declare(strict_types=1);
namespace App\Http\Requests\Rfid;
use Illuminate\Foundation\Http\FormRequest;
class HeartbeatRequest extends FormRequest {public function authorize():bool{return $this->attributes->has('rfid_device');} public function rules():array{return ['firmware_version'=>['required','string','max:50'],'ip'=>['nullable','ip'],'rssi'=>['nullable','integer','between:-120,0'],'mode'=>['required','in:reader,writer,idle,writing']];}}
