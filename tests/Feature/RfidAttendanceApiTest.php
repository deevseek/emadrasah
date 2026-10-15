<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\ApplicationSetting;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\GradeLevel;
use App\Models\Personnel;
use App\Models\RfidAttendanceEvent;
use App\Models\RfidDevice;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentRfidCard;
use App\Models\User;
use App\Services\Academic\RfidAttendanceService;
use App\Services\Academic\TeachingJournalService;
use App\Services\Settings\ApplicationSettingService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

class RfidAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = [
        'card_token' => '7142C511E9A163679A43E2259A723517',
        'uid' => 'E797BC64',
    ];

    public function test_post_is_recognized_and_enters_rfid_authentication_middleware(): void
    {
        $this->postJson('/api/rfid/attendance', self::PAYLOAD)
            ->assertStatus(401)
            ->assertJsonPath('code', 'DEVICE_UNAUTHORIZED')
            ->assertHeaderMissing('Location');
    }

    public function test_authenticated_json_post_reaches_the_form_request_and_service(): void
    {
        $token = 'token-reader-test-yang-tidak-rahasia';
        $device = RfidDevice::create([
            'device_id' => 'reader-test-01',
            'name' => 'Reader Test',
            'device_type' => 'reader',
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->mock(RfidAttendanceService::class, function (MockInterface $mock) use ($device): void {
            $mock->shouldReceive('record')
                ->once()
                ->with(self::PAYLOAD['card_token'], self::PAYLOAD['uid'], \Mockery::on(
                    fn (RfidDevice $authenticated): bool => $authenticated->is($device),
                ))
                ->andReturn(['http' => 201, 'success' => true, 'message' => 'Absensi berhasil']);
        });

        $this->withHeaders([
            'X-Device-Id' => $device->device_id,
            'X-Device-Token' => $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/rfid/attendance', self::PAYLOAD)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertHeaderMissing('Location');
    }

    public function test_get_remains_method_not_allowed_instead_of_a_false_not_found(): void
    {
        $this->getJson('/api/rfid/attendance')
            ->assertStatus(405)
            ->assertHeaderMissing('Location');
    }

    public function test_unregistered_card_creates_safe_failure_event(): void
    {
        ApplicationSetting::create(['key' => 'attendance_rfid_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance']);
        app(ApplicationSettingService::class)->clearCache();
        $token = 'token-reader-test';
        $device = RfidDevice::create(['device_id' => 'reader-failure', 'name' => 'Reader', 'device_type' => 'reader', 'token_hash' => hash('sha256', $token), 'is_active' => true]);

        $this->withHeaders(['X-Device-Id' => $device->device_id, 'X-Device-Token' => $token])
            ->postJson('/api/rfid/attendance', self::PAYLOAD)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CARD_NOT_REGISTERED')
            ->assertJsonMissingPath('card_token');

        $event = RfidAttendanceEvent::sole();
        $this->assertSame('CARD_NOT_REGISTERED', $event->result_code);
        $this->assertFalse($event->success);
        $this->assertNull($event->student_id);
        $this->assertStringNotContainsString(self::PAYLOAD['card_token'], $event->message);
    }

    public function test_invalid_payload_creates_safe_failure_event_without_credentials(): void
    {
        $token = 'token-reader-validation';
        $device = RfidDevice::create(['device_id' => 'reader-validation', 'name' => 'Reader', 'device_type' => 'reader', 'token_hash' => hash('sha256', $token), 'is_active' => true]);

        $this->withHeaders(['X-Device-Id' => $device->device_id, 'X-Device-Token' => $token])
            ->postJson('/api/rfid/attendance', ['card_token' => 'invalid'])
            ->assertUnprocessable()
            ->assertExactJson([
                'success' => false,
                'code' => 'CARD_NOT_PROVISIONED',
                'message' => 'Data kartu RFID tidak valid.',
                'event_id' => RfidAttendanceEvent::sole()->id,
            ]);

        $this->assertDatabaseHas('rfid_attendance_events', ['result_code' => 'CARD_NOT_PROVISIONED', 'success' => false]);
    }

    public function test_new_and_repeated_scan_each_create_a_distinct_live_event(): void
    {
        [$device, $card] = $this->makeAttendanceContext();
        $service = app(RfidAttendanceService::class);

        $created = $service->record($card->card_token, $card->uid, $device);
        $already = $service->record($card->card_token, $card->uid, $device);

        $this->assertSame('ATTENDANCE_CREATED', $created['code']);
        $this->assertSame('ALREADY_ATTENDED', $already['code']);
        $this->assertSame(200, $already['http']);
        $this->assertNotSame($created['event_id'], $already['event_id']);
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseHas('rfid_attendance_events', ['id' => $created['event_id'], 'result_code' => 'ATTENDANCE_CREATED']);
        $event = RfidAttendanceEvent::findOrFail($already['event_id']);
        $this->assertSame($card->student_id, $event->student_id);
        $this->assertSame('ALREADY_ATTENDED', $event->result_code);
        $this->assertStringNotContainsString($card->card_token, json_encode($event->toArray(), JSON_THROW_ON_ERROR));
        $this->assertSame('Siswa Uji', $already['student']['name']);
    }

    public function test_rfid_attendance_remains_present_and_is_used_by_teaching_journal(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 06:45:00', 'Asia/Jakarta'));
        [$device, $card, $semester] = $this->makeAttendanceContext();
        app(RfidAttendanceService::class)->record($card->card_token, $card->uid, $device);
        $membership = ClassroomMembership::where('student_id', $card->student_id)->firstOrFail();
        $user = User::factory()->create();
        Personnel::create(['user_id' => $user->id, 'full_name' => 'Guru Uji', 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'is_active' => true]);
        $subject = AcademicSubject::create(['name' => 'Pelajaran Uji', 'is_active' => true]);

        $journal = app(TeachingJournalService::class)->save([
            'academic_year_id' => $semester->academic_year_id,
            'semester_id' => $semester->id,
            'classroom_id' => $membership->classroom_id,
            'academic_subject_id' => $subject->id,
            'journal_date' => StudentAttendance::sole()->getRawOriginal('attendance_date'),
            'lesson_number' => '1-2',
            'topic' => 'Materi uji',
            'learning_method' => 'Diskusi',
            'attendances' => [['student_id' => $card->student_id, 'status' => 'sick', 'notes' => 'Tidak boleh mengganti RFID']],
        ], $user);

        $attendance = $journal->attendances()->sole();
        $daily = StudentAttendance::where('student_id', $card->student_id)->where('classroom_id', $membership->classroom_id)->whereDate('attendance_date', today())->sole();
        $this->assertSame('present', $attendance->status->value);
        $this->assertSame('present', $daily->status->value);
        $this->assertSame('rfid', $daily->source->value);
        $this->assertSame($device->id, $daily->rfid_device_id);
    }

    public function test_post_does_not_redirect_even_when_proxy_headers_are_present(): void
    {
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'mimuslimatnudemak.sch.id',
        ])->postJson('/api/rfid/attendance', self::PAYLOAD)
            ->assertStatus(401)
            ->assertJsonPath('code', 'DEVICE_UNAUTHORIZED')
            ->assertHeaderMissing('Location');
    }

    public function test_post_route_is_written_to_the_route_cache(): void
    {
        try {
            $this->assertSame(0, Artisan::call('route:cache'), Artisan::output());

            $cacheFiles = glob(base_path('bootstrap/cache/routes-*.php')) ?: [];
            $this->assertNotEmpty($cacheFiles);
            $cache = file_get_contents($cacheFiles[0]);

            $this->assertIsString($cache);
            $this->assertStringContainsString('api/rfid/attendance', $cache);
            $this->assertStringContainsString("'POST'", $cache);
        } finally {
            Artisan::call('route:clear');
        }
    }

    public function test_request_id_retry_returns_original_business_response_and_event(): void
    {
        [$device] = $this->makeAttendanceContext();
        $attendanceDate = today()->toDateString();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live']);
        $payload = self::PAYLOAD + ['request_id' => 'boot-01-scan-001', 'scanned_at' => '2025-01-01T08:00:00+07:00'];
        $first = $this->postJson('/api/rfid/attendance', $payload)->assertCreated()->assertJsonPath('code', 'ATTENDANCE_CREATED');
        $this->travel(1)->days();
        $this->postJson('/api/rfid/attendance', $payload)->assertCreated()->assertExactJson($first->json());
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseCount('rfid_attendance_events', 1);
        $this->assertDatabaseCount('rfid_attendance_requests', 1);
        $this->assertSame($attendanceDate, StudentAttendance::sole()->attendance_date->toDateString());
        $this->assertDatabaseHas('rfid_attendance_requests', ['device_scanned_at' => $payload['scanned_at']]);
        $stored = DB::table('rfid_attendance_requests')->first();
        $this->assertStringNotContainsString(self::PAYLOAD['card_token'], json_encode($stored));
    }

    public function test_legacy_api_scan_preserves_distinct_events_without_request_receipts(): void
    {
        [$device] = $this->makeAttendanceContext();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live']);
        $first = $this->postJson('/api/rfid/attendance', self::PAYLOAD)->assertCreated();
        $next = $this->postJson('/api/rfid/attendance', self::PAYLOAD)->assertOk()->assertJsonPath('code', 'ALREADY_ATTENDED');
        $this->assertNotSame($first->json('event_id'), $next->json('event_id'));
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseCount('rfid_attendance_events', 2);
        $this->assertDatabaseCount('rfid_attendance_requests', 0);
    }

    public function test_diagnostic_logs_do_not_contain_device_or_card_tokens(): void
    {
        [$device] = $this->makeAttendanceContext();
        config(['rfid.diagnostics.enabled' => true]);
        Log::spy();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live'])
            ->postJson('/api/rfid/attendance', self::PAYLOAD + ['request_id' => 'safe-log'])->assertCreated();
        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            $data = json_encode([$message, $context], JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString(self::PAYLOAD['card_token'], $data);
            $this->assertStringNotContainsString('token-live', $data);

            return true;
        })->times(3);
    }

    public function test_request_id_payload_conflict_returns_409_without_another_event(): void
    {
        [$device] = $this->makeAttendanceContext();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live']);
        $payload = self::PAYLOAD + ['request_id' => 'same-id'];
        $this->postJson('/api/rfid/attendance', $payload)->assertCreated();
        foreach (['card_token' => str_repeat('A', 32), 'uid' => 'ABCD', 'scanned_at' => '2026-01-01T00:00:00Z'] as $field => $value) {
            $this->postJson('/api/rfid/attendance', array_replace($payload, [$field => $value]))
                ->assertStatus(409)->assertJsonPath('code', 'REQUEST_ID_CONFLICT');
        }
        $this->assertDatabaseCount('rfid_attendance_events', 1);
    }

    public function test_disabled_rfid_response_is_replayed_and_device_rejection_is_not_cached(): void
    {
        $device = RfidDevice::create(['device_id' => 'disabled-reader', 'name' => 'Reader', 'device_type' => 'reader',
            'token_hash' => hash('sha256', 'disabled-test-token'), 'is_active' => true]);
        $payload = self::PAYLOAD + ['request_id' => 'disabled-scan'];
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'disabled-test-token']);
        $first = $this->postJson('/api/rfid/attendance', $payload)->assertForbidden()->assertJsonPath('code', 'RFID_DISABLED');
        $this->postJson('/api/rfid/attendance', $payload)->assertForbidden()->assertExactJson($first->json());
        $this->withHeaders(['X-Device-Token' => 'wrong'])->postJson('/api/rfid/attendance', $payload)->assertUnauthorized();
        $this->assertDatabaseCount('rfid_attendance_requests', 1);
        $this->assertDatabaseCount('rfid_attendance_events', 1);
    }

    public function test_business_and_validation_failures_are_idempotent(): void
    {
        [$device] = $this->makeAttendanceContext();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live']);
        foreach ([['card_token' => str_repeat('A', 32), 'request_id' => 'unknown-card'],
            ['card_token' => 'invalid', 'request_id' => 'invalid-card']] as $payload) {
            $first = $this->postJson('/api/rfid/attendance', $payload);
            $this->assertContains($first->status(), [404, 422]);
            $this->postJson('/api/rfid/attendance', $payload)->assertStatus($first->status())->assertExactJson($first->json());
        }
        $this->assertDatabaseCount('rfid_attendance_events', 2);
        $this->assertDatabaseCount('rfid_attendance_requests', 2);
        $this->postJson('/api/rfid/attendance', ['card_token' => str_repeat('B', 32), 'request_id' => 'invalid-card'])
            ->assertStatus(409);
        $this->assertDatabaseCount('rfid_attendance_events', 2);
    }

    public function test_same_request_id_is_scoped_to_device_and_is_case_sensitive(): void
    {
        [$device] = $this->makeAttendanceContext();
        $payload = self::PAYLOAD + ['request_id' => 'Scan-1'];
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live'])
            ->postJson('/api/rfid/attendance', $payload)->assertCreated();
        $this->postJson('/api/rfid/attendance', array_replace($payload, ['request_id' => 'scan-1']))->assertOk();
        $second = RfidDevice::create(['device_id' => 'reader-second', 'name' => 'Second', 'device_type' => 'reader',
            'token_hash' => hash('sha256', 'second-token'), 'is_active' => true]);
        $this->withHeaders(['X-Device-ID' => $second->device_id, 'X-Device-Token' => 'second-token'])
            ->postJson('/api/rfid/attendance', $payload)->assertOk();
        $this->assertDatabaseCount('rfid_attendance_requests', 3);
    }

    public function test_database_enforces_unique_device_request_id(): void
    {
        [$device] = $this->makeAttendanceContext();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live'])
            ->postJson('/api/rfid/attendance', self::PAYLOAD + ['request_id' => 'unique-1'])->assertCreated();
        $row = (array) DB::table('rfid_attendance_requests')->first();
        unset($row['id']);
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('rfid_attendance_requests')->insert($row);
    }

    public function test_invalid_new_metadata_and_unauthorized_retry_do_not_create_attendance(): void
    {
        [$device] = $this->makeAttendanceContext();
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live']);
        $this->postJson('/api/rfid/attendance', self::PAYLOAD + ['request_id' => str_repeat('a', 129)])->assertUnprocessable();
        $this->postJson('/api/rfid/attendance', self::PAYLOAD + ['request_id' => 'invalid-date', 'scanned_at' => 'garbage'])->assertUnprocessable();
        $device->update(['is_active' => false]);
        $this->postJson('/api/rfid/attendance', self::PAYLOAD + ['request_id' => 'invalid-date', 'scanned_at' => 'garbage'])->assertUnauthorized();
        $this->assertDatabaseCount('student_attendances', 0);
        $this->assertDatabaseCount('rfid_attendance_requests', 1);
    }

    public function test_server_error_rolls_back_receipt_and_event_and_retry_can_succeed(): void
    {
        [$device] = $this->makeAttendanceContext();
        $service = app(RfidAttendanceService::class);
        $this->mock(RfidAttendanceService::class, function (MockInterface $mock) use ($service): void {
            $mock->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($service): array {
                $service->record(...$args);
                throw new \RuntimeException('Simulated transient server failure');
            });
        });
        $payload = self::PAYLOAD + ['request_id' => 'retry-after-500'];
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live'])
            ->postJson('/api/rfid/attendance', $payload)->assertStatus(500);
        $this->assertDatabaseCount('rfid_attendance_events', 0);
        $this->assertDatabaseCount('student_attendances', 0);
        $this->assertDatabaseCount('rfid_attendance_requests', 0);
        $this->app->instance(RfidAttendanceService::class, $service);
        $this->postJson('/api/rfid/attendance', $payload)->assertCreated();
    }

    public function test_scan_synchronizes_saved_automatic_lessons_and_preserves_teacher_exception(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 13:44:41', 'Asia/Jakarta'));
        [$device, $card, $semester] = $this->makeAttendanceContext();
        $membership = ClassroomMembership::where('student_id', $card->student_id)->firstOrFail();
        $user = User::factory()->create();
        Personnel::create(['user_id' => $user->id, 'full_name' => 'Guru Sinkronisasi', 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'is_active' => true]);
        $subject = AcademicSubject::create(['name' => 'Mapel Sinkronisasi', 'is_active' => true]);
        $payload = [
            'academic_year_id' => $semester->academic_year_id, 'semester_id' => $semester->id,
            'classroom_id' => $membership->classroom_id, 'academic_subject_id' => $subject->id,
            'journal_date' => today()->toDateString(), 'lesson_number' => '1-2',
            'topic' => 'Sinkronisasi', 'learning_method' => 'Diskusi',
            'attendances' => [['student_id' => $card->student_id, 'status' => 'present']],
        ];
        $automatic = app(TeachingJournalService::class)->save($payload, $user);
        $this->assertSame('pending', $automatic->attendances()->sole()->status->value);
        $payload['attendances'][0] = ['student_id' => $card->student_id, 'status' => 'permitted', 'mode' => 'manual', 'notes' => 'Tidak mengikuti pelajaran ini.'];
        $manual = app(TeachingJournalService::class)->save($payload, $user);
        $this->withHeaders(['X-Device-ID' => $device->device_id, 'X-Device-Token' => 'token-live'])->postJson('/api/rfid/attendance', self::PAYLOAD)->assertCreated()->assertJsonPath('status', 'present');
        $this->assertSame('present', $automatic->attendances()->sole()->status->value);
        $this->assertSame('permitted', $manual->attendances()->sole()->status->value);
        $this->postJson('/api/rfid/attendance', self::PAYLOAD)->assertOk()->assertJsonPath('code', 'ALREADY_ATTENDED');
        $this->assertDatabaseCount('student_attendances', 1);
    }

    private function makeAttendanceContext(): array
    {
        ApplicationSetting::create(['key' => 'attendance_rfid_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance']);
        app(ApplicationSettingService::class)->clearCache();
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => today()->startOfYear(), 'ends_at' => today()->endOfYear(), 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'type' => 'ganjil', 'starts_at' => today()->startOfYear(), 'ends_at' => today()->endOfYear(), 'is_active' => true]);
        $grade = GradeLevel::create(['number' => 1, 'name' => 'Kelas 1', 'roman_label' => 'I', 'sort_order' => 1, 'is_active' => true]);
        $classroom = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'code' => 'A', 'is_active' => true]);
        $student = Student::create(['full_name' => 'Siswa Uji', 'nisn' => '1234567890', 'status' => 'active', 'gender' => 'male']);
        ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $year->id, 'status' => 'active', 'joined_at' => today()]);
        $card = StudentRfidCard::create(['student_id' => $student->id, 'uid' => self::PAYLOAD['uid'], 'card_token' => self::PAYLOAD['card_token'], 'is_active' => true]);
        $device = RfidDevice::create(['device_id' => 'reader-live', 'name' => 'Reader Live', 'device_type' => 'reader', 'token_hash' => hash('sha256', 'token-live'), 'is_active' => true, 'last_seen_at' => now()]);

        return [$device, $card, $semester];
    }
}
