<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BtaqJournalStudent extends Model
{
    protected $fillable = [
        'btaq_journal_id',
        'student_id',
        'attendance_status',
        'current_page',
        'current_volume',
        'surah',
        'verse_from',
        'verse_to',
        'reading_score',
        'writing_score',
        'tajwid_score',
        'memorization_score',
        'fluency_score',
        'progress_status',
        'achievement_notes',
        'follow_up',
    ];

    protected function casts(): array
    {
        return [
            'current_page' => 'integer',
            'verse_from' => 'integer',
            'verse_to' => 'integer',
            'reading_score' => 'decimal:2',
            'writing_score' => 'decimal:2',
            'tajwid_score' => 'decimal:2',
            'memorization_score' => 'decimal:2',
            'fluency_score' => 'decimal:2',
        ];
    }
}
