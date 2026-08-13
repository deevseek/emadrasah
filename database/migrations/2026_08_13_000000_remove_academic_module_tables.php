<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('teaching_assignment_exceptions');
        Schema::dropIfExists('additional_duties');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('teaching_assignment_sets');
        Schema::dropIfExists('teaching_import_rows');
        Schema::dropIfExists('teaching_import_batches');
        Schema::dropIfExists('subject_grade_loads');
        Schema::dropIfExists('subjects');

        $permissionIds = DB::table('permissions')
            ->where('name', 'like', 'subjects.%')
            ->orWhere('name', 'like', 'teaching-assignments.%')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }

    public function down(): void
    {
        // Modul akademik yang dihapus tidak dipulihkan oleh migration cleanup ini.
    }
};
