<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('citizenship')->nullable()->after('religion');
            $table->unsignedTinyInteger('child_order')->nullable()->after('citizenship');
            $table->unsignedTinyInteger('siblings_count')->nullable()->after('child_order');
            $table->string('family_status')->nullable()->after('siblings_count');
            $table->string('rt', 5)->nullable()->after('address');
            $table->string('rw', 5)->nullable()->after('rt');
            $table->string('previous_exam_number')->nullable()->after('previous_school');
            $table->string('previous_diploma_number')->nullable()->after('previous_exam_number');
            $table->string('blood_type', 3)->nullable()->after('notes');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('blood_type');
            $table->decimal('height_cm', 5, 2)->nullable()->after('weight_kg');
            $table->text('special_needs')->nullable()->after('height_cm');
            $table->text('medical_history')->nullable()->after('special_needs');
            $table->text('allergies')->nullable()->after('medical_history');
            $table->string('bpjs_number')->nullable()->after('allergies');
            $table->string('residence_type')->nullable()->after('bpjs_number');
            $table->string('transportation_mode')->nullable()->after('residence_type');
            $table->decimal('distance_to_school_km', 5, 2)->nullable()->after('transportation_mode');
            $table->unsignedSmallInteger('travel_time_minutes')->nullable()->after('distance_to_school_km');
        });

        Schema::table('guardians', function (Blueprint $table): void {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('religion')->nullable()->after('birth_date');
            $table->string('relationship_type')->nullable()->after('religion');
            $table->string('workplace')->nullable()->after('occupation');
            $table->string('income_range')->nullable()->after('monthly_income');
            $table->string('life_status')->nullable()->after('income_range');
            $table->string('village')->nullable()->after('address');
            $table->string('district')->nullable()->after('village');
            $table->string('city')->nullable()->after('district');
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('province');
        });

        Schema::table('guardian_student', function (Blueprint $table): void {
            $table->boolean('is_financial_responsible')->default(false)->after('is_primary');
        });

        Schema::table('student_documents', function (Blueprint $table): void {
            $table->timestamp('uploaded_at')->nullable()->after('uploaded_by');
        });

        Schema::table('student_status_histories', function (Blueprint $table): void {
            $table->text('notes')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('student_status_histories', fn (Blueprint $table) => $table->dropColumn('notes'));
        Schema::table('student_documents', fn (Blueprint $table) => $table->dropColumn('uploaded_at'));
        Schema::table('guardian_student', fn (Blueprint $table) => $table->dropColumn('is_financial_responsible'));
        Schema::table('guardians', fn (Blueprint $table) => $table->dropColumn(['whatsapp','religion','relationship_type','workplace','income_range','life_status','village','district','city','province','postal_code']));
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn(['citizenship','child_order','siblings_count','family_status','rt','rw','previous_exam_number','previous_diploma_number','blood_type','weight_kg','height_cm','special_needs','medical_history','allergies','bpjs_number','residence_type','transportation_mode','distance_to_school_km','travel_time_minutes']));
    }
};
