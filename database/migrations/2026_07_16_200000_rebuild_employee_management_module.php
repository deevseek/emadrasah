<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('front_title')->nullable()->after('user_id');
            $table->string('back_title')->nullable()->after('name');
            $table->string('nip')->nullable()->unique('emp_nip_unique')->after('employee_number');
            $table->string('nuptk')->nullable()->unique('emp_nuptk_unique')->after('nip');
            $table->string('religion')->nullable()->after('birth_date');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('village')->nullable()->after('address');
            $table->string('district')->nullable()->after('village');
            $table->string('city')->nullable()->after('district');
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('province');
            $table->string('position')->nullable()->after('employee_status');
            $table->text('notes')->nullable()->after('left_at');
            $table->string('last_education')->nullable()->after('notes');
            $table->string('major')->nullable()->after('last_education');
            $table->string('education_institution')->nullable()->after('major');
            $table->unsignedSmallInteger('graduation_year')->nullable()->after('education_institution');
            $table->unique('email', 'emp_email_unique');
        });

        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'type'], 'emp_doc_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique('emp_nip_unique');
            $table->dropUnique('emp_nuptk_unique');
            $table->dropUnique('emp_email_unique');
            $table->dropColumn(['front_title', 'back_title', 'nip', 'nuptk', 'religion', 'whatsapp', 'village', 'district', 'city', 'province', 'postal_code', 'position', 'notes', 'last_education', 'major', 'education_institution', 'graduation_year']);
        });
    }
};
