<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCardBtaq extends Model
{
    protected $table = 'report_card_btaq';

    protected $fillable = [
        'report_card_id',
        'btaq_level_id',
        'last_material_id',
        'final_score',
        'predicate',
        'achievement_description',
        'development_notes',
        'needs_guidance',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'needs_guidance' => 'boolean',
        ];
    }
}
