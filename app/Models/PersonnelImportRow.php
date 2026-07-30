<?php
declare(strict_types=1); namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PersonnelImportRow extends Model { protected $guarded=[]; protected function casts():array{return ['raw_data'=>'array','normalized_data'=>'array','messages'=>'array'];} public function batch():BelongsTo{return $this->belongsTo(PersonnelImportBatch::class,'batch_id');} }
