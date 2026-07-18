<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\{Factories\HasFactory,Model}; use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryCondition extends Model{use HasFactory; protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean','is_system'=>'boolean'];} public function balances():HasMany{return $this->hasMany(InventoryBalance::class);} }
