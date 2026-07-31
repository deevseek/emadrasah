<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class TeachingImportBatch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['sheet_names' => 'array', 'summary' => 'array'];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function rows(): HasMany { return $this->hasMany(TeachingImportRow::class, 'batch_id'); }
}
