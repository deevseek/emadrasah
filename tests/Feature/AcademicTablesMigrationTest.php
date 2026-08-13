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

    public function test_journal_migration_recovers_when_teaching_journals_table_already_exists(): void
    {
        Schema::drop('classroom_journals');

        $migration = require database_path('migrations/2026_08_13_200000_create_academic_journal_tables.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('teaching_journals'));
        $this->assertTrue(Schema::hasTable('classroom_journals'));
    }
}
