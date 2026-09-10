<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RfidCommandStatus;
use App\Models\{ApplicationSetting, RfidDevice, RfidDeviceCommand, Student, StudentRfidCard, User};
use App\Services\Rfid\RfidWriterService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidCardRewriteTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Student $student;
    private RfidDevice $writer;
    private string $deviceToken = 'token-writer-aman';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
        ApplicationSetting::create(['key' => 'rfid_writer_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'attendance']);
        $this->operator = User::factory()->create(['must_change_password' => false]);
        $this->operator->assignRole('operator');
        $this->student = Student::create(['full_name' => 'Ahmad Fauzan', 'gender' => 'male', 'status' => 'active']);
        $this->writer = RfidDevice::create([
            'device_id' => 'writer-1', 'name' => 'Writer', 'device_type' => 'writer',
            'firmware_version' => '3.4.0-esp8266', 'token_hash' => hash('sha256', $this->deviceToken),
            'is_active' => true, 'last_seen_at' => now(),
        ]);
    }

    public function test_existing_write_card_flow_remains_compatible(): void
    {
        $command = $this->createCommand(false);

        $this->assertSame('write_card', $command->command);
        $this->complete($command, 'F3ED113A')->assertOk();
        $this->assertDatabaseHas('student_rfid_cards', ['student_id' => $this->student->id, 'uid' => 'F3ED113A', 'card_token' => $command->payload['card_token'], 'is_active' => true]);
    }

    public function test_rewrite_same_card_only_activates_new_token_after_verified_completion(): void
    {
        $card = $this->activeCard();
        $command = $this->createCommand(true);

        $this->assertSame('rewrite_card', $command->command);
        $this->assertSame('rewrite', $command->payload['operation']);
        $this->assertSame('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $card->fresh()->card_token);

        $this->complete($command, $card->uid)->assertOk();

        $this->assertSame($command->payload['card_token'], $card->fresh()->card_token);
        $this->assertSame(1, StudentRfidCard::query()->where('student_id', $this->student->id)->count());
    }

    public function test_rewrite_can_replace_physical_card_uid_after_success(): void
    {
        $card = $this->activeCard();
        $command = $this->createCommand(true);

        $this->complete($command, 'A1B2C3D4')->assertOk();

        $this->assertSame('A1B2C3D4', $card->fresh()->uid);
    }

    public function test_failed_and_expired_rewrites_keep_the_active_assignment(): void
    {
        $card = $this->activeCard();
        $failed = $this->createCommand(true);
        $this->deviceRequest()->postJson("/api/rfid/device/command/{$failed->id}/fail", ['error_code' => 'VERIFY_FAILED'])->assertOk();
        $this->assertSame('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $card->fresh()->card_token);

        $expired = $this->createCommand(true);
        $expired->update(['status' => RfidCommandStatus::Processing, 'expires_at' => now()->subSecond()]);
        $this->complete($expired, $card->uid)->assertUnprocessable();
        $this->assertSame('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $card->fresh()->card_token);
    }

    public function test_identical_complete_retry_is_idempotent_but_mismatched_token_is_rejected(): void
    {
        $card = $this->activeCard();
        $command = $this->createCommand(true);

        $this->complete($command, $card->uid)->assertOk();
        $this->complete($command, $card->uid)->assertOk();
        $this->assertSame(1, StudentRfidCard::query()->where('student_id', $this->student->id)->count());

        $this->complete($command, $card->uid, 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB')->assertUnprocessable();
    }

    public function test_invalid_or_unverified_completion_does_not_change_assignment(): void
    {
        $card = $this->activeCard();
        $command = $this->createCommand(true);
        $payload = $this->completionPayload($command, $card->uid);

        $this->deviceRequest()->postJson("/api/rfid/device/command/{$command->id}/complete", [...$payload, 'verified' => false])->assertUnprocessable();
        foreach (['SHORT', str_repeat('A', 33), str_repeat('Z', 32), strtolower($command->payload['card_token'])] as $invalid) {
            $this->deviceRequest()->postJson("/api/rfid/device/command/{$command->id}/complete", [...$payload, 'card_token' => $invalid])->assertUnprocessable();
        }
        $this->assertSame('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $card->fresh()->card_token);
    }

    public function test_uid_owned_by_another_student_and_wrong_device_are_rejected(): void
    {
        $card = $this->activeCard();
        $other = Student::create(['full_name' => 'Siti Aminah', 'gender' => 'female', 'status' => 'active']);
        StudentRfidCard::create(['student_id' => $other->id, 'uid' => 'A1B2C3D4', 'card_token' => 'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC', 'is_active' => true]);
        $command = $this->createCommand(true);

        $this->complete($command, 'A1B2C3D4')->assertUnprocessable()
            ->assertJsonPath('error_code', 'RFID_UID_ASSIGNED_TO_OTHER_STUDENT');
        $wrong = RfidDevice::create(['device_id' => 'writer-2', 'name' => 'Writer 2', 'device_type' => 'writer', 'token_hash' => hash('sha256', 'other-token'), 'is_active' => true]);
        $this->withHeaders(['X-Device-Id' => $wrong->device_id, 'X-Device-Token' => 'other-token'])->postJson("/api/rfid/device/command/{$command->id}/complete", $this->completionPayload($command, $card->uid))->assertNotFound();

        $this->assertSame('F3ED113A', $card->fresh()->uid);
        $this->assertSame($other->id, StudentRfidCard::query()->where('uid', 'A1B2C3D4')->value('student_id'));
    }

    public function test_regular_write_cannot_take_uid_owned_by_another_student(): void
    {
        $other = Student::create(['full_name' => 'Siti Aminah', 'gender' => 'female', 'status' => 'active']);
        StudentRfidCard::create(['student_id' => $other->id, 'uid' => 'A1B2C3D4', 'card_token' => 'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC', 'is_active' => true]);
        $command = $this->createCommand(false);

        $this->complete($command, 'A1B2C3D4')->assertUnprocessable()
            ->assertJsonPath('error_code', 'RFID_UID_ASSIGNED_TO_OTHER_STUDENT');

        $this->assertDatabaseMissing('student_rfid_cards', ['student_id' => $this->student->id]);
        $this->assertSame(RfidCommandStatus::Processing, $command->fresh()->status);
    }

    public function test_explicit_reassign_deactivates_old_owner_and_activates_target_atomically(): void
    {
        $oldStudent = Student::create(['full_name' => 'Siti Aminah', 'gender' => 'female', 'status' => 'active']);
        $oldCard = StudentRfidCard::create(['student_id' => $oldStudent->id, 'uid' => 'A1B2C3D4', 'card_token' => 'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC', 'is_active' => true]);
        $command = $this->createOperationCommand('reassign');

        $this->complete($command, $oldCard->uid)->assertOk()->assertJson(['success' => true]);

        $this->assertFalse($oldCard->fresh()->is_active);
        $this->assertDatabaseHas('student_rfid_cards', ['student_id' => $this->student->id, 'uid' => $oldCard->uid, 'card_token' => $command->payload['card_token'], 'is_active' => true]);
        $this->assertSame(RfidCommandStatus::Completed, $command->fresh()->status);
    }

    public function test_completed_command_status_is_immediately_visible_to_frontend(): void
    {
        $command = $this->createCommand(false);
        $this->complete($command, 'F3ED113A')->assertOk();

        $this->actingAs($this->operator)->getJson(route('students.rfid-writer.show', [$this->student, $command]))
            ->assertOk()->assertJsonPath('status', 'completed');
    }

    public function test_reassign_rolls_back_old_assignment_if_target_assignment_fails(): void
    {
        $this->withoutExceptionHandling();
        $oldStudent = Student::create(['full_name' => 'Siti Aminah', 'gender' => 'female', 'status' => 'active']);
        $oldCard = StudentRfidCard::create(['student_id' => $oldStudent->id, 'uid' => 'A1B2C3D4', 'card_token' => 'CCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC', 'is_active' => true]);
        $command = $this->createOperationCommand('reassign');
        $listener = function (StudentRfidCard $card): void {
            if (! $card->exists && $card->student_id === $this->student->id) throw new \RuntimeException('Simulasi kegagalan penyimpanan target.');
        };
        StudentRfidCard::creating($listener);

        try {
            $this->complete($command, $oldCard->uid);
            $this->fail('Complete seharusnya melempar exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan penyimpanan target.', $exception->getMessage());
        } finally {
            StudentRfidCard::flushEventListeners();
        }

        $this->assertTrue($oldCard->fresh()->is_active);
        $this->assertSame(RfidCommandStatus::Processing, $command->fresh()->status);
        $this->assertDatabaseMissing('student_rfid_cards', ['student_id' => $this->student->id, 'uid' => $oldCard->uid]);
    }

    public function test_duplicate_active_command_is_blocked_and_old_firmware_receives_write_card(): void
    {
        $this->activeCard();
        $first = $this->createCommand(true);
        $this->actingAs($this->operator)->postJson(route('students.rfid-writer.store', $this->student), ['replace' => true])->assertUnprocessable();

        $first->update(['status' => RfidCommandStatus::Expired]);
        $this->writer->update(['firmware_version' => '3.3.9']);
        $fallback = $this->createCommand(true);
        $this->assertSame('write_card', $fallback->command);
        $this->assertSame('rewrite', $fallback->payload['operation']);
    }

    public function test_user_without_replace_permission_cannot_create_rewrite(): void
    {
        $this->activeCard();
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['rfid-writer.use']);

        $this->actingAs($user)->postJson(route('students.rfid-writer.store', $this->student), ['replace' => true])->assertForbidden();
        $this->assertDatabaseCount('rfid_device_commands', 0);
    }

    private function activeCard(): StudentRfidCard
    {
        return StudentRfidCard::create(['student_id' => $this->student->id, 'uid' => 'F3ED113A', 'card_token' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'is_active' => true]);
    }

    private function createCommand(bool $replace): RfidDeviceCommand
    {
        $response = $this->actingAs($this->operator)->postJson(route('students.rfid-writer.store', $this->student), ['replace' => $replace])->assertSuccessful();
        $command = RfidDeviceCommand::findOrFail($response->json('command_id'));
        app(RfidWriterService::class)->next($this->writer);

        return $command->fresh();
    }

    private function createOperationCommand(string $operation): RfidDeviceCommand
    {
        $response = $this->actingAs($this->operator)->postJson(route('students.rfid-writer.store', $this->student), ['operation' => $operation])->assertSuccessful();
        $command = RfidDeviceCommand::findOrFail($response->json('command_id'));
        app(RfidWriterService::class)->next($this->writer);

        return $command->fresh();
    }

    private function complete(RfidDeviceCommand $command, string $uid, ?string $token = null)
    {
        return $this->deviceRequest()->postJson("/api/rfid/device/command/{$command->id}/complete", $this->completionPayload($command, $uid, $token));
    }

    private function completionPayload(RfidDeviceCommand $command, string $uid, ?string $token = null): array
    {
        return ['success' => true, 'verified' => true, 'uid' => $uid, 'card_token' => $token ?? $command->payload['card_token']];
    }

    private function deviceRequest(): static
    {
        return $this->withHeaders(['X-Device-Id' => $this->writer->device_id, 'X-Device-Token' => $this->deviceToken]);
    }
}
