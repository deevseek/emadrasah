<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\AcademicSubject;
use App\Models\ClassroomMembership;
use App\Models\LessonSchedule;
use App\Models\Personnel;
use App\Models\Semester;
use App\Models\User;
use App\Services\Academic\TeachingJournalService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\RfidAttendanceConcurrencyTest;

/** Run one inherited scenario against a newly created disposable database. */
class RfidSafeConcurrencyTest extends RfidAttendanceConcurrencyTest
{
    protected function setUp(): void
    {
        // Bootstrap Laravel without the original destructive database reset.
        TestCase::setUp();
        if (! app()->environment('testing') || config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'rfid_test_concurrency') {
            $this->markTestSkipped('Gunakan database MySQL sementara khusus rfid_test_concurrency.');
        }
        if (Schema::hasTable('migrations')) {
            $this->fail('Database pengujian harus kosong dan baru dibuat untuk setiap skenario.');
        }
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_simultaneous_readers_synchronize_lesson_snapshots_without_overwriting_exception(): void
    {
        if (now()->format('H:i:s') >= '23:59:00') {
            $this->markTestSkipped('Tidak tersedia pelajaran mendatang pada menit terakhir hari ini.');
        }
        $device = $this->context();
        $second = $this->device('journal-second-reader');
        $membership = ClassroomMembership::sole();
        $semester = Semester::sole();
        $user = User::factory()->create();
        Personnel::create(['user_id' => $user->id, 'full_name' => 'Guru Konkurensi', 'gender' => 'male', 'employment_status' => 'Tetap', 'position' => 'Guru', 'is_active' => true]);
        $subject = AcademicSubject::create(['name' => 'Pelajaran Mendatang', 'is_active' => true]);
        LessonSchedule::create([
            'academic_year_id' => $semester->academic_year_id, 'semester_id' => $semester->id,
            'classroom_id' => $membership->classroom_id, 'academic_subject_id' => $subject->id,
            'day_of_week' => now()->dayOfWeekIso, 'start_time' => '23:59:00', 'end_time' => '23:59:59', 'active' => true,
        ]);
        $payload = [
            'academic_year_id' => $semester->academic_year_id, 'semester_id' => $semester->id,
            'classroom_id' => $membership->classroom_id, 'academic_subject_id' => $subject->id,
            'journal_date' => today()->toDateString(), 'lesson_number' => '3-4', 'topic' => 'Konkurensi', 'learning_method' => 'Diskusi',
            'attendances' => [['student_id' => $membership->student_id, 'status' => 'pending']],
        ];
        $service = app(TeachingJournalService::class);
        $automatic = $service->save($payload, $user);
        $payload['attendances'][0] = ['student_id' => $membership->student_id, 'status' => 'permitted', 'mode' => 'manual', 'notes' => 'Izin khusus pelajaran.'];
        $manual = $service->save($payload, $user);
        $scan = ['uid' => 'ABCD1234', 'card_token' => str_repeat('A', 32)];
        $results = $this->concurrent([$device->device_id, $second->device_id], [$scan, $scan]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([200, 201], $statuses);
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertSame('present', $automatic->attendances()->sole()->status->value);
        $this->assertSame('rfid', $automatic->attendances()->sole()->daily_source);
        $this->assertSame('permitted', $manual->attendances()->sole()->status->value);
    }
}
