<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtaqJournalStudent extends Model
{
    protected $fillable = ['btaq_journal_id', 'student_id', 'attendance_status', 'current_page', 'current_volume', 'surah', 'verse_from', 'verse_to', 'reading_score', 'writing_score', 'tajwid_score', 'memorization_score', 'fluency_score', 'progress_status', 'achievement_notes', 'follow_up'];

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
