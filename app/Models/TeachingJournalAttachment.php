<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingJournalAttachment extends Model
{
    protected $guarded = [];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(TeachingJournal::class, 'teaching_journal_id');
    }
}
