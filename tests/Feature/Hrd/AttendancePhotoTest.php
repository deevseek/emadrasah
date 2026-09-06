<?php

declare(strict_types=1);

namespace Tests\Feature\Hrd;

use App\Contracts\FaceRecognitionService;
use App\Models\AttendanceChallenge;
use App\Models\AttendanceFaceVerification;
use App\Models\Personnel;
use App\Models\PersonnelAttendance;
use App\Models\PersonnelAttendanceAudit;
use App\Models\User;
use App\Services\Hrd\AttendanceSecurityService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class AttendancePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_hrd_can_view_private_attendance_photo_from_attendance_list(): void
    {
        Storage::fake('local');
        $hrd = User::factory()->create(['must_change_password' => false]);
        $hrd->syncRoles(['hrd']);
        [$attendance, $verification] = $this->attendanceWithPhoto();
        Storage::disk('local')->put($verification->snapshot_path, 'foto-absensi');

        $this->actingAs($hrd)->get(route('hrd.attendance.index'))
            ->assertOk()
            ->assertSee('Foto Absensi')
            ->assertSee(route('hrd.attendance.photo', [$attendance, 'check_in']), false);

        $this->actingAs($hrd)->get(route('hrd.attendance.photo', [$attendance, 'check_in']))
            ->assertOk()
            ->assertHeader('cache-control', 'private, no-store')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_user_without_view_all_permission_cannot_view_attendance_photo(): void
    {
        Storage::fake('local');
        $teacher = User::factory()->create(['must_change_password' => false]);
        $teacher->syncRoles(['guru']);
        [$attendance, $verification] = $this->attendanceWithPhoto();
        Storage::disk('local')->put($verification->snapshot_path, 'foto-absensi');

        $this->actingAs($teacher)
            ->get(route('hrd.attendance.photo', [$attendance, 'check_in']))
            ->assertForbidden();
    }

    public function test_successful_face_verification_stores_snapshot_on_private_disk(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $personnel = Personnel::create(['full_name' => 'Siti Aminah', 'gender' => 'female', 'employment_status' => 'permanent', 'position' => 'Guru', 'is_active' => true, 'user_id' => $user->id]);
        $faces = Mockery::mock(FaceRecognitionService::class);
        $faces->shouldReceive('verify')->once()->andReturn(['faces' => 1, 'matched_personnel_id' => $personnel->id, 'confidence' => .95, 'liveness_passed' => true]);
        $faces->shouldReceive('livenessSupported')->once()->andReturn(true);
        $faces->shouldReceive('provider')->once()->andReturn('test');
        $this->app->instance(FaceRecognitionService::class, $faces);
        $request = Request::create('/hrd/attendance/face-verify', 'POST', ['device_uuid' => (string) Str::uuid()]);
        $request->setLaravelSession($this->app['session']->driver());
        $security = $this->app->make(AttendanceSecurityService::class);
        $challenge = $security->challenge($user, $personnel, 'check_in', $request);

        $verification = $security->verifyFace($user, $personnel, $challenge['id'], $challenge['nonce'], UploadedFile::fake()->image('absensi.jpg'), $request);

        $this->assertNotNull($verification->snapshot_path);
        Storage::disk('local')->assertExists($verification->snapshot_path);
        $this->assertStringStartsWith('attendance-snapshots/', $verification->snapshot_path);
    }

    private function attendanceWithPhoto(): array
    {
        $personnel = Personnel::create(['full_name' => 'Ahmad Fauzi', 'gender' => 'male', 'employment_status' => 'permanent', 'position' => 'Guru', 'is_active' => true]);
        $attendance = PersonnelAttendance::create(['personnel_id' => $personnel->id, 'attendance_date' => today(), 'shift_number' => 1, 'check_in_time' => now(), 'status' => 'hadir', 'method' => 'self']);
        $challenge = AttendanceChallenge::create(['id' => (string) Str::uuid(), 'nonce_hash' => hash('sha256', Str::random(64)), 'user_id' => User::factory()->create()->id, 'personnel_id' => $personnel->id, 'session_hash' => hash('sha256', 'session'), 'intended_action' => 'check_in', 'expires_at' => now()->addMinute()]);
        $verification = AttendanceFaceVerification::create(['id' => (string) Str::uuid(), 'challenge_id' => $challenge->id, 'personnel_id' => $personnel->id, 'provider' => 'test', 'confidence' => .95, 'liveness_passed' => true, 'snapshot_path' => 'attendance-snapshots/test.jpg', 'verified_at' => now(), 'expires_at' => now()->addMinute()]);
        PersonnelAttendanceAudit::create(['personnel_id' => $personnel->id, 'attendance_id' => $attendance->id, 'challenge_id' => $challenge->id, 'event' => 'check_in', 'result' => 'accepted', 'face_verified' => true, 'occurred_at' => now()]);

        return [$attendance, $verification];
    }
}
