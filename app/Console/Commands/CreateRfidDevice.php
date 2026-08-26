<?php
declare(strict_types=1);
namespace App\Console\Commands;
use App\Models\RfidDevice;use Illuminate\Console\Command;use Illuminate\Support\Str;
class CreateRfidDevice extends Command {protected $signature='rfid:device-create {device_id} {--name=} {--type=reader}';protected $description='Membuat kredensial perangkat RFID';public function handle():int{$type=(string)$this->option('type');if(!in_array($type,['reader','writer'],true)){$this->error('Tipe harus reader atau writer.');return self::FAILURE;}$token=Str::random(64);RfidDevice::create(['device_id'=>$this->argument('device_id'),'name'=>$this->option('name')?:$this->argument('device_id'),'device_type'=>$type,'token_hash'=>hash('sha256',$token),'is_active'=>true]);$this->warn('Simpan token berikut dengan aman. Token tidak dapat ditampilkan kembali.');$this->line($token);return self::SUCCESS;}}
