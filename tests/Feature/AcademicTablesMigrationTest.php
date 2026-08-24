<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicTablesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_recovers_when_academic_subjects_table_already_exists(): void
    {
        DB::table('academic_subjects')->insert([
            'name' => 'Fikih',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Schema::drop('student_grades');
        Schema::drop('student_attendances');

        $migration = require database_path('migrations/2026_08_13_100000_create_new_academic_tables.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('academic_subjects'));
        $this->assertTrue(Schema::hasTable('student_attendances'));
        $this->assertTrue(Schema::hasTable('student_grades'));
        $this->assertDatabaseHas('academic_subjects', ['name' => 'Fikih']);
    }

    public function test_classroom_journal_module_tables_and_permissions_are_removed(): void
    {
        $this->assertTrue(Schema::hasTable('teaching_journals'));
        $this->assertFalse(Schema::hasTable('classroom_journals'));
        $this->assertDatabaseMissing('permissions', ['name' => 'classroom-journals.view']);
        $this->assertDatabaseMissing('permissions', ['name' => 'classroom-journals.manage']);
        $this->assertDatabaseMissing('permissions', ['name' => 'classroom-journals.view-all']);
    }
}
