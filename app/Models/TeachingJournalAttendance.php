<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;

class TeachingJournalAttendance extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['status' => AttendanceStatus::class]; }
    public function journal() { return $this->belongsTo(TeachingJournal::class, 'teaching_journal_id'); }
    public function student() { return $this->belongsTo(Student::class); }
}
