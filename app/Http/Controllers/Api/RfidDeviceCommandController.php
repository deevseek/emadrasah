<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use App\Http\Requests\Rfid\{CompleteRfidCommandRequest,FailRfidCommandRequest,HeartbeatRequest}; use App\Models\RfidDeviceCommand; use App\Services\Rfid\RfidWriterService; use Illuminate\Http\{JsonResponse,Request};
class RfidDeviceCommandController extends Controller {
 public function next(Request $r,RfidWriterService $s):JsonResponse{$c=$s->next($r->attributes->get('rfid_device'));return response()->json(['success'=>true,'command'=>$c?['id'=>$c->id,'type'=>$c->command,'student_id'=>$c->student_id,'card_token'=>$c->payload['card_token'],'expires_at'=>$c->expires_at->toIso8601String()]:null]);}
 public function complete(CompleteRfidCommandRequest $r,RfidDeviceCommand $command,RfidWriterService $s):JsonResponse{$s->complete($r->attributes->get('rfid_device'),$command,$r->validated());return response()->json(['success'=>true]);}
 public function fail(FailRfidCommandRequest $r,RfidDeviceCommand $command,RfidWriterService $s):JsonResponse{$s->fail($r->attributes->get('rfid_device'),$command,$r->validated('error_code'));return response()->json(['success'=>true]);}
 public function heartbeat(HeartbeatRequest $r):JsonResponse{$r->attributes->get('rfid_device')->update(['firmware_version'=>$r->validated('firmware_version'),'ip_address'=>$r->validated('ip'),'rssi'=>$r->validated('rssi'),'mode'=>$r->validated('mode'),'last_seen_at'=>now()]);return response()->json(['success'=>true,'server_time'=>now()->toIso8601String()]);}
}
