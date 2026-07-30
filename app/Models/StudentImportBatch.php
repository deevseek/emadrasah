<?php
declare(strict_types=1); namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class StudentImportBatch extends Model { protected $guarded=[]; protected function casts():array{return ['summary'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];} public function rows():HasMany{return $this->hasMany(StudentImportRow::class,'batch_id');} }
