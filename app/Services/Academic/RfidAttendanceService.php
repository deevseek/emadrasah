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
        if (! $this->settings->get('attendance_rfid_enabled', false)) return ['http' => 403, 'success' => false, 'code' => 'RFID_DISABLED', 'message' => 'Absensi RFID sedang dinonaktifkan'];
        $token = strtoupper($rawToken);
        if (! preg_match('/^[A-F0-9]{32}$/', $token)) return ['http' => 422, 'success' => false, 'code' => 'CARD_NOT_PROVISIONED', 'message' => 'Kartu belum diprogram untuk e-Madrasah.'];
        $card = StudentRfidCard::with('student')->where('card_token', $token)->where('is_active', true)->first();
        if (! $card) return ['http' => 404, 'success' => false, 'code' => 'CARD_NOT_REGISTERED', 'message' => 'Kartu RFID belum terdaftar'];
        $membership = ClassroomMembership::where('student_id', $card->student_id)->where('status', 'active')->latest('joined_at')->first();
        $year = AcademicYear::where('is_active', true)->first();
        $semester = $year ? Semester::where('academic_year_id', $year->id)->where('is_active', true)->first() : null;
        if (! $membership || ! $year || ! $semester) return ['http' => 422, 'success' => false, 'code' => 'ACADEMIC_CONTEXT_MISSING', 'message' => 'Periode akademik atau rombel aktif tidak tersedia'];

        $result = DB::transaction(function () use ($card, $device, $membership, $year, $semester): array {
            $existing = StudentAttendance::where('student_id', $card->student_id)->where('classroom_id', $membership->classroom_id)->whereDate('attendance_date', today())->lockForUpdate()->first();
            $card->update(['last_used_at' => now()]);
            if ($existing) {
                $manual = ($existing->source?->value ?? $existing->source) === 'manual';
                return ['http' => 200, 'success' => true, 'code' => $manual ? RfidAttendanceResultCode::ManualStatusLocked->value : RfidAttendanceResultCode::AlreadyAttended->value, 'message' => $manual ? 'Status absensi sudah ditetapkan oleh guru.' : 'Siswa sudah melakukan absensi', 'student' => $this->studentData($card), '_attendance_id' => $existing->id];
            }
            $attendance = StudentAttendance::create(['academic_year_id' => $year->id, 'semester_id' => $semester->id, 'classroom_id' => $membership->classroom_id, 'student_id' => $card->student_id, 'attendance_date' => today(), 'status' => 'present', 'source' => 'rfid', 'scanned_at' => now(), 'rfid_device_id' => $device->id]);
            return ['http' => 201, 'success' => true, 'code' => RfidAttendanceResultCode::AttendanceCreated->value, 'status' => 'present', 'student' => $this->studentData($card), 'message' => 'Absensi berhasil', '_attendance_id' => $attendance->id];
        });
        try {
            RfidAttendanceEvent::create(['rfid_device_id'=>$device->id,'student_id'=>$card->student_id,'classroom_id'=>$membership->classroom_id,'student_attendance_id'=>$result['_attendance_id'],'result_code'=>$result['code'],'success'=>$result['success'],'message'=>$result['message'],'scanned_at'=>now()]);
        } catch (\Throwable $exception) {
            Log::warning('RFID attendance live event gagal dicatat.', ['attendance_id'=>$result['_attendance_id'],'exception'=>$exception->getMessage()]);
        }
        unset($result['_attendance_id']);
        // ATTENDANCE_CREATED bersifat internal; respons sukses ESP32 lama tidak memuat code.
        if ($result['code'] === RfidAttendanceResultCode::AttendanceCreated->value) unset($result['code']);
        return $result;
    }

    private function studentData(StudentRfidCard $card): array { return ['name' => $card->student->full_name, 'nis' => $card->student->nis ?? $card->student->nisn]; }
}
