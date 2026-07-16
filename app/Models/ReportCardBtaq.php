<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCardBtaq extends Model
{
    protected $fillable = ['report_card_id', 'btaq_level_id', 'last_material_id', 'final_score', 'predicate', 'achievement_description', 'development_notes', 'needs_guidance'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_required' => 'boolean',
            'needs_guidance' => 'boolean',
            'joined_at' => 'date',
            'completed_at' => 'date',
            'journal_date' => 'date',
            'assessment_date' => 'date',
            'achievement_date' => 'date',
            'report_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'published_at' => 'datetime',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }
}
