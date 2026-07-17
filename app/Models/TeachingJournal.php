<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeachingJournalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class TeachingJournal extends Model
{
    protected $fillable = ['teaching_assignment_id','lesson_schedule_id','employee_id','subject_id','classroom_id','academic_year_id','semester_id','journal_date','starts_at','ends_at','scheduled_start_time','scheduled_end_time','actual_start_time','actual_end_time','lesson_hours','meeting_number','learning_topic','material','learning_objectives','learning_material','method','learning_method','media','learning_media','learning_activity','assignment','homework','assessment','assessment_activity','teacher_notes','obstacles','follow_up','status','submitted_at','verified_by','verified_at','verification_notes','rejected_by','rejected_at','rejection_reason','created_by','updated_by'];

    protected function casts(): array
    {
        return ['journal_date'=>'date','submitted_at'=>'datetime','verified_at'=>'datetime','rejected_at'=>'datetime','status'=>TeachingJournalStatus::class];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function teachingAssignment(): BelongsTo { return $this->belongsTo(TeachingAssignment::class); }
    public function lessonSchedule(): BelongsTo { return $this->belongsTo(LessonSchedule::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function rejector(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function students(): BelongsToMany { return $this->belongsToMany(Student::class, 'teaching_journal_student')->withPivot(['status','notes'])->withTimestamps(); }

    public function isEditableByTeacher(): bool
    {
        return in_array($this->status, [TeachingJournalStatus::Draft, TeachingJournalStatus::Rejected], true);
    }
}
