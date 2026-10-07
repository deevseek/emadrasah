<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SemesterType;
use App\Models\{AcademicSubject, AcademicYear, Classroom, ClassroomMembership, GradeLevel, Personnel, Semester, Student, TeachingJournal, User};
use App\Services\Academic\TeachingJournalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Route, Schema};
use Spatie\Permission\Models\{Permission, Role};
use Tests\TestCase;

class TeachingJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_attendance_and_template_tables_are_available(): void
    {
        $this->assertSame('text', Schema::getColumnType('teaching_journals', 'topic'));
        $this->assertTrue(Schema::hasColumns('teaching_journal_attendances', ['teaching_journal_id', 'student_id', 'status', 'notes']));
        $this->assertTrue(Schema::hasColumns('teaching_journal_templates', ['name', 'original_name', 'path', 'is_active', 'uploaded_by']));
    }

    public function test_migration_recovers_after_attendance_table_was_partially_created(): void
    {
        Schema::drop('teaching_journal_templates');
        Schema::drop('teaching_journal_attendances');
        Schema::create('teaching_journal_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teaching_journal_id');
            $table->foreignId('student_id');
            $table->string('status', 20);
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_08_24_000000_integrate_teaching_journal_attendance_and_templates.php');
        $migration->up();

        $this->assertTrue(Schema::hasIndex('teaching_journal_attendances', 'tj_attendance_journal_student_unique'));
        $this->assertTrue(Schema::hasTable('teaching_journal_templates'));
    }

    public function test_journal_exposes_attendance_snapshots_and_report_routes(): void
    {
        $this->assertSame('teaching_journal_id', (new TeachingJournal)->attendances()->getForeignKeyName());
        $this->assertTrue(Route::has('academic.teaching-journals.template.store'));
        $this->assertTrue(Route::has('academic.teaching-journals.report'));
    }

    public function test_teacher_can_create_and_update_journal_with_topic_longer_than_255_characters(): void
    {
        [$user, $payload] = $this->journalContext();
        $payload['topic'] = str_repeat('Materi pembelajaran ', 30);

        $response = $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload);

        $journal = TeachingJournal::sole();
        $response->assertRedirect(route('academic.teaching-journals.show', $journal));
        $this->assertSame($payload['topic'], $journal->topic);

        $payload['topic'] = str_repeat('Uraian pembelajaran lanjutan ', 20);
        $this->actingAs($user)
            ->put(route('academic.teaching-journals.update', $journal), $payload)
            ->assertRedirect(route('academic.teaching-journals.show', $journal));
        $this->assertSame($payload['topic'], $journal->refresh()->topic);
    }

    public function test_journal_validation_uses_indonesian_messages_and_humanized_attributes(): void
    {
        app()->setLocale('id');
        [$user, $payload] = $this->journalContext();
        $payload['topic'] = '';
        $payload['learning_method'] = str_repeat('a', 256);

        $response = $this->from(route('academic.teaching-journals.create'))
            ->actingAs($user)
            ->post(route('academic.teaching-journals.store'), $payload);

        $response->assertRedirect(route('academic.teaching-journals.create'))
            ->assertSessionHasErrors(['topic', 'learning_method']);
        $errors = session('errors')->all();
        $this->assertStringContainsString('uraian mengajar wajib diisi', mb_strtolower(implode(' ', $errors)));
        $this->assertStringContainsString('metode pembelajaran tidak boleh lebih dari 255 karakter', mb_strtolower(implode(' ', $errors)));
        $this->assertStringNotContainsString('validation.', implode(' ', $errors));
    }

    public function test_existing_teacher_role_receives_journal_input_permissions_from_migration(): void
    {
        $role = Role::findByName('guru');
        $role->revokePermissionTo(['teaching-journals.view', 'teaching-journals.manage']);

        $migration = require database_path('migrations/2026_09_07_000000_grant_teaching_journal_permissions_to_teachers.php');
        $migration->up();

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo('teaching-journals.view'));
        $this->assertTrue($role->hasPermissionTo('teaching-journals.manage'));
    }

    public function test_empty_report_redirects_to_journal_index_with_a_helpful_message(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_at' => '2026-07-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'type' => SemesterType::Ganjil,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $query = [
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'journal_date' => '2026-08-24',
        ];

        $response = $this->withoutMiddleware()->actingAs($user)->get(route(
            'academic.teaching-journals.report',
            ['format' => 'docx'] + $query,
        ));

        $response->assertRedirect(route('academic.teaching-journals.index', $query));
        $response->assertSessionHas('error', 'Laporan tidak dapat diunduh karena tidak ada jurnal pada filter yang dipilih.');
    }

    public function test_teacher_account_is_connected_to_active_personnel_with_the_same_email(): void
    {
        $user = User::factory()->create(['email' => 'guru@example.test']);
        $personnel = Personnel::create([
            'full_name' => 'Ustaz Ahmad',
            'gender' => 'male',
            'employment_status' => 'Tetap',
            'position' => 'Guru',
            'email' => 'GURU@example.test',
            'is_active' => true,
        ]);

        $resolved = app(TeachingJournalService::class)->activePersonnel($user);

        $this->assertTrue($resolved->is($personnel));
        $this->assertSame($user->id, $personnel->refresh()->user_id);
    }

    public function test_teacher_account_is_not_connected_to_inactive_personnel(): void
    {
        $user = User::factory()->create(['email' => 'guru@example.test']);
        $personnel = Personnel::create([
            'full_name' => 'Ustaz Ahmad',
            'gender' => 'male',
            'employment_status' => 'Tetap',
            'position' => 'Guru',
            'email' => 'guru@example.test',
            'is_active' => false,
        ]);

        $this->assertNull(app(TeachingJournalService::class)->activePersonnel($user));
        $this->assertNull($personnel->refresh()->user_id);
    }

    private function journalContext(): array
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            Permission::findOrCreate('teaching-journals.view'),
            Permission::findOrCreate('teaching-journals.manage'),
        ]);
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'Semester Ganjil', 'type' => SemesterType::Ganjil, 'starts_at' => '2026-07-01', 'ends_at' => '2026-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['number' => 1, 'name' => 'Kelas 1', 'roman_label' => 'I', 'sort_order' => 1, 'is_active' => true]);
        $classroom = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'code' => 'A', 'is_active' => true]);
        $student = Student::create(['full_name' => 'Siswa Jurnal', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'status' => 'active', 'joined_at' => '2026-07-01']);
        Personnel::create(['user_id' => $user->id, 'full_name' => 'Guru Jurnal', 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'email' => $user->email, 'is_active' => true]);
        $subject = AcademicSubject::create(['name' => 'Pelajaran Jurnal', 'is_active' => true]);

        return [$user, [
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'academic_subject_id' => $subject->id,
            'journal_date' => '2026-10-07',
            'lesson_number' => '1–2',
            'topic' => 'Materi pembelajaran',
            'learning_objectives' => 'Siswa memahami materi.',
            'learning_material' => 'Materi inti.',
            'learning_method' => 'Diskusi',
            'learning_activity' => 'Pembukaan, inti, dan penutup.',
            'assignment' => 'Latihan mandiri.',
            'notes' => 'Pembelajaran berjalan baik.',
            'attendances' => [['student_id' => $student->id, 'status' => 'present', 'notes' => null]],
        ]];
    }
}
