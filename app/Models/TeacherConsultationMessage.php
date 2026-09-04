<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherConsultationMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['read_at' => 'datetime']; }

    public function consultation(): BelongsTo { return $this->belongsTo(TeacherConsultation::class, 'teacher_consultation_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_user_id'); }
}
