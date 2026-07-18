<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\{Factories\HasFactory,Model}; use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class InventoryStockOpname extends Model{use HasFactory; protected $guarded=[]; protected function casts():array{return ['opname_date'=>'date','posted_at'=>'datetime'];} public function location():BelongsTo{return $this->belongsTo(InventoryLocation::class,'inventory_location_id');} public function lines():HasMany{return $this->hasMany(InventoryStockOpnameLine::class);} }
