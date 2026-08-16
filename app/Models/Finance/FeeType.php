<?php
declare(strict_types=1); namespace App\Models\Finance; use Illuminate\Database\Eloquent\Model;
class FeeType extends Model { protected $table='fee_types'; protected $guarded=[]; protected function casts():array{return ['active'=>'boolean'];} }
