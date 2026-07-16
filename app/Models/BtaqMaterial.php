<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BtaqMaterial extends Model
{
    protected $fillable = [
        'btaq_level_id',
        'code',
        'name',
        'category',
        'sequence',
        'target_description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(BtaqLevel::class, 'btaq_level_id');
    }
}
