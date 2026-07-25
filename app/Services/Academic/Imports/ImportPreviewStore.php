<?php

declare(strict_types=1);

namespace App\Services\Academic\Imports;

use App\Exceptions\ImportPreviewException;
use App\Models\ImportPreview;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportPreviewStore
{
    private const DIRECTORY = 'academic-import-previews';

    public function create(string $type, int $userId, ?int $academicYearId, ?int $semesterId, string $originalFilename, array $payload): ImportPreview
    {
        $token = (string) Str::uuid();
        $path = self::DIRECTORY.'/'.$token.'.json';
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if (! Storage::disk('local')->put($path, $json)) {
            throw new ImportPreviewException('storage', 'Data preview tidak dapat disimpan.');
        }

        try {
            return ImportPreview::create([
                'token' => $token,
                'type' => $type,
                'user_id' => $userId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'original_filename' => $originalFilename,
                'payload_path' => $path,
                'row_count' => count($payload['rows'] ?? []),
                'checksum' => hash('sha256', $json),
                'expires_at' => now()->addHours(2),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function locate(string $token, string $type, int $userId, bool $lock = false): ImportPreview
    {
        $query = ImportPreview::query()->where('token', $token);
        if ($lock) {
            $query->lockForUpdate();
        }
        $preview = $query->first();
        if (! $preview) {
            throw new ImportPreviewException('not_found', 'Data preview tidak ditemukan.');
        }
        if ((int) $preview->user_id !== $userId) {
            throw new ImportPreviewException('forbidden', 'Preview ini bukan milik Anda.');
        }
        if ($preview->type !== $type) {
            throw new ImportPreviewException('wrong_type', 'Jenis preview tidak sesuai.');
        }
        if ($preview->isConsumed()) {
            throw new ImportPreviewException('consumed', 'Data dari preview ini sudah diproses.');
        }
        if ($preview->isExpired()) {
            throw new ImportPreviewException('expired', 'Preview telah kedaluwarsa.');
        }

        return $preview;
    }

    public function read(string $token, string $type, int $userId, bool $lock = false): array
    {
        return $this->readPreview($this->locate($token, $type, $userId, $lock));
    }

    public function readPreview(ImportPreview $preview): array
    {
        if (! Storage::disk('local')->exists($preview->payload_path)) {
            throw new ImportPreviewException('not_found', 'Berkas preview tidak ditemukan.');
        }
        $contents = Storage::disk('local')->get($preview->payload_path);
        if (! is_string($contents) || ! hash_equals((string) $preview->checksum, hash('sha256', $contents))) {
            throw new ImportPreviewException('checksum', 'Integritas data preview tidak valid.');
        }

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    public function consume(ImportPreview $preview): void
    {
        $preview->forceFill(['consumed_at' => now()])->save();
        Storage::disk('local')->delete($preview->payload_path);
    }

    public function cleanupExpired(): int
    {
        $count = 0;
        ImportPreview::query()->whereNull('consumed_at')->where('expires_at', '<=', now())->chunkById(100, function ($previews) use (&$count): void {
            foreach ($previews as $preview) {
                \DB::transaction(function () use ($preview, &$count): void {
                    $locked = ImportPreview::query()->whereKey($preview->id)->lockForUpdate()->first();
                    if (! $locked || $locked->isConsumed() || ! $locked->isExpired()) {
                        return;
                    }
                    Storage::disk('local')->delete($locked->payload_path);
                    $locked->delete();
                    $count++;
                });
            }
        });

        return $count;
    }
}
