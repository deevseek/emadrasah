<?php

declare(strict_types=1);

namespace App\Services\Rfid;

use App\Enums\RfidCommandStatus;
use App\Enums\RfidWriteOperation;
use App\Exceptions\RfidUidConflictException;
use App\Models\{RfidDevice, RfidDeviceCommand, Student, StudentRfidCard, User};
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RfidWriterService
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function onlineWriter(): ?RfidDevice
    {
        return RfidDevice::query()->where('is_active', true)->where('device_type', 'writer')->where('last_seen_at', '>=', now()->subSeconds(75))->latest('last_seen_at')->first();
    }

    public function issue(Student $student, User $actor, RfidWriteOperation|bool $operation = RfidWriteOperation::Write): RfidDeviceCommand
    {
        // Boolean dipertahankan sementara untuk kompatibilitas pemanggil internal lama.
        if (is_bool($operation)) $operation = $operation ? RfidWriteOperation::Rewrite : RfidWriteOperation::Write;
        if (! $this->settings->get('rfid_writer_enabled', false)) throw ValidationException::withMessages(['writer' => 'RFID Writer sedang dinonaktifkan.']);
        $device = $this->onlineWriter();
        if (! $device) throw ValidationException::withMessages(['writer' => 'RFID Writer tidak terhubung.']);
        return DB::transaction(function () use ($student, $actor, $operation, $device): RfidDeviceCommand {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            if ($operation === RfidWriteOperation::Write && $student->activeRfidCard()->exists()) throw ValidationException::withMessages(['card' => 'Siswa sudah mempunyai kartu aktif. Gunakan Tulis Ulang Kartu.']);
            if ($operation === RfidWriteOperation::Rewrite && ! $student->activeRfidCard()->exists()) throw ValidationException::withMessages(['card' => 'Siswa belum mempunyai kartu aktif. Gunakan Tulis Kartu.']);

            RfidDeviceCommand::query()->where('student_id', $student->id)->whereIn('status', [RfidCommandStatus::Pending, RfidCommandStatus::Processing])->where('expires_at', '<=', now())->update(['status' => RfidCommandStatus::Expired, 'failed_at' => now(), 'result' => json_encode(['code' => 'WRITE_TIMEOUT'])]);
            $activeCommand = RfidDeviceCommand::query()->where('student_id', $student->id)->whereIn('status', [RfidCommandStatus::Pending, RfidCommandStatus::Processing])->where('expires_at', '>', now())->lockForUpdate()->exists();
            if ($activeCommand) throw ValidationException::withMessages(['command' => 'Penulisan kartu siswa ini masih berlangsung. Tunggu hingga selesai atau kedaluwarsa.']);

            $token = $this->uniqueCardToken();
            $supportsRewrite = $operation->replacesExisting() && $this->supportsRewriteCommand($device->firmware_version);

            return RfidDeviceCommand::create([
                'device_id' => $device->id,
                'student_id' => $student->id,
                'requested_by' => $actor->id,
                'command' => $supportsRewrite ? 'rewrite_card' : 'write_card',
                'payload' => ['card_token' => $token, 'operation' => $operation->value],
                'status' => RfidCommandStatus::Pending,
                'replaces_existing' => $operation->replacesExisting(),
                'expires_at' => now()->addSeconds(60),
            ]);
        });
    }

    public static function generateCardToken(): string
    {
        return strtoupper(bin2hex(random_bytes(16)));
    }

    private function uniqueCardToken(): string
    {
        do {
            $token = self::generateCardToken();
        } while (StudentRfidCard::query()->where('card_token', $token)->exists()
            || RfidDeviceCommand::query()->where('payload->card_token', $token)->exists());

        return $token;
    }

    private function supportsRewriteCommand(?string $firmwareVersion): bool
    {
        if (! $firmwareVersion || ! preg_match('/^(\d+\.\d+\.\d+)/', $firmwareVersion, $matches)) return false;

        return version_compare($matches[1], '3.4.0', '>=');
    }

    public function next(RfidDevice $device): ?RfidDeviceCommand
    {
        RfidDeviceCommand::where('status', RfidCommandStatus::Pending)->where('expires_at', '<=', now())->update(['status' => RfidCommandStatus::Expired, 'failed_at' => now(), 'result' => json_encode(['code' => 'WRITE_TIMEOUT'])]);
        return DB::transaction(function () use ($device): ?RfidDeviceCommand {
            $command = RfidDeviceCommand::where('device_id', $device->id)->where('status', RfidCommandStatus::Pending)->where('expires_at', '>', now())->oldest()->lockForUpdate()->first();
            if ($command) $command->update(['status' => RfidCommandStatus::Processing, 'started_at' => now()]);
            return $command;
        });
    }

    public function complete(RfidDevice $device, RfidDeviceCommand $command, array $data): RfidDeviceCommand
    {
        $expired = false;
        $completed = DB::transaction(function () use ($device, $command, $data, &$expired): RfidDeviceCommand {
            $command = RfidDeviceCommand::lockForUpdate()->findOrFail($command->id);
            abort_unless($command->device_id === $device->id, 404);
            abort_unless(in_array($command->command, ['write_card', 'rewrite_card'], true), 422, 'Command bukan penulisan kartu.');
            $expected = $command->payload['card_token'];
            $uid = StudentRfidCard::normalizeUid($data['uid']);
            if ($command->status === RfidCommandStatus::Completed) {
                if (($command->result['uid'] ?? null) === $uid && hash_equals($expected, $data['card_token'])) return $command->fresh(['student', 'device']);
                throw ValidationException::withMessages(['command' => 'Hasil retry tidak sama dengan hasil penulisan yang telah disimpan.']);
            }
            if ($command->expires_at->isPast()) {
                $command->update(['status' => RfidCommandStatus::Expired, 'failed_at' => now(), 'result' => ['code' => 'WRITE_TIMEOUT']]);
                $expired = true;

                return $command;
            }
            if ($command->status !== RfidCommandStatus::Processing) throw ValidationException::withMessages(['command' => 'Command tidak dapat diselesaikan pada status saat ini.']);
            if (! $data['success'] || ! $data['verified'] || ! hash_equals($expected, $data['card_token'])) throw ValidationException::withMessages(['verified' => 'Verifikasi data kartu gagal.']);

            $student = Student::query()->lockForUpdate()->find($command->student_id);
            if (! $student) throw ValidationException::withMessages(['student' => 'Siswa target tidak lagi tersedia.']);
            $operation = RfidWriteOperation::tryFrom((string) ($command->payload['operation'] ?? ''))
                ?? ($command->replaces_existing ? RfidWriteOperation::Rewrite : RfidWriteOperation::Write);
            $activeCard = StudentRfidCard::query()->where('student_id', $student->id)->where('is_active', true)->lockForUpdate()->first();
            if ($operation === RfidWriteOperation::Rewrite && ! $activeCard) throw ValidationException::withMessages(['card' => 'Kartu aktif siswa tidak lagi tersedia.']);
            if ($operation === RfidWriteOperation::Write && $activeCard) throw ValidationException::withMessages(['card' => 'Siswa telah mempunyai kartu aktif.']);

            $uidAssignments = StudentRfidCard::query()->where('uid', $uid)->lockForUpdate()->get();
            $otherStudentOwnsUid = $uidAssignments->contains(fn (StudentRfidCard $assignment) => $assignment->student_id !== $student->id);
            if ($otherStudentOwnsUid && $operation !== RfidWriteOperation::Reassign) throw new RfidUidConflictException();

            $card = $uidAssignments->firstWhere('student_id', $student->id) ?? $activeCard;
            $tokenCollision = StudentRfidCard::query()->where('card_token', $expected)->when($card, fn ($query) => $query->where('id', '!=', $card->id))->lockForUpdate()->exists();
            if ($tokenCollision) throw ValidationException::withMessages(['card_token' => 'Token kartu sudah terdaftar.']);

            if ($operation === RfidWriteOperation::Reassign) {
                StudentRfidCard::query()->where('uid', $uid)->where('student_id', '!=', $student->id)->update(['is_active' => false]);
            }
            StudentRfidCard::query()->where('student_id', $student->id)->where('is_active', true)
                ->when($card, fn ($query) => $query->where('id', '!=', $card->id))->update(['is_active' => false]);

            if ($card) {
                $card->update(['student_id' => $student->id, 'uid' => $uid, 'card_token' => $expected, 'is_active' => true, 'registered_at' => now(), 'issued_by' => $command->requested_by]);
            } else {
                $card = StudentRfidCard::create(['student_id' => $student->id, 'uid' => $uid, 'card_token' => $expected, 'is_active' => true, 'registered_at' => now(), 'issued_by' => $command->requested_by]);
            }
            $command->update(['status' => RfidCommandStatus::Completed, 'completed_at' => now(), 'result' => ['uid' => $uid, 'verified' => true]]);
            activity('rfid-card')->causedBy($command->requester)->performedOn($command->student)->withProperties(['card_id' => $card->id, 'device_id' => $device->device_id, 'action' => $operation->value])->log($operation->replacesExisting() ? 'Menulis ulang kartu RFID siswa.' : 'Menerbitkan kartu RFID siswa.');
            return $command->fresh(['student', 'device']);
        });

        if ($expired) throw ValidationException::withMessages(['command' => 'Waktu penulisan kartu habis. Silakan coba kembali.']);

        return $completed;
    }

    public function fail(RfidDevice $device, RfidDeviceCommand $command, string $code): void
    {
        DB::transaction(function () use ($device, $command, $code): void {
            $command = RfidDeviceCommand::query()->lockForUpdate()->findOrFail($command->id);
            abort_unless($command->device_id === $device->id, 404);
            if ($command->expires_at->isPast() && in_array($command->status, [RfidCommandStatus::Pending, RfidCommandStatus::Processing], true)) {
                $command->update(['status' => RfidCommandStatus::Expired, 'failed_at' => now(), 'result' => ['code' => 'WRITE_TIMEOUT']]);
            } elseif (in_array($command->status, [RfidCommandStatus::Pending, RfidCommandStatus::Processing], true)) {
                $command->update(['status' => RfidCommandStatus::Failed, 'failed_at' => now(), 'result' => ['code' => $code]]);
            }
        });
    }
}
