<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const PERMISSIONS = [
        'classroom-journals.view',
        'classroom-journals.manage',
        'classroom-journals.view-all',
    ];

    public function up(): void
    {
        Schema::dropIfExists('classroom_journals');

        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }

    public function down(): void
    {
        Schema::create('classroom_journals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->index()->constrained()->restrictOnDelete();
            $table->date('journal_date')->index();
            $table->text('agenda')->nullable();
            $table->text('classroom_condition')->nullable();
            $table->text('student_discipline')->nullable();
            $table->text('important_events')->nullable();
            $table->text('teacher_notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'journal_date']);
        });

        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
