<?php
declare(strict_types=1);
namespace App\Services\Students;

use App\Models\Student;
use App\Models\StudentRfidCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentRfidCardService
{
    public function register(Student $student, string $uid, User $actor): void
    {
        DB::transaction(function () use ($student, $uid, $actor) {
            $student->rfidCards()->where('is_active', true)->update(['is_active' => false]);
            $card = $student->rfidCards()->create([
                'uid' => StudentRfidCard::normalizeUid($uid),
                'is_active' => true,
                'registered_at' => now(),
            ]);
            activity('rfid-card')->causedBy($actor)->performedOn($student)->withProperties(['card_id' => $card->id])->log('Mendaftarkan kartu RFID siswa.');
        });
    }

    public function deleteActive(Student $student, User $actor): void
    {
        DB::transaction(function () use ($student, $actor): void {
            $card = $student->rfidCards()
                ->where('is_active', true)
                ->lockForUpdate()
                ->latest('id')
                ->firstOrFail();

            activity('rfid-card')
                ->causedBy($actor)
                ->performedOn($student)
                ->withProperties(['card_id' => $card->id, 'uid' => $card->maskedUid()])
                ->log('Menghapus kartu RFID siswa.');

            $card->delete();
        });
    }
}
