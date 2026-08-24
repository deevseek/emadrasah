<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingJournalTemplate extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
