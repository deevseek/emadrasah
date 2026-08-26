<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Models\{RfidDevice,User}; use App\Services\Rfid\RfidWriterService; use Database\Seeders\AccessControlSeeder; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class RfidDeviceManagementTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(AccessControlSeeder::class);}
 public function test_operator_can_register_device_and_plain_token_is_only_returned_in_session():void{$operator=User::factory()->create(['must_change_password'=>false]);$operator->assignRole('operator');$response=$this->actingAs($operator)->post(route('rfid-devices.store'),['device_id'=>'rfid-writer-01','name'=>'Writer Tata Usaha','device_type'=>'writer'])->assertRedirect()->assertSessionHas('device_token');$device=RfidDevice::where('device_id','rfid-writer-01')->firstOrFail();$token=$response->getSession()->get('device_token');$this->assertSame(64,strlen($token));$this->assertSame(hash('sha256',$token),$device->token_hash);$this->assertDatabaseMissing('rfid_devices',['token_hash'=>$token]);}
 public function test_heartbeat_marks_device_online_and_records_telemetry():void{$token='token-perangkat-yang-aman';$device=RfidDevice::create(['device_id'=>'rfid-reader-01','name'=>'Reader Gerbang','device_type'=>'reader','token_hash'=>hash('sha256',$token),'is_active'=>true]);$this->withHeaders(['X-Device-ID'=>$device->device_id,'X-Device-Token'=>$token])->postJson('/api/rfid/device/heartbeat',['firmware_version'=>'1.0.0','mode'=>'reader','ip'=>'192.168.1.10','rssi'=>-62])->assertOk()->assertJsonPath('success',true);$this->assertTrue($device->fresh()->isOnline());$this->assertDatabaseHas('rfid_devices',['id'=>$device->id,'firmware_version'=>'1.0.0','rssi'=>-62]);}
 public function test_writer_remains_detectable_while_firmware_reports_idle():void{$device=RfidDevice::create(['device_id'=>'writer-1','name'=>'Writer','device_type'=>'writer','mode'=>'idle','token_hash'=>hash('sha256','token'),'is_active'=>true,'last_seen_at'=>now()->subSeconds(60)]);$this->assertTrue($device->isOnline());$this->assertSame($device->id,app(RfidWriterService::class)->onlineWriter()?->id);}
}
