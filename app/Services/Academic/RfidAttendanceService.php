<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Enums\RfidAttendanceResultCode;
use App\Models\{AcademicYear, ClassroomMembership, RfidAttendanceEvent, RfidDevice, Semester, StudentAttendance, StudentRfidCard};
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfidAttendanceService
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function record(string $rawToken, string $rawUid, RfidDevice $device): array
    {
        Log::info('RFID attendance received.', [
            'uid_masked' => $this->maskUid($rawUid),
            'device_id' => $device->device_id,
        ]);

        if (! $this->settings->get('attendance_rfid_enabled', false)) {
            return $this->failure($device, RfidAttendanceResultCode::RfidDisabled, 'Absensi RFID sedang dinonaktifkan.', 403);
        }
        $token = strtoupper($rawToken);
        if (! preg_match('/^[A-F0-9]{32}$/', $token)) {
            return $this->failure($device, RfidAttendanceResultCode::CardNotProvisioned, 'Kartu belum diprogram untuk e-Madrasah.', 422);
        }
        $card = StudentRfidCard::with('student')->where('card_token', $token)->where('is_active', true)->first();
        if (! $card) {
            return $this->failure($device, RfidAttendanceResultCode::CardNotRegistered, 'Kartu RFID tidak terdaftar.', 404);
        }
        if ($rawUid !== '' && StudentRfidCard::normalizeUid($rawUid) !== StudentRfidCard::normalizeUid($card->uid)) {
            return $this->failure($device, RfidAttendanceResultCode::CardUidMismatch, 'Identitas kartu RFID tidak sesuai.', 422, $card);
        }
        $membership = ClassroomMembership::where('student_id', $card->student_id)->where('status', 'active')->latest('joined_at')->first();
        $year = AcademicYear::where('is_active', true)->first();
        $semester = $year ? Semester::where('academic_year_id', $year->id)->where('is_active', true)->first() : null;
        if (! $membership || ! $year || ! $semester) {
            return $this->failure($device, RfidAttendanceResultCode::AcademicContextMissing, 'Periode akademik atau rombel aktif tidak tersedia.', 422, $card, $membership?->classroom_id);
        }

        $result = DB::transaction(function () use ($card, $device, $membership, $year, $semester): array {
            $existing = StudentAttendance::where('student_id', $card->student_id)->where('classroom_id', $membership->classroom_id)->whereDate('attendance_date', today())->lockForUpdate()->first();
            $card->update(['last_used_at' => now()]);
            if ($existing) {
                $manual = ($existing->source?->value ?? $existing->source) === 'manual';
                $result = ['http' => 200, 'success' => true, 'code' => $manual ? RfidAttendanceResultCode::ManualStatusLocked->value : RfidAttendanceResultCode::AlreadyAttended->value, 'message' => $manual ? 'Status absensi sudah ditetapkan oleh guru.' : 'Siswa sudah melakukan absensi', 'student' => $this->studentData($card), '_attendance_id' => $existing->id];
            } else {
                $attendance = StudentAttendance::create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'classroom_id' => $membership->classroom_id, 'student_id' => $card->student_id, 'attendance_date' => today(), 'status' => 'present', 'source' => 'rfid', 'scanned_at' => now(), 'rfid_device_id' => $device->id]);
                $result = ['http' => 201, 'success' => true, 'code' => RfidAttendanceResultCode::AttendanceCreated->value, 'status' => 'present', 'student' => $this->studentData($card), 'message' => 'Absensi berhasil', '_attendance_id' => $attendance->id];
            }

            $event = $this->createEvent($device, $result['code'], true, $result['message'], $card, $membership->classroom_id, $result['_attendance_id']);
            $result['event_id'] = $event->id;

            return $result;
        });
        unset($result['_attendance_id']);
        return $result;
    }

    private function failure(RfidDevice $device, RfidAttendanceResultCode $code, string $message, int $http, ?StudentRfidCard $card = null, ?int $classroomId = null): array
    {
        $event = $this->createEvent($device, $code->value, false, $message, $card, $classroomId);

        return array_filter([
            'http' => $http,
            'success' => false,
            'code' => $code->value,
            'message' => $message,
            'event_id' => $event->id,
            'student' => $card ? $this->studentData($card) : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function studentData(StudentRfidCard $card): array { return ['name' => $card->student->full_name, 'nis' => $card->student->nis ?? $card->student->nisn]; }

    private function createEvent(RfidDevice $device, string $code, bool $success, string $message, ?StudentRfidCard $card = null, ?int $classroomId = null, ?int $attendanceId = null): RfidAttendanceEvent
    {
        $event = RfidAttendanceEvent::create([
            'rfid_device_id' => $device->id,
            'student_id' => $card?->student_id,
            'classroom_id' => $classroomId,
            'student_attendance_id' => $attendanceId,
            'result_code' => $code,
            'success' => $success,
            'message' => $message,
            'scanned_at' => now(),
        ]);
        Log::info('RFID live event emitted.', ['event_id' => $event->id, 'device_id' => $device->device_id, 'code' => $code, 'student_id' => $card?->student_id]);

        return $event;
    }

    private function maskUid(string $uid): string
    {
        $normalized = StudentRfidCard::normalizeUid($uid);
        return $normalized === '' ? '(kosong)' : str_repeat('*', max(0, strlen($normalized) - 4)).substr($normalized, -4);
    }
}
