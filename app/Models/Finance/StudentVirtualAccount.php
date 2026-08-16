<?php
declare(strict_types=1); namespace App\Models\Finance; use Illuminate\Database\Eloquent\Model;
class StudentVirtualAccount extends Model { protected $table='student_virtual_accounts'; protected $guarded=[]; protected function casts():array{return ['expires_at'=>'datetime','last_synced_at'=>'datetime','metadata'=>'encrypted:array'];} }
