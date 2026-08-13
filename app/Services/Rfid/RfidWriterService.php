<?php

declare(strict_types=1);

namespace App\Services\Rfid;

use App\Enums\RfidCommandStatus;
use App\Models\{RfidDevice, RfidDeviceCommand, Student, StudentRfidCard, User};
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RfidWriterService
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function onlineWriter(): ?RfidDevice
    {
        return RfidDevice::query()->where('is_active', true)->where('mode', 'writer')->where('last_seen_at', '>=', now()->subSeconds(15))->latest('last_seen_at')->first();
    }

    public function issue(Student $student, User $actor, bool $replace = false): RfidDeviceCommand
    {
        if (! $this->settings->get('rfid_writer_enabled', false)) throw ValidationException::withMessages(['writer' => 'RFID Writer sedang dinonaktifkan.']);
        $device = $this->onlineWriter();
        if (! $device) throw ValidationException::withMessages(['writer' => 'RFID Writer tidak terhubung.']);
        if (! $replace && $student->activeRfidCard()->exists()) throw ValidationException::withMessages(['card' => 'Siswa sudah mempunyai kartu aktif. Gunakan Ganti Kartu atau Tulis Ulang.']);
        $token = self::generateCardToken();
        return RfidDeviceCommand::create(['device_id' => $device->id, 'student_id' => $student->id, 'requested_by' => $actor->id, 'command' => 'write_card', 'payload' => ['card_token' => $token], 'status' => RfidCommandStatus::Pending, 'replaces_existing' => $replace, 'expires_at' => now()->addSeconds(60)]);
    }

    public static function generateCardToken(): string
    {
        return strtoupper(bin2hex(random_bytes(16)));
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
        return DB::transaction(function () use ($device, $command, $data): RfidDeviceCommand {
            $command = RfidDeviceCommand::lockForUpdate()->findOrFail($command->id);
            abort_unless($command->device_id === $device->id, 404);
            if ($command->expires_at->isPast()) { $command->update(['status' => RfidCommandStatus::Expired, 'failed_at' => now()]); throw ValidationException::withMessages(['command' => 'Waktu penulisan kartu habis. Silakan coba kembali.']); }
            if ($command->status !== RfidCommandStatus::Processing) throw ValidationException::withMessages(['command' => 'Command tidak dapat diselesaikan pada status saat ini.']);
            $expected = $command->payload['card_token'];
            if (! $data['success'] || ! $data['verified'] || ! hash_equals($expected, strtoupper($data['card_token']))) throw ValidationException::withMessages(['verified' => 'Verifikasi data kartu gagal.']);
            $uid = StudentRfidCard::normalizeUid($data['uid']);
            if (StudentRfidCard::where('uid', $uid)->exists()) throw ValidationException::withMessages(['uid' => 'UID kartu sudah terdaftar.']);
            if (StudentRfidCard::where('card_token', $expected)->exists()) throw ValidationException::withMessages(['card_token' => 'Token kartu sudah terdaftar.']);
            StudentRfidCard::where('student_id', $command->student_id)->where('is_active', true)->update(['is_active' => false]);
            $card = StudentRfidCard::create(['student_id' => $command->student_id, 'uid' => $uid, 'card_token' => $expected, 'is_active' => true, 'registered_at' => now(), 'issued_by' => $command->requested_by]);
            $command->update(['status' => RfidCommandStatus::Completed, 'completed_at' => now(), 'result' => ['uid' => $uid, 'verified' => true]]);
            activity('rfid-card')->causedBy($command->requester)->performedOn($command->student)->withProperties(['card_id' => $card->id, 'device_id' => $device->device_id, 'action' => $command->replaces_existing ? 'replace' : 'issue'])->log('Menerbitkan kartu RFID siswa.');
            return $command->fresh(['student', 'device']);
        });
    }

    public function fail(RfidDevice $device, RfidDeviceCommand $command, string $code): void
    {
        abort_unless($command->device_id === $device->id, 404);
        if (in_array($command->status, [RfidCommandStatus::Pending, RfidCommandStatus::Processing], true)) $command->update(['status' => RfidCommandStatus::Failed, 'failed_at' => now(), 'result' => ['code' => $code]]);
    }
}
