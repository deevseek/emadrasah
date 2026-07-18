<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\{Factories\HasFactory,Model}; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryBalance extends Model{use HasFactory; protected $guarded=[]; public function item():BelongsTo{return $this->belongsTo(InventoryItem::class,'inventory_item_id');} public function location():BelongsTo{return $this->belongsTo(InventoryLocation::class,'inventory_location_id');} public function condition():BelongsTo{return $this->belongsTo(InventoryCondition::class,'inventory_condition_id');}}
