<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('student_number')->nullable()->unique(); $table->string('national_student_number')->nullable()->unique(); $table->string('national_identity_number')->nullable()->unique(); $table->string('family_card_number')->nullable();
            $table->string('name'); $table->string('nickname')->nullable(); $table->string('gender'); $table->string('birth_place')->nullable(); $table->date('birth_date')->nullable(); $table->string('religion')->nullable();
            $table->text('address')->nullable(); $table->string('village')->nullable(); $table->string('district')->nullable(); $table->string('city')->nullable(); $table->string('province')->nullable(); $table->string('postal_code')->nullable();
            $table->string('phone')->nullable(); $table->string('email')->nullable(); $table->string('previous_school')->nullable(); $table->date('admission_date')->nullable(); $table->string('admission_type'); $table->string('student_status')->index();
            $table->date('graduation_date')->nullable(); $table->string('photo_path')->nullable(); $table->text('notes')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('guardians', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete(); $table->string('national_identity_number')->nullable()->unique(); $table->string('family_card_number')->nullable();
            $table->string('name'); $table->string('gender')->nullable(); $table->string('birth_place')->nullable(); $table->date('birth_date')->nullable(); $table->string('education')->nullable(); $table->string('occupation')->nullable(); $table->decimal('monthly_income',12,2)->nullable();
            $table->string('phone')->nullable(); $table->string('email')->nullable(); $table->text('address')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('guardian_id')->constrained()->cascadeOnDelete(); $table->string('relationship');
            $table->boolean('is_primary')->default(false)->index(); $table->boolean('is_emergency_contact')->default(false)->index(); $table->boolean('lives_with_student')->default(false); $table->boolean('financially_responsible')->default(false); $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['student_id','guardian_id']);
        });
        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('enrollment_number')->nullable(); $table->date('enrolled_at')->nullable(); $table->date('completed_at')->nullable(); $table->string('enrollment_status')->index(); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('student_status_histories', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->string('previous_status')->nullable(); $table->string('new_status'); $table->date('effective_date'); $table->text('reason')->nullable(); $table->string('destination_school')->nullable(); $table->string('document_number')->nullable(); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('student_documents', function (Blueprint $table): void {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->string('document_type'); $table->string('document_number')->nullable(); $table->string('file_path'); $table->date('issued_at')->nullable(); $table->date('expires_at')->nullable(); $table->text('notes')->nullable(); $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('student_documents'); Schema::dropIfExists('student_status_histories'); Schema::dropIfExists('student_enrollments'); Schema::dropIfExists('guardian_student'); Schema::dropIfExists('guardians'); Schema::dropIfExists('students'); }
};
