<?php

declare(strict_types=1);

namespace App\Actions\Hrd;

use App\Models\User;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class RestartFaceRecognitionService
{
    public function handle(User $actor): void
    {
        if (config('face-recognition.driver') !== 'python') {
            throw new RuntimeException('Restart hanya tersedia untuk driver Face Recognition Python.');
        }

        $command = trim((string) config('face-recognition.restart_command'));

        if ($command === '') {
            throw new RuntimeException('Perintah restart layanan Face Recognition belum dikonfigurasi.');
        }

        $result = Process::timeout((int) config('face-recognition.restart_timeout', 30))->run($command);

        if ($result->failed()) {
            throw new RuntimeException('Layanan Face Recognition gagal dimulai ulang. Periksa log layanan pada server.');
        }

        activity('hrd')
            ->causedBy($actor)
            ->withProperties(['driver' => 'python'])
            ->log('Memulai ulang layanan Face Recognition Python.');
    }
}
