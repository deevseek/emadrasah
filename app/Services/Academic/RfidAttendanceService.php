<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{AcademicYear, ClassroomMembership, RfidDevice, Semester, StudentAttendance, StudentRfidCard};
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Support\Facades\DB;

class RfidAttendanceService
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function record(string $rawUid, RfidDevice $device): array
    {
        if (! $this->settings->get('attendance_rfid_enabled', false)) return ['http' => 403, 'success' => false, 'code' => 'RFID_DISABLED', 'message' => 'Absensi RFID sedang dinonaktifkan'];
        $card = StudentRfidCard::with('student')->where('uid', StudentRfidCard::normalizeUid($rawUid))->where('is_active', true)->first();
        if (! $card) return ['http' => 404, 'success' => false, 'code' => 'CARD_NOT_REGISTERED', 'message' => 'Kartu RFID belum terdaftar'];
        $membership = ClassroomMembership::where('student_id', $card->student_id)->where('status', 'active')->latest('joined_at')->first();
        $year = AcademicYear::where('is_active', true)->first();
        $semester = $year ? Semester::where('academic_year_id', $year->id)->where('is_active', true)->first() : null;
        if (! $membership || ! $year || ! $semester) return ['http' => 422, 'success' => false, 'code' => 'ACADEMIC_CONTEXT_MISSING', 'message' => 'Periode akademik atau rombel aktif tidak tersedia'];

        return DB::transaction(function () use ($card, $device, $membership, $year, $semester): array {
            $existing = StudentAttendance::where('student_id', $card->student_id)->where('classroom_id', $membership->classroom_id)->whereDate('attendance_date', today())->lockForUpdate()->first();
            $card->update(['last_used_at' => now()]);
            if ($existing) {
                $manual = ($existing->source?->value ?? $existing->source) === 'manual';
                return ['http' => 200, 'success' => true, 'code' => $manual ? 'MANUAL_STATUS_LOCKED' : 'ALREADY_ATTENDED', 'message' => $manual ? 'Status absensi sudah ditetapkan oleh guru.' : 'Siswa sudah melakukan absensi', 'student' => $this->studentData($card)];
            }
            StudentAttendance::create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'classroom_id' => $membership->classroom_id, 'student_id' => $card->student_id, 'attendance_date' => today(), 'status' => 'present', 'source' => 'rfid', 'scanned_at' => now(), 'rfid_device_id' => $device->id]);
            return ['http' => 201, 'success' => true, 'status' => 'present', 'student' => $this->studentData($card), 'message' => 'Absensi berhasil'];
        });
    }

    private function studentData(StudentRfidCard $card): array { return ['name' => $card->student->full_name, 'nis' => $card->student->nis ?? $card->student->nisn]; }
}
