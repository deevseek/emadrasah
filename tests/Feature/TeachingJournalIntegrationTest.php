<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SemesterType;
use App\Models\{AcademicSubject, AcademicYear, Classroom, ClassroomMembership, GradeLevel, Personnel, Semester, Student, StudentAttendance, TeachingJournal, TeachingJournalAttendance, TeachingJournalTemplate, User};
use App\Services\Academic\{JournalAttendanceResolver, TeachingJournalReportService, TeachingJournalService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Route, Schema};
use Spatie\Permission\Models\{Permission, Role};
use Tests\TestCase;
use ZipArchive;

class TeachingJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_attendance_and_template_tables_are_available(): void
    {
        $this->assertSame('text', Schema::getColumnType('teaching_journals', 'topic'));
        $this->assertTrue(Schema::hasColumns('teaching_journal_attendances', ['teaching_journal_id', 'student_id', 'status', 'source', 'notes', 'correction_reason', 'corrected_by', 'corrected_at']));
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

    public function test_attendance_source_migration_recovers_when_corrected_by_already_exists(): void
    {
        Schema::table('teaching_journal_attendances', function (Blueprint $table): void {
            $table->dropColumn(['source', 'correction_reason', 'corrected_at']);
        });

        $this->assertTrue(Schema::hasColumn('teaching_journal_attendances', 'corrected_by'));
        $migration = require database_path('migrations/2026_10_15_000000_add_source_to_teaching_journal_attendances.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumns('teaching_journal_attendances', [
            'source',
            'correction_reason',
            'corrected_by',
            'corrected_at',
        ]));
    }

    public function test_journal_exposes_attendance_snapshots_and_report_routes(): void
    {
        $this->assertSame('teaching_journal_id', (new TeachingJournal)->attendances()->getForeignKeyName());
        $this->assertTrue(Route::has('academic.teaching-journals.template.store'));
        $this->assertTrue(Route::has('academic.teaching-journals.report'));
        $resolver = app(JournalAttendanceResolver::class);
        foreach (['1-2', '1–2', '1/2', '1 dan 2'] as $format) $this->assertTrue($resolver->isOpeningLesson($format));
        $this->assertFalse($resolver->isOpeningLesson('3-4'));
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

    public function test_opening_lesson_becomes_daily_source_and_following_lessons_inherit_every_status(): void
    {
        [$user, $payload] = $this->journalContext();
        $service = app(TeachingJournalService::class);
        $statuses = ['sick', 'permitted', 'absent'];
        $students = [Student::findOrFail($payload['attendances'][0]['student_id'])];
        foreach (['Siswa Izin', 'Siswa Alpa'] as $name) {
            $student = Student::create(['full_name' => $name, 'gender' => 'male', 'status' => 'active']);
            ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $payload['classroom_id'], 'academic_year_id' => $payload['academic_year_id'], 'status' => 'active', 'joined_at' => '2026-07-01']);
            $students[] = $student;
        }
        $payload['attendances'] = collect($students)->values()->map(fn ($student, $index) => ['student_id' => $student->id, 'status' => $statuses[$index], 'notes' => null])->all();

        $following = $service->save([...$payload, 'lesson_number' => '3-4', 'topic' => 'Jurnal lanjutan', 'attendances' => null], $user);
        $this->assertSame(['pending', 'pending', 'pending'], $following->attendances()->orderBy('student_id')->pluck('status')->all());

        $service->save($payload, $user);
        $later = $service->save([...$payload, 'lesson_number' => '5–6', 'topic' => 'Jurnal terakhir', 'attendances' => null], $user);

        $this->assertSame($statuses, StudentAttendance::orderBy('student_id')->pluck('status')->all());
        $this->assertSame($statuses, $following->attendances()->orderBy('student_id')->pluck('status')->all());
        $this->assertSame($statuses, $later->attendances()->orderBy('student_id')->pluck('status')->all());
        $this->assertSame(['manual'], StudentAttendance::distinct()->pluck('source')->all());
    }

    public function test_lesson_correction_does_not_change_daily_attendance_or_other_contexts(): void
    {
        [$user, $payload] = $this->journalContext();
        $service = app(TeachingJournalService::class);
        $service->save($payload, $user);
        $studentId = $payload['attendances'][0]['student_id'];
        $following = $service->save([...$payload, 'lesson_number' => '3/4', 'topic' => 'Jurnal koreksi', 'attendances' => null, 'attendance_corrections' => [['student_id' => $studentId, 'status' => 'sick', 'reason' => 'Sakit saat pelajaran berlangsung.']]], $user);

        $this->assertSame('present', StudentAttendance::where('student_id', $studentId)->sole()->status->value);
        $snapshot = $following->attendances()->sole();
        $this->assertSame('sick', $snapshot->status->value);
        $this->assertSame('correction', $snapshot->source);
        $this->assertSame($user->id, $snapshot->corrected_by);

        $otherDate = $service->save([...$payload, 'journal_date' => '2026-10-08', 'lesson_number' => '3-4', 'topic' => 'Tanggal lain', 'attendances' => null], $user);
        $otherClassroom = Classroom::create(['academic_year_id' => $payload['academic_year_id'], 'grade_level_id' => Classroom::findOrFail($payload['classroom_id'])->grade_level_id, 'code' => 'B', 'is_active' => true]);
        $otherStudent = Student::create(['full_name' => 'Siswa Rombel Lain', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['student_id' => $otherStudent->id, 'classroom_id' => $otherClassroom->id, 'academic_year_id' => $payload['academic_year_id'], 'status' => 'active', 'joined_at' => '2026-07-01']);
        $otherClassroomJournal = $service->save([...$payload, 'classroom_id' => $otherClassroom->id, 'lesson_number' => '3-4', 'topic' => 'Rombel lain', 'attendances' => null], $user);
        $service->save([...$payload, 'topic' => 'Perubahan hari pertama', 'attendances' => [['student_id' => $studentId, 'status' => 'absent', 'notes' => null]]], $user, TeachingJournal::where('lesson_number', '1–2')->firstOrFail());
        $this->assertSame('pending', $otherDate->attendances()->sole()->status->value);
        $this->assertSame('pending', $otherClassroomJournal->attendances()->sole()->status->value);
        $this->assertSame('sick', $following->attendances()->sole()->status->value);
    }

    public function test_opening_lesson_does_not_overwrite_rfid_and_repeated_update_does_not_duplicate_rows(): void
    {
        [$user, $payload] = $this->journalContext();
        $studentId = $payload['attendances'][0]['student_id'];
        StudentAttendance::create(['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $payload['classroom_id'], 'student_id' => $studentId, 'attendance_date' => $payload['journal_date'], 'status' => 'present', 'source' => 'rfid', 'scanned_at' => now(), 'recorded_by' => null]);
        $payload['attendances'][0]['status'] = 'absent';
        $service = app(TeachingJournalService::class);
        $journal = $service->save($payload, $user);
        $service->save($payload, $user, $journal);

        $daily = StudentAttendance::sole();
        $this->assertSame('rfid', $daily->source->value);
        $this->assertSame('present', $daily->status->value);
        $this->assertSame(1, TeachingJournalAttendance::where('teaching_journal_id', $journal->id)->count());
        $this->assertSame(1, StudentAttendance::count());
    }

    public function test_following_lesson_rejects_browser_supplied_primary_attendance(): void
    {
        [$user, $payload] = $this->journalContext();
        $payload['lesson_number'] = '3-4';

        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload)
            ->assertSessionHasErrors('attendances');
        $this->assertDatabaseCount('teaching_journals', 0);
    }

    public function test_opening_lesson_rejects_student_outside_selected_classroom(): void
    {
        [$user, $payload] = $this->journalContext();
        $outsideStudent = Student::create(['full_name' => 'Siswa Rombel Lain', 'gender' => 'female', 'status' => 'active']);
        $payload['attendances'] = [['student_id' => $outsideStudent->id, 'status' => 'present', 'notes' => null]];

        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload)
            ->assertSessionHasErrors('attendances');
        $this->assertDatabaseCount('student_attendances', 0);
        $this->assertDatabaseCount('teaching_journals', 0);
    }

    public function test_journal_with_centralized_attendance_still_renders_pdf_view_and_docx_report(): void
    {
        [$user, $payload] = $this->journalContext();
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $journal->load(['academicYear', 'semester', 'classroom.gradeLevel', 'subject', 'personnel', 'attendances']);

        $html = view('academic.teaching-journals.report', ['journals' => collect([$journal])])->render();
        $this->assertStringContainsString('Materi pembelajaran', $html);
        $this->assertStringContainsString('<td>1</td><td>0</td>', $html);

        $templatePath = tempnam(sys_get_temp_dir(), 'jurnal-template-');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($templatePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('word/document.xml', '<w:document><w:tr><w:tc>${no}</w:tc><w:tc>${uraian_mengajar}</w:tc><w:tc>${hadir}</w:tc></w:tr></w:document>');
        $zip->close();
        $output = app(TeachingJournalReportService::class)->createDocx(new TeachingJournalTemplate, collect([$journal]), $templatePath);
        $result = new ZipArchive;
        $this->assertTrue($result->open($output) === true);
        $document = $result->getFromName('word/document.xml');
        $result->close();
        @unlink($templatePath);
        @unlink($output);
        $this->assertIsString($document);
        $this->assertStringContainsString('Materi pembelajaran', $document);
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
