<?php

declare(strict_types=1);

namespace App\Actions\Hrd;

use App\Models\User;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class RestartFaceRecognitionService
{
    public function handle(User $actor): void
    {
        $reference = Str::upper(Str::random(8));

        Log::channel('face-recognition')->info('Permintaan restart layanan diterima.', [
            'reference' => $reference,
            'actor_id' => $actor->getKey(),
            'driver' => (string) config('face-recognition.driver'),
        ]);

        if (config('face-recognition.driver') !== 'python') {
            Log::channel('face-recognition')->warning('Restart ditolak karena driver bukan Python.', [
                'reference' => $reference,
            ]);

            throw new RuntimeException('Restart hanya tersedia untuk driver Face Recognition Python.');
        }

        $command = trim((string) config('face-recognition.restart_command'));

        if ($command === '') {
            Log::channel('face-recognition')->warning('Restart ditolak karena perintah belum dikonfigurasi.', [
                'reference' => $reference,
            ]);

            throw new RuntimeException('Perintah restart layanan Face Recognition belum dikonfigurasi.');
        }

        try {
            $result = Process::timeout((int) config('face-recognition.restart_timeout', 30))->run($command);
        } catch (Throwable $exception) {
            Log::channel('face-recognition')->error('Perintah restart layanan tidak dapat dijalankan.', [
                'reference' => $reference,
                'exception' => $exception::class,
                'message' => $this->sanitize($exception->getMessage()),
            ]);

            throw new RuntimeException($this->failureMessage($reference), previous: $exception);
        }

        if ($result->failed()) {
            $this->logFailure($reference, $result);

            throw new RuntimeException($this->failureMessage($reference));
        }

        Log::channel('face-recognition')->info('Perintah restart layanan berhasil.', [
            'reference' => $reference,
            'exit_code' => $result->exitCode(),
        ]);

        activity('hrd')
            ->causedBy($actor)
            ->withProperties(['driver' => 'python', 'reference' => $reference])
            ->log('Memulai ulang layanan Face Recognition Python.');
    }

    private function logFailure(string $reference, ProcessResult $result): void
    {
        Log::channel('face-recognition')->error('Perintah restart layanan gagal.', [
            'reference' => $reference,
            'exit_code' => $result->exitCode(),
            'stderr' => $this->sanitize($result->errorOutput()),
            'stdout' => $this->sanitize($result->output()),
        ]);
    }

    private function failureMessage(string $reference): string
    {
        return "Layanan Face Recognition gagal dimulai ulang. Periksa storage/logs/face-recognition-*.log dengan referensi {$reference}.";
    }

    private function sanitize(string $value): string
    {
        $token = (string) config('face-recognition.token', '');
        if ($token !== '') {
            $value = str_replace($token, '[disembunyikan]', $value);
        }
        $value = preg_replace('/Authorization\s*:\s*[^\s,;]+(?:\s+[^\s,;]+)?/i', 'Authorization: [disembunyikan]', $value) ?? $value;

        return Str::limit(Str::squish(strip_tags($value)), 1000);
    }
}
