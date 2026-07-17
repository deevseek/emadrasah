<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionBatch extends Model
{
    protected $fillable = ['source_academic_year_id','target_academic_year_id','source_classroom_id','target_classroom_id','processed_by','processed_at','status','notes'];
    protected function casts(): array { return ['processed_at' => 'datetime']; }
}
