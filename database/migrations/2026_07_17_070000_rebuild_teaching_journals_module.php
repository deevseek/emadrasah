<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table): void {
            foreach ([
                'scheduled_start_time' => fn () => $table->time('scheduled_start_time')->nullable()->after('journal_date'),
                'scheduled_end_time' => fn () => $table->time('scheduled_end_time')->nullable()->after('scheduled_start_time'),
                'actual_start_time' => fn () => $table->time('actual_start_time')->nullable()->after('scheduled_end_time'),
                'actual_end_time' => fn () => $table->time('actual_end_time')->nullable()->after('actual_start_time'),
                'meeting_number' => fn () => $table->unsignedSmallInteger('meeting_number')->nullable()->after('lesson_hours'),
                'learning_topic' => fn () => $table->string('learning_topic')->nullable()->after('meeting_number'),
                'learning_material' => fn () => $table->text('learning_material')->nullable()->after('learning_objectives'),
                'learning_method' => fn () => $table->string('learning_method')->nullable()->after('learning_material'),
                'learning_media' => fn () => $table->string('learning_media')->nullable()->after('learning_method'),
                'learning_activity' => fn () => $table->text('learning_activity')->nullable()->after('learning_media'),
                'assessment_activity' => fn () => $table->text('assessment_activity')->nullable()->after('learning_activity'),
                'homework' => fn () => $table->text('homework')->nullable()->after('assessment_activity'),
                'obstacles' => fn () => $table->text('obstacles')->nullable()->after('teacher_notes'),
                'follow_up' => fn () => $table->text('follow_up')->nullable()->after('obstacles'),
                'verification_notes' => fn () => $table->text('verification_notes')->nullable()->after('verified_at'),
                'rejected_by' => fn () => $table->foreignId('rejected_by')->nullable()->after('verification_notes')->constrained('users')->nullOnDelete(),
                'rejected_at' => fn () => $table->timestamp('rejected_at')->nullable()->after('rejected_by'),
                'created_by' => fn () => $table->foreignId('created_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete(),
                'updated_by' => fn () => $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('teaching_journals', $column)) { $callback(); }
            }
            $table->unique(['lesson_schedule_id', 'journal_date'], 'tj_schedule_date_unique');
            $table->unique(['teaching_assignment_id', 'meeting_number'], 'tj_assignment_meeting_unique');
        });
    }

    public function down(): void {}
};
