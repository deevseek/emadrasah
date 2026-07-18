<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\{Factories\HasFactory,Model};
class InventoryMeasurementUnit extends Model{use HasFactory; protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];}}
