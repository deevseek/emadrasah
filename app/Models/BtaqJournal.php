<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtaqJournal extends Model
{
    protected $fillable = ['btaq_group_id', 'journal_date', 'session_number', 'starts_at', 'ends_at', 'btaq_material_id', 'general_notes', 'status', 'submitted_at', 'verified_by', 'verified_at', 'rejection_reason', 'created_by'];

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
