<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class TeacherConsultation extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['last_message_at' => 'datetime']; }

    public function guardian(): BelongsTo { return $this->belongsTo(GuardianProfile::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_user_id'); }
    public function messages(): HasMany { return $this->hasMany(TeacherConsultationMessage::class); }
}
