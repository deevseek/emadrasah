<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRfidCard extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['is_active' => 'boolean', 'registered_at' => 'datetime', 'last_used_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public static function normalizeUid(string $uid): string { return strtoupper((string) preg_replace('/[^A-Fa-f0-9]/', '', $uid)); }
    public function maskedUid(): string
    {
        $parts = str_split($this->uid, 2);
        return collect($parts)->map(fn (string $part, int $index) => in_array($index, [0, 1, count($parts) - 1], true) ? $part : '••')->join(':');
    }
}
