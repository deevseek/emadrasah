<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingImportRow extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['raw_data' => 'array', 'normalized_data' => 'array', 'messages' => 'array'];
    }

    public function batch(): BelongsTo { return $this->belongsTo(TeachingImportBatch::class, 'batch_id'); }
    public function personnel(): BelongsTo { return $this->belongsTo(Personnel::class, 'matched_personnel_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class, 'matched_subject_id'); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class, 'matched_classroom_id'); }
}
