<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BtaqJournal extends Model
{
    protected $fillable = [
        'btaq_group_id',
        'journal_date',
        'session_number',
        'starts_at',
        'ends_at',
        'btaq_material_id',
        'general_notes',
        'status',
        'submitted_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'session_number' => 'integer',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
