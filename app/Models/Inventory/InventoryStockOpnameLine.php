<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\{Factories\HasFactory,Model}; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryStockOpnameLine extends Model{use HasFactory; protected $guarded=[]; public function opname():BelongsTo{return $this->belongsTo(InventoryStockOpname::class,'inventory_stock_opname_id');} public function item():BelongsTo{return $this->belongsTo(InventoryItem::class,'inventory_item_id');} public function condition():BelongsTo{return $this->belongsTo(InventoryCondition::class,'inventory_condition_id');}}
