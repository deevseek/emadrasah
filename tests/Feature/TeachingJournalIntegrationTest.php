<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SemesterType;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\Personnel;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use App\Models\TeachingJournalAttendance;
use App\Models\TeachingJournalTemplate;
use App\Models\User;
use App\Services\Academic\AcademicAccessService;
use App\Services\Academic\JournalAttendanceResolver;
use App\Services\Academic\TeachingJournalReportService;
use App\Services\Academic\TeachingJournalService;
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
            $table->dropIndex(['source']);
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
        foreach (['1-2', '1–2', '1/2', '1 dan 2'] as $format) {
            $this->assertTrue($resolver->isOpeningLesson($format));
        }
        $this->assertFalse($resolver->isOpeningLesson('3-4'));
    }

    public function test_teacher_can_create_and_update_journal_with_topic_longer_than_255_characters(): void
    {
        [$user, $payload] = $this->journalContext();
        $payload['topic'] = str_repeat('Materi pembelajaran ', 30);

        $response = $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload);

        $journal = TeachingJournal::sole();
        $response->assertRedirect(route('academic.teaching-journals.show', $journal));
        $this->assertSame(trim($payload['topic']), $journal->topic);

        $payload['topic'] = str_repeat('Uraian pembelajaran lanjutan ', 20);
        $this->actingAs($user)
            ->put(route('academic.teaching-journals.update', $journal), $payload)
            ->assertRedirect(route('academic.teaching-journals.show', $journal));
        $this->assertSame(trim($payload['topic']), $journal->refresh()->topic);
    }

    public function test_journal_validation_uses_indonesian_messages_and_humanized_attributes(): void
    {
        app()->setLocale('id');
        [$user, $payload] = $this->journalContext();
        $payload['topic'] = str_repeat('a', 5001);
        $payload['learning_method'] = str_repeat('a', 256);

        $response = $this->from(route('academic.teaching-journals.create'))
            ->actingAs($user)
            ->post(route('academic.teaching-journals.store'), $payload);

        $response->assertRedirect(route('academic.teaching-journals.create'))
            ->assertSessionHasErrors(['topic', 'learning_method']);
        $errors = session('errors')->all();
        $this->assertStringContainsString('uraian mengajar tidak boleh lebih dari 5.000 karakter', mb_strtolower(implode(' ', $errors)));
        $this->assertStringContainsString('metode pembelajaran tidak boleh lebih dari 255 karakter', mb_strtolower(implode(' ', $errors)));
        $this->assertStringNotContainsString('validation.', implode(' ', $errors));
    }

    public function test_create_without_classroom_uses_first_accessible_room_and_loads_its_daily_attendance(): void
    {
        [$user, $payload] = $this->journalContext();
        $studentId = $payload['attendances'][0]['student_id'];
        StudentAttendance::create(['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $payload['classroom_id'], 'student_id' => $studentId, 'attendance_date' => today(), 'status' => 'present', 'source' => 'manual', 'recorded_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('academic.teaching-journals.create'));

        $response->assertOk()
            ->assertSee('value="'.$payload['classroom_id'].'" selected', false)
            ->assertSee('Absensi harian manual')
            ->assertSee('Hadir');
        $this->assertSame($payload['classroom_id'], $response->viewData('journal')->classroom_id);
    }

    public function test_create_uses_explicit_classroom_and_date_without_leaking_previous_context(): void
    {
        [$user, $payload] = $this->journalContext();
        $gradeId = Classroom::findOrFail($payload['classroom_id'])->grade_level_id;
        $otherRoom = Classroom::create(['academic_year_id' => $payload['academic_year_id'], 'grade_level_id' => $gradeId, 'code' => 'B', 'is_active' => true]);
        $otherStudent = Student::create(['id' => 204, 'full_name' => 'Siswa Konteks Baru', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['student_id' => $otherStudent->id, 'classroom_id' => $otherRoom->id, 'academic_year_id' => $payload['academic_year_id'], 'status' => 'active', 'joined_at' => '2026-07-01']);
        StudentAttendance::create(['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $otherRoom->id, 'student_id' => $otherStudent->id, 'attendance_date' => '2026-10-15', 'status' => 'present', 'source' => 'manual', 'recorded_by' => $user->id]);
        StudentAttendance::create(['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $payload['classroom_id'], 'student_id' => $payload['attendances'][0]['student_id'], 'attendance_date' => '2026-10-14', 'status' => 'absent', 'source' => 'manual', 'recorded_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('academic.teaching-journals.create', ['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $otherRoom->id, 'journal_date' => '2026-10-15', 'lesson_number' => '3-4']));

        $response->assertOk()->assertSee('Siswa Konteks Baru')->assertSee('Absensi harian manual')->assertSee('Hadir');
        $this->assertSame($otherRoom->id, $response->viewData('journal')->classroom_id);
        $this->assertTrue($response->viewData('dailyAttendance')->has($otherStudent->id));
        $this->assertFalse($response->viewData('dailyAttendance')->has($payload['attendances'][0]['student_id']));
    }

    public function test_create_rejects_explicit_classroom_that_teacher_cannot_access(): void
    {
        [$user, $payload] = $this->journalContext();
        $user->assignRole(Role::findOrCreate('guru'));
        $user->revokePermissionTo('teaching-journals.view-all');

        $this->actingAs($user)->get(route('academic.teaching-journals.create', ['academic_year_id' => $payload['academic_year_id'], 'classroom_id' => $payload['classroom_id']]))
            ->assertSessionHasErrors('classroom_id');
    }

    public function test_create_form_automatically_reloads_changed_attendance_context(): void
    {
        $view = file_get_contents(resource_path('views/academic/teaching-journals/form.blade.php'));

        $this->assertStringContainsString("'[name=\"academic_year_id\"],[name=\"semester_id\"],[name=\"journal_date\"],[name=\"classroom_id\"]'", $view);
        $this->assertStringContainsString('field.addEventListener(\'change\', reloadContext)', $view);
        $this->assertStringContainsString('clearTimeout(contextReloadTimer)', $view);
        $this->assertStringContainsString('version !== contextVersion', $view);
        $this->assertStringNotContainsString('Muat Data Absensi Sesuai Konteks', $view);
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
        $this->assertSame(['pending', 'pending', 'pending'], $following->attendances()->orderBy('student_id')->toBase()->pluck('status')->all());

        $service->save($payload, $user);
        $later = $service->save([...$payload, 'lesson_number' => '5–6', 'topic' => 'Jurnal terakhir', 'attendances' => null], $user);

        $this->assertSame($statuses, StudentAttendance::orderBy('student_id')->toBase()->pluck('status')->all());
        $this->assertSame($statuses, $following->attendances()->orderBy('student_id')->toBase()->pluck('status')->all());
        $this->assertSame($statuses, $later->attendances()->orderBy('student_id')->toBase()->pluck('status')->all());
        $this->assertSame(['manual'], StudentAttendance::distinct()->toBase()->pluck('source')->all());
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
        $role = Role::findOrCreate('guru');
        $role->givePermissionTo([Permission::findOrCreate('teaching-journals.view'), Permission::findOrCreate('teaching-journals.manage')]);
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

    private function assignSchedule(User $user, array $payload, array $overrides = []): LessonSchedule
    {
        return LessonSchedule::create(array_replace(collect($payload)->only(['academic_year_id', 'semester_id', 'classroom_id', 'academic_subject_id'])->all(), [
            'personnel_id' => Personnel::where('user_id', $user->id)->sole()->id,
            'day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '09:00', 'active' => true,
        ], $overrides));
    }

    public function test_btaq_teacher_can_select_active_rooms_and_save_without_a_timetable(): void
    {
        [$user, $payload] = $this->journalContext(false);
        $personnel = Personnel::where('user_id', $user->id)->sole();
        $btaq = AcademicSubject::create(['name' => 'BTAQ', 'code' => 'BTAQ', 'is_active' => true]);
        $room = Classroom::findOrFail($payload['classroom_id']);
        $other = Classroom::create(['academic_year_id' => $room->academic_year_id, 'grade_level_id' => $room->grade_level_id, 'code' => 'B', 'is_active' => true]);
        Classroom::create(['academic_year_id' => $room->academic_year_id, 'grade_level_id' => $room->grade_level_id, 'code' => 'C', 'is_active' => false]);

        foreach (['Guru BTAQ', ' guru   batq '] as $position) {
            $personnel->update(['position' => $position]);
            $response = $this->actingAs($user)->get(route('academic.teaching-journals.create'));
            $response->assertOk();
            $this->assertEqualsCanonicalizing([$room->id, $other->id], $response->viewData('rooms')->pluck('id')->all());
            $this->assertSame([$btaq->id], $response->viewData('subjects')->pluck('id')->all());
        }

        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'academic_subject_id' => $btaq->id])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('teaching_journals', ['personnel_id' => $personnel->id, 'academic_subject_id' => $btaq->id]);
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload)->assertSessionHasErrors('academic_subject_id');
        $this->assertSame(0, app(AcademicAccessService::class)->classrooms($user->assignRole(Role::findOrCreate('guru')))->count());
    }

    public function test_btaq_access_excludes_inactive_subjects_and_rooms_from_other_years(): void
    {
        [$user, $payload] = $this->journalContext(false);
        Personnel::where('user_id', $user->id)->update(['position' => 'Guru BTAQ']);
        $btaq = AcademicSubject::create(['name' => 'Baca Tulis Al-Quran', 'code' => 'BATQ', 'is_active' => true]);
        $inactive = AcademicSubject::create(['name' => 'BTAQ', 'is_active' => false]);
        $year = AcademicYear::create(['name' => '2025/2026', 'starts_at' => '2025-07-01', 'ends_at' => '2026-06-30', 'is_active' => false]);
        $room = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => Classroom::findOrFail($payload['classroom_id'])->grade_level_id, 'code' => 'A', 'is_active' => true]);
        $response = $this->actingAs($user)->get(route('academic.teaching-journals.create'));
        $response->assertOk();
        $this->assertFalse($response->viewData('rooms')->contains('id', $room->id));
        $this->assertSame([$btaq->id], $response->viewData('subjects')->pluck('id')->all());
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'academic_subject_id' => $inactive->id])->assertSessionHasErrors('classroom_id');
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'classroom_id' => $room->id, 'academic_subject_id' => $btaq->id])->assertSessionHasErrors('classroom_id');
    }

    public function test_subject_teacher_has_all_assigned_rooms_and_only_assigned_subjects(): void
    {
        [$user, $payload] = $this->journalContext(false);
        $this->assignSchedule($user, $payload);
        $room = Classroom::findOrFail($payload['classroom_id']);
        $other = Classroom::create(['academic_year_id' => $room->academic_year_id, 'grade_level_id' => $room->grade_level_id, 'code' => 'B', 'is_active' => true]);
        $this->assignSchedule($user, $payload, ['classroom_id' => $other->id]);
        $unassigned = AcademicSubject::create(['name' => 'Mapel bukan tugas', 'is_active' => true]);
        $response = $this->actingAs($user)->get(route('academic.teaching-journals.create'));
        $response->assertOk();
        $this->assertEqualsCanonicalizing([$room->id, $other->id], $response->viewData('rooms')->pluck('id')->all());
        $this->assertSame([$payload['academic_subject_id']], $response->viewData('subjects')->pluck('id')->all());
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'academic_subject_id' => $unassigned->id])->assertSessionHasErrors('academic_subject_id');
        // Akses global absensi/nilai tetap hanya rombel wali kelas.
        $this->assertSame(0, app(AcademicAccessService::class)->classrooms($user->assignRole(Role::findOrCreate('guru')))->count());
    }

    public function test_subject_teacher_creates_updates_own_journal_and_cannot_take_over_another(): void
    {
        [$user, $payload] = $this->journalContext(false);
        $this->assignSchedule($user, $payload);
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'personnel_id' => 99999])->assertRedirect();
        $journal = TeachingJournal::sole();
        $this->assertSame(Personnel::where('user_id', $user->id)->sole()->id, $journal->personnel_id);
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), [...$payload, 'topic' => 'Diperbarui'])->assertRedirect();
        $this->assertSame('Diperbarui', $journal->refresh()->topic);
        $other = User::factory()->create(['must_change_password' => false]);
        $other->givePermissionTo(['teaching-journals.view', 'teaching-journals.manage']);
        Personnel::create(['user_id' => $other->id, 'full_name' => 'Guru Lain', 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'is_active' => true]);
        $this->actingAs($other)->get(route('academic.teaching-journals.edit', $journal))->assertForbidden();
        $this->actingAs($other)->put(route('academic.teaching-journals.update', $journal), $payload)->assertForbidden();
        $this->actingAs($other)->delete(route('academic.teaching-journals.destroy', $journal))->assertForbidden();
        $this->actingAs($other)->get(route('academic.teaching-journals.show', $journal))->assertForbidden();
        $this->actingAs($other)->get(route('academic.teaching-journals.report', ['format' => 'pdf', 'journal_date' => $payload['journal_date']]))->assertRedirect();
        $this->assertDatabaseCount('teaching_journals', 1);
    }

    public function test_inactive_assignment_blocks_new_context_but_preserves_historical_owner_access(): void
    {
        [$user, $payload] = $this->journalContext(false);
        $schedule = $this->assignSchedule($user, $payload);
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $schedule->update(['active' => false]);
        $this->actingAs($user)->get(route('academic.teaching-journals.show', $journal))->assertOk();
        $this->actingAs($user)->get(route('academic.teaching-journals.edit', $journal))->assertOk();
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), [...$payload, 'topic' => 'Koreksi jurnal lama'])->assertRedirect();
        $this->assertSame('Koreksi jurnal lama', $journal->refresh()->topic);
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload)->assertSessionHasErrors('classroom_id');
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), [...$payload, 'journal_date' => '2026-10-08'])->assertSessionHasErrors('classroom_id');
    }

    public function test_teacher_without_active_personnel_is_denied_clearly(): void
    {
        [$user, $payload] = $this->journalContext(false);
        Personnel::where('user_id', $user->id)->update(['is_active' => false]);
        $this->actingAs($user)->get(route('academic.teaching-journals.create'))->assertForbidden()->assertSee(TeachingJournalService::NO_PERSONNEL);
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload)->assertForbidden();
    }

    public function test_homeroom_legacy_rights_remain_and_delete_requires_manage_permission(): void
    {
        [$user, $payload] = $this->journalContext(false);
        Classroom::whereKey($payload['classroom_id'])->update(['homeroom_personnel_id' => Personnel::where('user_id', $user->id)->sole()->id]);
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $user->revokePermissionTo('teaching-journals.manage');
        $this->actingAs($user)->delete(route('academic.teaching-journals.destroy', $journal))->assertForbidden();
        $this->assertDatabaseCount('teaching_journals', 1);
    }

    public function test_different_subject_teachers_share_daily_snapshots_without_changing_rfid(): void
    {
        [$user, $payload] = $this->journalContext(false);
        $this->assignSchedule($user, $payload);
        $other = User::factory()->create(['must_change_password' => false]);
        $other->givePermissionTo(['teaching-journals.view', 'teaching-journals.manage']);
        Personnel::create(['user_id' => $other->id, 'full_name' => 'Guru Berikutnya', 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'is_active' => true]);
        $this->assignSchedule($other, $payload, ['start_time' => '09:00', 'end_time' => '10:00']);
        StudentAttendance::create(['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $payload['classroom_id'], 'student_id' => $payload['attendances'][0]['student_id'], 'attendance_date' => $payload['journal_date'], 'status' => 'present', 'source' => 'rfid']);
        $service = app(TeachingJournalService::class);
        $first = $service->save([...$payload, 'attendances' => [['student_id' => $payload['attendances'][0]['student_id'], 'status' => 'absent']]], $user);
        $next = $service->save([...$payload, 'lesson_number' => '3-4', 'attendances' => null], $other);
        $this->assertSame('present', $first->attendances()->sole()->status->value);
        $this->assertSame('present', $next->attendances()->sole()->status->value);
        $this->assertSame('rfid', StudentAttendance::sole()->source->value);
    }

    public function test_period_validation_and_schedule_management_follow_permissions(): void
    {
        [$user, $payload] = $this->journalContext(false);
        $this->assignSchedule($user, $payload);
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'journal_date' => '2027-02-01'])->assertSessionHasErrors('journal_date');
        $this->actingAs($user)->get(route('academic.lesson-schedules.index'))->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('teaching-journals.view-all'));
        $this->actingAs($user)->get(route('academic.lesson-schedules.index'))->assertOk();
        $data = collect($payload)->only(['academic_year_id', 'semester_id', 'classroom_id', 'academic_subject_id'])->all() + ['personnel_id' => Personnel::where('user_id', $user->id)->sole()->id, 'day_of_week' => 4, 'start_time' => '10:00', 'end_time' => '11:00'];
        $this->actingAs($user)->post(route('academic.lesson-schedules.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $schedule = LessonSchedule::latest('id')->firstOrFail();
        $this->actingAs($user)->patch(route('academic.lesson-schedules.deactivate', $schedule))->assertRedirect();
        $this->assertFalse($schedule->refresh()->active);
    }

    public function test_subject_teacher_switches_context_without_previous_roster_or_attendance(): void
    {
        [$user, $payload] = $this->journalContext(false);
        $this->assignSchedule($user, $payload);
        $room = Classroom::findOrFail($payload['classroom_id']);
        $other = Classroom::create(['academic_year_id' => $room->academic_year_id, 'grade_level_id' => $room->grade_level_id, 'code' => 'B', 'is_active' => true]);
        $this->assignSchedule($user, $payload, ['classroom_id' => $other->id]);
        $student = Student::create(['full_name' => 'Siswa Rombel Penugasan Baru', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $other->id, 'academic_year_id' => $payload['academic_year_id'], 'status' => 'active', 'joined_at' => '2026-07-01']);
        StudentAttendance::create(['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $other->id, 'student_id' => $student->id, 'attendance_date' => $payload['journal_date'], 'status' => 'sick', 'source' => 'manual']);
        $journal = app(TeachingJournalService::class)->save([...$payload, 'lesson_number' => '3-4', 'attendances' => null], $user);
        $query = ['academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'classroom_id' => $other->id, 'journal_date' => $payload['journal_date'], 'lesson_number' => '3-4'];
        foreach (['academic.teaching-journals.create', 'academic.teaching-journals.edit'] as $route) {
            $response = $this->actingAs($user)->withHeaders(['X-Journal-Context' => '1'])->get(route($route, ($route === 'academic.teaching-journals.edit' ? ['teaching_journal' => $journal] : []) + $query));
            $response->assertOk()->assertSee($student->full_name)->assertDontSee('Siswa Jurnal');
            $this->assertSame('sick', $response->viewData('dailyAttendance')->get($student->id)->status->value);
            $this->assertTrue($response->viewData('snapshotAttendance')->isEmpty());
        }
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'classroom_id' => $other->id])->assertSessionHasErrors('attendances');
    }

    public function test_admin_without_personnel_can_manage_and_cannot_bypass_base_permission(): void
    {
        [$user, $payload] = $this->journalContext();
        $owner = Personnel::where('user_id', $user->id)->sole();
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo(['teaching-journals.view', 'teaching-journals.view-all', 'teaching-journals.manage']);
        $this->actingAs($admin)->get(route('academic.teaching-journals.create'))->assertOk();
        $this->actingAs($admin)->post(route('academic.teaching-journals.store'), [...$payload, 'personnel_id' => $owner->id])->assertSessionHasNoErrors()->assertRedirect();
        $journal = TeachingJournal::sole();
        $this->actingAs($admin)->get(route('academic.teaching-journals.edit', $journal))->assertOk();
        $this->actingAs($admin)->put(route('academic.teaching-journals.update', $journal), [...$payload, 'topic' => 'Koreksi operator'])->assertSessionHasNoErrors()->assertRedirect();
        $admin->revokePermissionTo('teaching-journals.manage');
        $this->actingAs($admin)->delete(route('academic.teaching-journals.destroy', $journal))->assertForbidden();
        $admin->givePermissionTo('teaching-journals.manage');
        $this->actingAs($admin)->delete(route('academic.teaching-journals.destroy', $journal))->assertRedirect();
        $this->assertDatabaseCount('teaching_journals', 0);
    }

    public function test_simplified_form_saves_without_removed_fields_and_preserves_historical_content(): void
    {
        [$user, $payload] = $this->journalContext();
        $response = $this->actingAs($user)->get(route('academic.teaching-journals.create'));
        $response->assertOk()->assertDontSee('name="topic"', false)
            ->assertDontSee('name="learning_objectives"', false)
            ->assertDontSee('name="learning_activity"', false);
        $simplified = collect($payload)->except(['topic', 'learning_objectives', 'learning_activity'])->all();
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $simplified)
            ->assertSessionHasNoErrors()->assertRedirect();
        $journal = TeachingJournal::sole();
        $this->assertSame('', $journal->topic);
        $journal->update(['topic' => 'Uraian lama', 'learning_objectives' => 'Tujuan lama', 'learning_activity' => 'Kegiatan lama']);
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), $simplified)
            ->assertSessionHasNoErrors()->assertRedirect();
        $journal->refresh();
        $this->assertSame('Uraian lama', $journal->topic);
        $this->assertSame('Tujuan lama', $journal->learning_objectives);
        $this->assertSame('Kegiatan lama', $journal->learning_activity);
    }

    public function test_assignment_upload_is_private_downloadable_by_owner_and_survives_updates(): void
    {
        Storage::fake('local');
        [$user, $payload] = $this->journalContext(false);
        $this->assignSchedule($user, $payload);
        $file = UploadedFile::fake()->create('Tugas Matematika.pdf', 20, 'application/pdf');
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'assignment_files' => [$file]])
            ->assertSessionHasNoErrors()->assertRedirect();
        $journal = TeachingJournal::sole();
        $attachment = $journal->attachments()->sole();
        Storage::disk('local')->assertExists($attachment->path);
        $url = route('academic.teaching-journals.attachments.download', [$journal, $attachment]);
        $this->actingAs($user)->get($url)->assertOk()->assertDownload('Tugas Matematika.pdf');
        $this->actingAs($user)->get(route('academic.teaching-journals.show', $journal))->assertOk()->assertSee('Tugas Matematika.pdf');
        $this->actingAs($user)->get(route('academic.teaching-journals.edit', $journal))->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('Tugas Matematika.pdf');
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $journal->attachments()->count());
        $other = User::factory()->create(['must_change_password' => false]);
        $other->givePermissionTo('teaching-journals.view');
        Personnel::create(['user_id' => $other->id, 'full_name' => 'Guru Lampiran Lain', 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'is_active' => true]);
        $this->actingAs($other)->get($url)->assertForbidden();
        $other->givePermissionTo(Permission::findOrCreate('teaching-journals.view-all'));
        $this->actingAs($other)->get($url)->assertOk();
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), [...$payload, 'remove_assignment_attachments' => [$attachment->id]])->assertSessionHasNoErrors();
        $this->assertSame(0, $journal->attachments()->count());
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_assignment_upload_rejects_unsafe_or_oversized_files_without_saving_journal(): void
    {
        Storage::fake('local');
        [$user, $payload] = $this->journalContext();
        foreach ([UploadedFile::fake()->create('script.php', 1, 'application/x-php'), UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf')] as $file) {
            $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'assignment_files' => [$file]])->assertSessionHasErrors('assignment_files.0');
        }
        $this->assertDatabaseCount('teaching_journals', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_assignment_drive_link_is_validated_saved_and_not_duplicated(): void
    {
        [$user, $payload] = $this->journalContext();
        foreach (['https://example.com/file.pdf', 'javascript:alert(1)', 'https://drive.google.com.evil.test/file/d/123/view', 'https://drive.google.com/drive/folders/123'] as $url) {
            $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'assignment_drive_url' => $url])->assertSessionHasErrors('assignment_drive_url');
        }
        $this->assertDatabaseCount('teaching_journals', 0);
        $url = 'https://drive.google.com/file/d/abc_123/view?usp=sharing';
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload, 'assignment_drive_url' => $url])->assertSessionHasNoErrors()->assertRedirect();
        $journal = TeachingJournal::sole();
        $this->assertSame($url, $journal->attachments()->sole()->drive_url);
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), [...$payload, 'assignment_drive_url' => $url])->assertSessionHasNoErrors();
        $this->assertSame(1, $journal->attachments()->count());
        $this->actingAs($user)->get(route('academic.teaching-journals.show', $journal))->assertOk()->assertSee('File tugas Google Drive');
    }

    public function test_attachment_removal_is_scoped_and_failed_save_leaves_no_files_or_journal(): void
    {
        Storage::fake('local');
        [$user, $payload] = $this->journalContext();
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), [...$payload,
            'assignment_files' => [UploadedFile::fake()->create('tugas.pdf', 10, 'application/pdf')],
            'remove_assignment_attachments' => [999999],
        ])->assertSessionHasErrors('remove_assignment_attachments');
        $this->assertDatabaseCount('teaching_journals', 0);
        $this->assertDatabaseCount('student_attendances', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_assignment_files_are_cleaned_up_when_save_fails_after_upload(): void
    {
        Storage::fake('local');
        [$user, $payload] = $this->journalContext();
        $resolver = \Mockery::mock(JournalAttendanceResolver::class)->makePartial();
        $resolver->shouldReceive('writeSnapshots')->once()->andThrow(new \RuntimeException('Penyimpanan snapshot gagal.'));
        $this->app->instance(JournalAttendanceResolver::class, $resolver);
        try {
            app(TeachingJournalService::class)->save([...$payload, 'assignment_files' => [UploadedFile::fake()->create('tugas.pdf', 10, 'application/pdf')]], $user);
            $this->fail('Penyimpanan seharusnya gagal.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Penyimpanan snapshot gagal.', $exception->getMessage());
        }
        $this->assertDatabaseCount('teaching_journals', 0);
        $this->assertDatabaseCount('teaching_journal_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_attachments_cannot_be_removed_or_downloaded_through_another_journal(): void
    {
        Storage::fake('local');
        [$user, $payload] = $this->journalContext();
        $service = app(TeachingJournalService::class);
        $first = $service->save($payload, $user);
        $second = $service->save([...$payload, 'lesson_number' => '3-4', 'attendances' => null, 'assignment_files' => [UploadedFile::fake()->create('tugas.pdf', 10, 'application/pdf')]], $user);
        $attachment = $second->attachments()->sole();
        $this->actingAs($user)->get(route('academic.teaching-journals.attachments.download', [$first, $attachment]))->assertNotFound();
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $first), [...$payload, 'remove_assignment_attachments' => [$attachment->id]])->assertSessionHasErrors('remove_assignment_attachments');
        $this->assertSame(1, $second->attachments()->count());
        Storage::disk('local')->assertExists($attachment->path);
        $this->actingAs($user)->delete(route('academic.teaching-journals.destroy', $second))->assertRedirect();
        $this->assertDatabaseCount('teaching_journal_attachments', 0);
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_assignment_attachment_limit_counts_saved_and_new_files_together(): void
    {
        Storage::fake('local');
        [$user, $payload] = $this->journalContext();
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        for ($i = 0; $i < 10; $i++) {
            $journal->attachments()->create(['original_name' => 'File Drive '.$i, 'drive_url' => 'https://drive.google.com/file/d/file_'.$i.'/view']);
        }
        $this->actingAs($user)->put(route('academic.teaching-journals.update', $journal), [...$payload, 'assignment_files' => [UploadedFile::fake()->create('tambahan.pdf', 10, 'application/pdf')]])->assertSessionHasErrors('assignment_files');
        $this->assertSame(10, $journal->attachments()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_google_drive_picker_is_disabled_until_operator_configures_google_cloud(): void
    {
        [$user] = $this->journalContext();
        $this->actingAs($user)->get(route('academic.teaching-journals.create'))->assertOk()
            ->assertSee('Pilih dari Google Drive')->assertSee('Pemilih Google Drive belum diaktifkan');
        $settings = app(ApplicationSettingService::class);
        $settings->update($user, [
            'google_drive_picker_enabled' => true,
            'google_drive_client_id' => 'test-client.apps.googleusercontent.com',
            'google_drive_api_key' => 'test_browser_key',
            'google_drive_app_id' => '1234567890',
        ]);
        $response = $this->actingAs($user)->get(route('academic.teaching-journals.create'));
        $response->assertOk()->assertSee('data-client-id="test-client.apps.googleusercontent.com"', false)
            ->assertSee('js/journal-drive-picker.js')->assertDontSee('Pemilih Google Drive belum diaktifkan');
        $this->assertTrue($response->viewData('drivePicker')['ready']);
    }

    private function journalContext(bool $admin = true): array
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            Permission::findOrCreate('teaching-journals.view'),
            Permission::findOrCreate('teaching-journals.manage'),
        ]);
        if ($admin) {
            $user->givePermissionTo(Permission::findOrCreate('teaching-journals.view-all'));
        }
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
