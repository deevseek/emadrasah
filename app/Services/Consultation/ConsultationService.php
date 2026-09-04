<?php

declare(strict_types=1);

namespace App\Services\Consultation;

use App\Events\ConsultationMessageSent;
use App\Models\{Student, TeacherConsultation, TeacherConsultationMessage, User};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ConsultationService
{
    public function conversationForGuardian(User $user, Student $student): TeacherConsultation
    {
        $guardian = $student->guardians()->where('guardian_profiles.user_id', $user->id)->where('guardian_profiles.is_active', true)->first();
        $classroom = $student->activeClassroomMembership?->classroom;
        $teacher = $classroom?->homeroomPersonnel?->user;

        if ($guardian === null) throw new AuthorizationException('Siswa tidak terhubung dengan akun orang tua/wali ini.');
        if ($classroom === null) throw new AuthorizationException('Siswa belum ditempatkan pada kelas aktif.');
        if ($teacher === null || ! $teacher->is_active) throw new AuthorizationException('Wali kelas belum memiliki akun guru aktif.');

        return TeacherConsultation::query()->updateOrCreate([
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ], ['teacher_user_id' => $teacher->id]);
    }

    public function send(TeacherConsultation $consultation, User $sender, string $body): TeacherConsultationMessage
    {
        return DB::transaction(function () use ($consultation, $sender, $body): TeacherConsultationMessage {
            $message = $consultation->messages()->create([
                'sender_user_id' => $sender->id,
                'body' => trim($body),
            ]);
            $consultation->update(['last_message_at' => $message->created_at]);
            ConsultationMessageSent::dispatch($message);

            return $message;
        });
    }

    public function markMessagesRead(TeacherConsultation $consultation, User $reader): void
    {
        $consultation->messages()->where('sender_user_id', '!=', $reader->id)->whereNull('read_at')->update(['read_at' => now()]);
    }
}
