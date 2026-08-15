<?php
declare(strict_types=1);
namespace App\Services\Hrd;
use App\Contracts\FaceRecognitionService;use App\Exceptions\AttendanceSecurityException;use App\Models\{AttendanceChallenge,AttendanceFaceVerification,Personnel,PersonnelAttendanceDevice,User};use App\Services\Settings\ApplicationSettingService;use Illuminate\Http\{Request,UploadedFile};use Illuminate\Support\Facades\DB;use Illuminate\Support\Str;
class AttendanceSecurityService
{
 public function __construct(private ApplicationSettingService $settings,private FaceRecognitionService $faces){}
 /** @return array{id:string,nonce:string,expires_at:string,face_required:bool} */
 public function challenge(User $user,Personnel $personnel,string $action,Request $request):array
 {
  $this->assertPersonnel($personnel);$device=$this->device($personnel,$request);
  $nonce=Str::random(64);$expires=now()->addSeconds((int)$this->settings->get('hrd_attendance_challenge_ttl_seconds',60));
  $row=AttendanceChallenge::create(['id'=>(string)Str::uuid(),'nonce_hash'=>hash('sha256',$nonce),'user_id'=>$user->id,'personnel_id'=>$personnel->id,'session_hash'=>$this->sessionHash($request),'device_uuid_hash'=>$device?->device_uuid_hash,'intended_action'=>$action,'expires_at'=>$expires]);
  return['id'=>$row->id,'nonce'=>$nonce,'expires_at'=>$expires->toIso8601String(),'face_required'=>(bool)$this->settings->get('hrd_attendance_face_enabled',false)];
 }
 public function verifyFace(User $user,Personnel $personnel,string $challengeId,string $nonce,UploadedFile $snapshot,Request $request):AttendanceFaceVerification
 {
  $challenge=$this->validChallenge($challengeId,$nonce,$user,$personnel,null,$request,false);
  try{$result=$this->faces->verify($personnel,$snapshot,(float)$this->settings->get('hrd_face_confidence_threshold',.80));}catch(AttendanceSecurityException $e){throw $e;}catch(\Throwable){throw new AttendanceSecurityException('FACE_NOT_VERIFIED','Layanan verifikasi wajah tidak tersedia. Silakan hubungi HRD.',503);}
  if(($result['faces']??0)!==1)throw new AttendanceSecurityException('FACE_NOT_VERIFIED','Pastikan tepat satu wajah terlihat dengan jelas.');
  if(($result['matched_personnel_id']??null)!==$personnel->id)throw new AttendanceSecurityException('FACE_IDENTITY_MISMATCH','Wajah tidak sesuai dengan akun yang sedang digunakan.',403);
  if(($result['confidence']??0)<(float)$this->settings->get('hrd_face_confidence_threshold',.80))throw new AttendanceSecurityException('FACE_NOT_VERIFIED','Kecocokan wajah belum memenuhi batas verifikasi.');
  if($this->faces->livenessSupported()&&($result['liveness_passed']??false)!==true)throw new AttendanceSecurityException('FACE_LIVENESS_FAILED','Pemeriksaan keaslian wajah gagal.');
  return AttendanceFaceVerification::create(['id'=>(string)Str::uuid(),'challenge_id'=>$challenge->id,'personnel_id'=>$personnel->id,'provider'=>$this->faces->provider(),'confidence'=>$result['confidence'],'liveness_passed'=>$result['liveness_passed'],'verified_at'=>now(),'expires_at'=>now()->addSeconds((int)$this->settings->get('hrd_face_verification_ttl_seconds',120))]);
 }
 public function consume(string $id,string $nonce,User $user,Personnel $personnel,string $action,Request $request):array
 {
  return DB::transaction(function()use($id,$nonce,$user,$personnel,$action,$request){$challenge=AttendanceChallenge::query()->lockForUpdate()->find($id);$challenge=$this->assertChallenge($challenge,$nonce,$user,$personnel,$action,$request);
   $device=$this->device($personnel,$request);
   $face=null;if($this->settings->get('hrd_attendance_face_enabled',false)){$face=AttendanceFaceVerification::where('challenge_id',$challenge->id)->latest('verified_at')->first();if(!$face)throw new AttendanceSecurityException('FACE_REQUIRED','Verifikasi wajah wajib dilakukan.');if($face->expires_at->isPast())throw new AttendanceSecurityException('FACE_VERIFICATION_EXPIRED','Verifikasi wajah telah kedaluwarsa. Silakan ulangi.');}
   $challenge->update(['used_at'=>now()]);return[$challenge,$device,$face];
  },3);
 }
 private function validChallenge(string $id,string $nonce,User $user,Personnel $personnel,?string $action,Request $request,bool $lock):AttendanceChallenge{$q=AttendanceChallenge::query();if($lock)$q->lockForUpdate();return $this->assertChallenge($q->find($id),$nonce,$user,$personnel,$action,$request);}
 private function assertChallenge(?AttendanceChallenge $c,string $nonce,User $u,Personnel $p,?string $action,Request $r):AttendanceChallenge
 {if(!$c||!hash_equals($c->nonce_hash,hash('sha256',$nonce))||$c->user_id!==$u->id||$c->personnel_id!==$p->id||!hash_equals($c->session_hash,$this->sessionHash($r))||($action&&$c->intended_action!==$action))throw new AttendanceSecurityException('CHALLENGE_INVALID','Challenge absensi tidak valid.',403);if($c->used_at)throw new AttendanceSecurityException('ATTENDANCE_REPLAY_DETECTED','Request absensi telah pernah digunakan.',409);if($c->expires_at->isPast())throw new AttendanceSecurityException('CHALLENGE_EXPIRED','Challenge absensi telah kedaluwarsa.',410);return$c;}
 private function device(Personnel $p,Request $r):?PersonnelAttendanceDevice
 {if(!$this->settings->get('hrd_attendance_device_binding_enabled',true))return null;$uuid=(string)$r->input('device_uuid');if(!Str::isUuid($uuid))throw new AttendanceSecurityException('DEVICE_NOT_TRUSTED','Identitas perangkat tidak valid.',403);$hash=hash('sha256',$uuid);$device=PersonnelAttendanceDevice::where('personnel_id',$p->id)->where('device_uuid_hash',$hash)->first();if($device?->revoked_at)throw new AttendanceSecurityException('DEVICE_REVOKED','Perangkat ini telah dicabut oleh HRD.',403);if(!$device){if(PersonnelAttendanceDevice::where('personnel_id',$p->id)->whereNull('revoked_at')->count()>=(int)$this->settings->get('hrd_attendance_max_devices',2))throw new AttendanceSecurityException('DEVICE_NOT_TRUSTED','Batas jumlah perangkat absensi telah tercapai.',403);$trusted=!$this->settings->get('hrd_attendance_new_device_requires_approval',false);$device=PersonnelAttendanceDevice::create(['personnel_id'=>$p->id,'device_uuid_hash'=>$hash,'device_name'=>$r->input('device_name','Perangkat pribadi'),'browser'=>$r->input('browser'),'platform'=>$r->input('platform'),'user_agent_hash'=>hash('sha256',(string)$r->userAgent()),'first_seen_at'=>now(),'last_seen_at'=>now(),'is_trusted'=>$trusted,'trusted_at'=>$trusted?now():null]);}if(!$device->is_trusted)throw new AttendanceSecurityException('DEVICE_NOT_TRUSTED','Perangkat belum disetujui HRD.',403);$device->update(['last_seen_at'=>now()]);return$device;}
 private function assertPersonnel(Personnel $p):void{if(!$p->is_active)throw new AttendanceSecurityException('PERSONNEL_INACTIVE','Status personalia tidak aktif.',403);}
 private function sessionHash(Request $r):string{return hash('sha256',$r->session()->getId().'|'.config('app.key'));}
}
