<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCardSubject extends Model
{
    protected $fillable = [
        'report_card_id',
        'subject_id',
        'final_score',
        'predicate',
        'minimum_passing_grade',
        'achievement_description',
        'notes',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'minimum_passing_grade' => 'integer',
            'sequence' => 'integer',
        ];
    }
}
