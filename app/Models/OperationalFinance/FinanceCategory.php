<?php

declare(strict_types=1);

namespace App\Models\OperationalFinance;

use Illuminate\Database\Eloquent\Model;

class FinanceCategory extends Model { protected $guarded = []; public function parent(){return $this->belongsTo(self::class,'parent_id');} public function transactions(){return $this->hasMany(OperationalTransaction::class);} }
