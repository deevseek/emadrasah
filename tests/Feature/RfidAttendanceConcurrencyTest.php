<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ApplicationSetting;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\GradeLevel;
use App\Models\RfidDevice;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentRfidCard;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class RfidAttendanceConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'rfid_test_concurrency') {
            $this->markTestSkipped('Race condition memerlukan MySQL/MariaDB lokal khusus bernama rfid_test_concurrency.');
        }
        // Worker membutuhkan fixture committed. Wipe database test khusus,
        // tanpa transaksi PHPUnit dan tanpa rollback migration modul lain.
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->beforeApplicationDestroyed(fn () => Artisan::call('db:wipe', ['--force' => true]));
    }

    protected function context(): RfidDevice
    {
        ApplicationSetting::create(['key' => 'attendance_rfid_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance']);
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
        Semester::create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'type' => 'ganjil', 'starts_at' => '2026-07-01', 'ends_at' => '2026-12-31', 'is_active' => true]);
        $grade = GradeLevel::create(['number' => 1, 'name' => 'Kelas 1', 'roman_label' => 'I', 'sort_order' => 1, 'is_active' => true]);
        $room = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'code' => 'A', 'is_active' => true]);
        $student = Student::create(['full_name' => 'Siswa Race', 'gender' => 'male', 'status' => 'active']);
        ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $room->id, 'academic_year_id' => $year->id, 'status' => 'active', 'joined_at' => '2026-07-01']);
        StudentRfidCard::create(['student_id' => $student->id, 'uid' => 'ABCD1234', 'card_token' => str_repeat('A', 32), 'is_active' => true]);

        return $this->device('race-reader-1');
    }

    protected function device(string $id): RfidDevice
    {
        return RfidDevice::create(['device_id' => $id, 'name' => $id, 'device_type' => 'reader',
            'token_hash' => hash('sha256', 'concurrency-test-'.$id), 'is_active' => true]);
    }

    protected function concurrent(array $deviceIds, array $payloads): array
    {
        $directory = sys_get_temp_dir().'/rfid-race-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $processes = [];
        try {
            foreach ($payloads as $index => $payload) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/rfid-concurrent-request.php'),
                    $directory, (string) $index, $deviceIds[$index], json_encode($payload, JSON_THROW_ON_ERROR)], base_path());
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 10;
            while (count(glob($directory.'/ready-*')) < 2 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(2, glob($directory.'/ready-*'), 'Kedua worker harus siap sebelum request dilepas.');
            touch($directory.'/start');
            while (count(glob($directory.'/entered-*')) < 1 && microtime(true) < $deadline) {
                usleep(10000);
            }
            // Transaksi pertama ditahan tepat sebelum insert event, sehingga
            // worker kedua benar-benar bersaing saat request pertama belum commit.
            usleep(250000);
            $this->assertCount(1, glob($directory.'/entered-*'), 'Request kedua harus menunggu kunci transaksi pertama.');
            $this->assertTrue($processes[0]->isRunning() && $processes[1]->isRunning());
            touch($directory.'/release');
            $results = [];
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    public function test_simultaneous_identical_requests_return_one_original_event(): void
    {
        $device = $this->context();
        $payload = ['uid' => 'ABCD1234', 'card_token' => str_repeat('A', 32), 'request_id' => 'race-1'];
        $results = $this->concurrent([$device->device_id, $device->device_id], [$payload, $payload]);
        $this->assertSame(201, $results[0]['status']);
        $this->assertSame($results[0], $results[1]);
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseCount('rfid_attendance_events', 1);
        $this->assertDatabaseCount('rfid_attendance_requests', 1);
    }

    public function test_simultaneous_conflicting_payloads_produce_one_conflict(): void
    {
        $device = $this->context();
        $payload = ['uid' => 'ABCD1234', 'card_token' => str_repeat('A', 32), 'request_id' => 'race-conflict'];
        $other = $payload + ['scanned_at' => '2026-10-08T08:00:00+07:00'];
        $results = $this->concurrent([$device->device_id, $device->device_id], [$payload, $other]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([201, 409], $statuses);
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseCount('rfid_attendance_events', 1);
        $this->assertDatabaseCount('rfid_attendance_requests', 1);
    }

    public function test_legacy_scans_from_two_readers_serialize_first_attendance(): void
    {
        $first = $this->context();
        $second = $this->device('race-reader-2');
        $payload = ['uid' => 'ABCD1234', 'card_token' => str_repeat('A', 32)];
        $results = $this->concurrent([$first->device_id, $second->device_id], [$payload, $payload]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([200, 201], $statuses);
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseCount('rfid_attendance_events', 2);
        $this->assertDatabaseCount('rfid_attendance_requests', 0);
    }
}
