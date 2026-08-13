<?php
declare(strict_types=1);
namespace App\Http\Controllers\Students;
use App\Http\Controllers\Controller; use App\Models\{RfidDeviceCommand,Student}; use App\Services\Rfid\RfidWriterService; use Illuminate\Http\{JsonResponse,Request};
class RfidWriterController extends Controller {
 public function store(Request $r,Student $student,RfidWriterService $s):JsonResponse{$r->validate(['replace'=>['sometimes','boolean']]);$c=$s->issue($student,$r->user(),$r->boolean('replace'));return response()->json(['success'=>true,'command_id'=>$c->id,'status'=>$c->status->value],201);}
 public function show(Request $r,Student $student,RfidDeviceCommand $command):JsonResponse{abort_unless($command->student_id===$student->id&&$command->requested_by===$r->user()->id,404);if(in_array($command->status->value,['pending','processing'],true)&&$command->expires_at->isPast())$command->update(['status'=>'expired','failed_at'=>now(),'result'=>['code'=>'WRITE_TIMEOUT']]);$command->refresh()->load('device');return response()->json(['success'=>true,'status'=>$command->status->value,'device'=>$command->device->device_id,'completed_at'=>$command->completed_at?->toIso8601String(),'error_code'=>$command->result['code']??null]);}
}
