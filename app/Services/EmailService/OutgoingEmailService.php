<?php

declare(strict_types=1);

namespace App\Services\EmailService;

use App\Enums\OutgoingEmailStatus;
use App\Jobs\SendOutgoingEmail;
use App\Models\OutgoingEmail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OutgoingEmailService
{
    public function create(User $actor, array $data, array $files): OutgoingEmail
    {
        $stored = [];
        try {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $extension = strtolower($file->getClientOriginalExtension());
                $path = $file->storeAs('outgoing-emails/'.now()->format('Y/m'), Str::uuid().'.'.$extension, 'local');
                if (! $path) {
                    throw ValidationException::withMessages(['attachments' => 'Lampiran tidak dapat disimpan.']);
                }
                $stored[] = [
                    'path' => $path,
                    'original_name' => Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', basename($file->getClientOriginalName())) ?: 'lampiran.'.$extension, 255, ''),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ];
            }

            $email = DB::transaction(fn (): OutgoingEmail => OutgoingEmail::create([
                'user_id' => $actor->id,
                'to_addresses' => $data['to_addresses'],
                'cc_addresses' => $data['cc_addresses'] ?: null,
                'bcc_addresses' => $data['bcc_addresses'] ?: null,
                'subject' => trim($data['subject']),
                'body' => trim($data['body']),
                'attachments' => $stored ?: null,
                'status' => $data['action'] === 'send' ? OutgoingEmailStatus::Queued : OutgoingEmailStatus::Draft,
            ]));
        } catch (Throwable $exception) {
            foreach ($stored as $file) {
                Storage::disk('local')->delete($file['path']);
            }
            throw $exception;
        }

        activity('pelayanan-email')->causedBy($actor)->performedOn($email)->log('Email pelayanan dibuat.');
        if ($email->status === OutgoingEmailStatus::Queued) {
            SendOutgoingEmail::dispatch($email->id);
        }

        return $email->refresh();
    }

    public function resend(User $actor, OutgoingEmail $email): OutgoingEmail
    {
        $email = $this->queue($email, OutgoingEmailStatus::Failed, 'Hanya email berstatus gagal yang dapat dikirim ulang.');
        activity('pelayanan-email')->causedBy($actor)->performedOn($email)->log('Email pelayanan dikirim ulang.');
        SendOutgoingEmail::dispatch($email->id);

        return $email->refresh();
    }

    public function sendDraft(User $actor, OutgoingEmail $email): OutgoingEmail
    {
        $email = $this->queue($email, OutgoingEmailStatus::Draft, 'Hanya email draft yang dapat dikirim.');
        activity('pelayanan-email')->causedBy($actor)->performedOn($email)->log('Draft email pelayanan dikirim.');
        SendOutgoingEmail::dispatch($email->id);

        return $email->refresh();
    }

    private function queue(OutgoingEmail $email, OutgoingEmailStatus $expected, string $message): OutgoingEmail
    {
        return DB::transaction(function () use ($email, $expected, $message): OutgoingEmail {
            $locked = OutgoingEmail::query()->lockForUpdate()->findOrFail($email->id);
            if ($locked->status !== $expected) {
                throw ValidationException::withMessages(['email' => $message]);
            }
            $locked->update([
                'status' => OutgoingEmailStatus::Queued,
                'error_message' => null,
                'provider_message_id' => null,
                'sent_at' => null,
            ]);

            return $locked;
        });
    }
}
