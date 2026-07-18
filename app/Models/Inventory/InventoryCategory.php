<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\{Factories\HasFactory,Model,SoftDeletes}; use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryCategory extends Model{use HasFactory,SoftDeletes; protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];} public function items():HasMany{return $this->hasMany(InventoryItem::class);} }
