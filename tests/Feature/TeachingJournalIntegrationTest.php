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
use App\Services\Academic\JournalAttendanceResolver;
use App\Services\Academic\TeachingJournalReportService;
use App\Services\Academic\TeachingJournalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
        $payload['topic'] = trim(str_repeat('Materi pembelajaran ', 30));

        $response = $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload);

        $journal = TeachingJournal::sole();
        $response->assertRedirect(route('academic.teaching-journals.show', $journal));
        $this->assertSame($payload['topic'], $journal->topic);

        $payload['topic'] = trim(str_repeat('Uraian pembelajaran lanjutan ', 20));
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

    private function daily(array $payload, string $status = 'present', string $source = 'rfid', string $time = '06:45:00'): StudentAttendance
    {
        return StudentAttendance::create([
            'student_id' => $payload['attendances'][0]['student_id'], 'classroom_id' => $payload['classroom_id'],
            'academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'],
            'attendance_date' => $payload['journal_date'], 'status' => $status, 'source' => $source,
            'scanned_at' => $source === 'rfid' ? $payload['journal_date'].' '.$time : null,
        ]);
    }

    public function test_three_lessons_share_daily_rfid_without_mutating_daily_record(): void
    {
        [$user, $payload] = $this->journalContext();
        $daily = $this->daily($payload);
        $before = $daily->refresh()->getAttributes();
        foreach (['1–2', '3–4', '5–6'] as $lesson) {
            $payload['lesson_number'] = $lesson;
            $journal = app(TeachingJournalService::class)->save($payload, $user);
            $this->assertSame('present', $journal->attendances()->sole()->status->value);
        }
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertSame($before, $daily->refresh()->getAttributes());
    }

    public function test_missing_daily_attendance_stays_pending_even_with_forged_present_input(): void
    {
        [$user, $payload] = $this->journalContext();
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $this->assertSame('pending', $journal->attendances()->sole()->status->value);
        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_daily_sick_and_permitted_are_inherited(): void
    {
        [$user, $payload] = $this->journalContext();
        $daily = $this->daily($payload, 'sick', 'manual');
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $this->assertSame('sick', $journal->attendances()->sole()->status->value);
        $daily->update(['status' => 'permitted']);
        app(JournalAttendanceResolver::class)->synchronize($daily);
        $this->assertSame('permitted', $journal->attendances()->sole()->status->value);
    }

    public function test_manual_lesson_exception_survives_edit_and_daily_sync_without_affecting_other_lessons(): void
    {
        [$user, $payload] = $this->journalContext();
        $first = app(TeachingJournalService::class)->save($payload, $user);
        $second = app(TeachingJournalService::class)->save($payload, $user);
        $payload['attendances'][0] += ['mode' => 'manual'];
        $payload['attendances'][0]['status'] = 'permitted';
        $payload['attendances'][0]['notes'] = 'Pulang lebih awal.';
        app(TeachingJournalService::class)->save($payload, $user, $first);
        $detail = $first->attendances()->sole();
        $this->assertSame($user->id, $detail->corrected_by);
        $daily = $this->daily($payload);
        app(JournalAttendanceResolver::class)->synchronize($daily);
        $this->assertSame('permitted', $detail->refresh()->status->value);
        $this->assertSame('present', $second->attendances()->sole()->status->value);
        unset($payload['attendances'][0]['mode']);
        app(TeachingJournalService::class)->save($payload, $user, $first);
        $this->assertSame($detail->id, $first->attendances()->sole()->id);
        $this->assertSame('permitted', $detail->refresh()->status->value);
        $this->assertSame('present', $daily->refresh()->status->value);
    }

    public function test_arrival_after_lesson_start_does_not_mark_missed_lesson_present(): void
    {
        [$user, $payload] = $this->journalContext();
        $this->daily($payload, 'present', 'rfid', '09:30:00');
        $schedule = LessonSchedule::create([
            'academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'],
            'classroom_id' => $payload['classroom_id'], 'academic_subject_id' => $payload['academic_subject_id'],
            'day_of_week' => 3, 'start_time' => '07:00:00', 'end_time' => '08:00:00', 'active' => true,
        ]);
        $first = app(TeachingJournalService::class)->save($payload, $user);
        $this->assertSame('pending', $first->attendances()->sole()->status->value);
        $schedule->update(['start_time' => '10:00:00', 'end_time' => '11:00:00']);
        $next = app(TeachingJournalService::class)->save($payload, $user);
        $this->assertSame('present', $next->attendances()->sole()->status->value);
    }

    public function test_roster_endpoint_uses_date_and_room_filters(): void
    {
        [$user, $payload] = $this->journalContext();
        $this->daily($payload);
        $url = route('academic.teaching-journals.attendance');
        $this->actingAs($user)->getJson($url.'?'.http_build_query($payload))->assertOk()->assertJsonPath('rows.0.status', 'present');
        $payload['journal_date'] = '2026-10-08';
        $this->getJson($url.'?'.http_build_query($payload))->assertOk()->assertJsonPath('rows.0.status', 'pending');
        $other = Classroom::create(['academic_year_id' => $payload['academic_year_id'], 'grade_level_id' => Classroom::find($payload['classroom_id'])->grade_level_id, 'code' => 'B', 'is_active' => true]);
        $payload['classroom_id'] = $other->id;
        $this->getJson($url.'?'.http_build_query($payload))->assertOk()->assertJsonCount(0, 'rows');
    }

    public function test_teacher_cannot_edit_another_teachers_journal_but_view_all_can(): void
    {
        [$user, $payload] = $this->journalContext();
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $other = User::factory()->create(['must_change_password' => false]);
        $other->givePermissionTo(['teaching-journals.view', 'teaching-journals.manage']);
        $this->actingAs($other)->get(route('academic.teaching-journals.edit', $journal))->assertForbidden();
        $other->givePermissionTo(Permission::findOrCreate('teaching-journals.view-all'));
        $this->get(route('academic.teaching-journals.edit', $journal))->assertOk();
    }

    public function test_manual_exception_requires_reason_and_outside_period_date_is_rejected(): void
    {
        [$user, $payload] = $this->journalContext();
        $payload['attendances'][0]['mode'] = 'manual';
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload)->assertSessionHasErrors('attendances.0.notes');
        $payload['journal_date'] = '2027-07-01';
        $this->post(route('academic.teaching-journals.store'), $payload)->assertSessionHasErrors('journal_date');
        $this->assertDatabaseCount('teaching_journals', 0);
    }

    public function test_foreign_student_and_duplicate_students_are_rejected(): void
    {
        [$user, $payload] = $this->journalContext();
        $foreign = Student::create(['full_name' => 'Siswa Rombel Lain', 'gender' => 'male', 'status' => 'active']);
        $payload['attendances'][0]['student_id'] = $foreign->id;
        $this->actingAs($user)->post(route('academic.teaching-journals.store'), $payload)->assertSessionHasErrors('attendances');
        $payload['attendances'][] = $payload['attendances'][0];
        $this->post(route('academic.teaching-journals.store'), $payload)->assertSessionHasErrors('attendances.0.student_id');
    }

    public function test_pending_is_separate_from_confirmed_absence_in_html_pdf_and_docx(): void
    {
        [$user, $payload] = $this->journalContext();
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        foreach (['present', 'sick', 'permitted', 'absent'] as $status) {
            $student = Student::create(['full_name' => 'Siswa '.$status, 'gender' => 'male', 'status' => 'active']);
            $journal->attendances()->create(['student_id' => $student->id, 'status' => $status, 'origin' => 'legacy']);
        }
        $journal->load(['attendances', 'academicYear', 'semester', 'classroom.gradeLevel', 'subject', 'personnel']);
        $html = view('academic.teaching-journals.report', ['journals' => collect([$journal])])->render();
        $this->assertStringContainsString('<td>1</td><td>3</td><td>1</td><td>1</td><td>1</td>', $html);
        $this->assertStringContainsString('Belum Tercatat: 1', $html);
        $this->actingAs($user)->get(route('academic.teaching-journals.report', ['format' => 'pdf', 'academic_year_id' => $payload['academic_year_id'], 'semester_id' => $payload['semester_id'], 'journal_date' => $payload['journal_date']]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $source = tempnam(sys_get_temp_dir(), 'journal-template-');
        $target = null;
        try {
            $zip = new \ZipArchive;
            $zip->open($source, \ZipArchive::OVERWRITE);
            $zip->addFromString('word/document.xml', '<w:document><w:tbl><w:tr><w:tc>${no}|${hadir}|${tidak_hadir}|${sakit}|${izin}|${alpa}|${keterangan}</w:tc></w:tr></w:tbl></w:document>');
            $zip->close();
            $target = app(TeachingJournalReportService::class)->createDocx(new TeachingJournalTemplate, collect([$journal]), $source);
            $zip->open($target);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertStringContainsString('1|1|3|1|1|1|', $xml);
            $this->assertStringContainsString('Belum Tercatat: 1', $xml);
        } finally {
            unlink($source);
            if ($target) {
                unlink($target);
            }
        }
    }

    public function test_legacy_snapshot_is_preserved_when_journal_is_edited_or_daily_changes(): void
    {
        [$user, $payload] = $this->journalContext();
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $detail = $journal->attendances()->sole();
        $detail->update(['status' => 'sick', 'origin' => 'legacy', 'notes' => 'Riwayat lama']);
        $daily = $this->daily($payload);
        app(JournalAttendanceResolver::class)->synchronize($daily);
        app(TeachingJournalService::class)->save($payload, $user, $journal);
        $this->assertSame('sick', $detail->refresh()->status->value);
        $this->assertSame('Riwayat lama', $detail->notes);
    }

    public function test_editing_date_reloads_daily_status_without_carrying_an_exception(): void
    {
        [$user, $payload] = $this->journalContext();
        $this->daily($payload);
        $payload['attendances'][0] = array_merge($payload['attendances'][0], ['mode' => 'manual', 'status' => 'permitted', 'notes' => 'Izin pada tanggal lama.']);
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $payload['journal_date'] = '2026-10-08';
        $payload['attendances'][0]['mode'] = 'keep';
        app(TeachingJournalService::class)->save($payload, $user, $journal);
        $this->assertSame('pending', $journal->attendances()->sole()->status->value);
        $this->assertDatabaseHas('activity_log', ['description' => 'Mengubah konteks jurnal dan memuat ulang absensi harian']);
        $this->assertDatabaseCount('student_attendances', 1);
    }

    public function test_editing_classroom_archives_old_roster_and_uses_new_roster(): void
    {
        [$user, $payload] = $this->journalContext();
        $this->daily($payload);
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $oldDetail = $journal->attendances()->sole();
        $room = Classroom::create(['academic_year_id' => $payload['academic_year_id'], 'grade_level_id' => Classroom::find($payload['classroom_id'])->grade_level_id, 'code' => 'B', 'is_active' => true]);
        $student = Student::create(['full_name' => 'Siswa Baru', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['classroom_id' => $room->id, 'student_id' => $student->id, 'academic_year_id' => $payload['academic_year_id'], 'status' => 'active', 'joined_at' => '2026-07-01']);
        $payload['classroom_id'] = $room->id;
        $payload['attendances'] = [['student_id' => $student->id, 'status' => 'present', 'mode' => 'daily']];
        app(TeachingJournalService::class)->save($payload, $user, $journal);
        $newDetail = $journal->attendances()->sole();
        $this->assertSame($student->id, $newDetail->student_id);
        $this->assertSame('pending', $newDetail->status->value);
        $this->assertSoftDeleted('teaching_journal_attendances', ['id' => $oldDetail->id]);
        $this->assertSame('present', TeachingJournalAttendance::withTrashed()->find($oldDetail->id)->status->value);
    }

    public function test_teacher_can_explicitly_restore_daily_source_and_reset_is_audited(): void
    {
        [$user, $payload] = $this->journalContext();
        $this->daily($payload);
        $payload['attendances'][0] = array_merge($payload['attendances'][0], ['mode' => 'manual', 'status' => 'absent', 'notes' => 'Tidak mengikuti pelajaran.']);
        $journal = app(TeachingJournalService::class)->save($payload, $user);
        $payload['attendances'][0]['mode'] = 'daily';
        app(TeachingJournalService::class)->save($payload, $user, $journal);
        $this->assertSame('present', $journal->attendances()->sole()->status->value);
        $this->assertDatabaseHas('activity_log', ['description' => 'Mengembalikan absensi pelajaran ke sumber harian']);
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
