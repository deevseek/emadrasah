<?php

declare(strict_types=1);

namespace App\Services\EmailService;

use App\Models\IncomingEmail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class IncomingEmailService
{
    public function receive(array $data, array $files): IncomingEmail
    {
        $existing = IncomingEmail::query()->where('provider_message_id', $data['message_id'])->first();
        if ($existing) {
            return $existing;
        }

        $stored = [];
        try {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $path = $file->storeAs('incoming-emails/'.now()->format('Y/m'), Str::uuid().'.'.$extension, 'local');
                abort_unless($path, 422, 'Lampiran tidak dapat disimpan.');
                $stored[] = [
                    'path' => $path,
                    'original_name' => Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', basename($file->getClientOriginalName())) ?: 'lampiran.'.$extension, 255, ''),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ];
            }

            return DB::transaction(fn (): IncomingEmail => IncomingEmail::create([
                'provider_message_id' => $data['message_id'],
                'from_address' => strtolower($data['from_address']),
                'from_name' => filled($data['from_name'] ?? null) ? trim($data['from_name']) : null,
                'to_addresses' => array_map('strtolower', $data['to_addresses']),
                'cc_addresses' => empty($data['cc_addresses']) ? null : array_map('strtolower', $data['cc_addresses']),
                'subject' => filled($data['subject'] ?? null) ? trim($data['subject']) : '(Tanpa subjek)',
                'body' => (string) ($data['body'] ?? ''),
                'attachments' => $stored ?: null,
                'received_at' => $data['received_at'],
            ]));
        } catch (Throwable $exception) {
            foreach ($stored as $file) {
                Storage::disk('local')->delete($file['path']);
            }
            throw $exception;
        }
    }

    public function markAsRead(IncomingEmail $email, User $reader): IncomingEmail
    {
        if ($email->read_at === null) {
            $email->update(['read_at' => now(), 'read_by' => $reader->id]);
        }

        return $email->refresh();
    }
}
