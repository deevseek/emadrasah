<?php

declare(strict_types=1);

namespace App\Actions\Hrd;

use App\Contracts\FaceRecognitionService;
use App\Models\Personnel;
use App\Models\PersonnelFaceProfile;
use App\Models\PersonnelFaceSample;
use Throwable;

final class GetFaceRecognitionStatus
{
    public function __construct(private FaceRecognitionService $faces) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        $health = [];

        try {
            $result = $this->faces->health();
            $health = is_array($result) ? $result : [];
        } catch (Throwable) {
            // Status halaman tidak boleh mengganggu Pengaturan HRD ketika provider mati.
        }

        $connected = isset($health['status']) && is_string($health['status'])
            && ! in_array($health['status'], ['offline', 'unavailable'], true);
        $modelLoaded = $health['model_loaded'] ?? null;
        $active = ($health['status'] ?? null) === 'ok' && $modelLoaded === true;
        $registered = PersonnelFaceProfile::query()
            ->where('status', 'active')
            ->whereHas('personnel', fn ($query) => $query->where('is_active', true))
            ->count();
        $samples = PersonnelFaceSample::query()
            ->whereHas('profile', fn ($query) => $query
                ->where('status', 'active')
                ->whereHas('personnel', fn ($personnel) => $personnel->where('is_active', true)))
            ->count();
        $activePersonnel = Personnel::query()->where('is_active', true)->count();

        return [
            'active' => $active,
            'connected' => $connected,
            'provider' => ucfirst($this->faces->provider()),
            'engine' => isset($health['engine']) && is_string($health['engine']) ? $this->engineLabel($health['engine']) : null,
            'model_loaded' => is_bool($modelLoaded) ? $modelLoaded : null,
            'driver' => (string) config('face-recognition.driver', ''),
            'endpoint' => $this->safeEndpoint((string) config('face-recognition.url', '')),
            'timeout' => (int) config('face-recognition.timeout', 10),
            'detail' => $this->safeDetail($health['detail'] ?? null),
            'checked_at' => now()->format('d/m/Y H:i:s'),
            'statistics' => [
                'registered' => $registered,
                'samples' => $samples,
                'unregistered' => max(0, $activePersonnel - $registered),
            ],
        ];
    }

    private function safeEndpoint(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        return is_string($host) ? $host.($port ? ':'.$port : '') : 'Belum dikonfigurasi';
    }

    private function safeDetail(mixed $detail): ?string
    {
        if (! is_string($detail) || trim($detail) === '') {
            return null;
        }

        $sanitized = str($detail)->stripTags()->squish()->toString();
        $token = (string) config('face-recognition.token', '');
        if ($token !== '') {
            $sanitized = str_replace($token, '[disembunyikan]', $sanitized);
        }

        $sanitized = preg_replace('/Authorization\s*:\s*[^\s,;]+(?:\s+[^\s,;]+)?/i', 'Authorization: [disembunyikan]', $sanitized) ?? $sanitized;

        return str($sanitized)->limit(300)->toString();
    }

    private function engineLabel(string $engine): string
    {
        return match (strtolower($engine)) {
            'sface' => 'SFace',
            'yunet' => 'YuNet',
            default => ucfirst($engine),
        };
    }
}
