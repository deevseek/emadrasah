<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Contracts\FaceRecognitionService;
use App\Exceptions\AttendanceSecurityException;
use App\Models\Personnel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class PythonFaceRecognitionService implements FaceRecognitionService
{
    public function encode(UploadedFile $sample): array
    {
        $data = $this->post('/v1/faces/encode', [$sample]);
        return ['embedding' => $data['embedding'], 'quality_score' => (float) $data['quality_score'],
            'model' => (string) $data['model'], 'model_version' => (string) ($data['model_version'] ?? '')];
    }

    public function verify(Personnel $expected, UploadedFile $snapshot, float $threshold): array
    {
        $profile = $expected->faceProfile()->with('samples')->first();
        $references = $profile?->samples->pluck('embedding')->filter()->values()->all() ?? [];
        if (! $profile || $references === []) throw new AttendanceSecurityException('FACE_NOT_ENROLLED', 'Wajah Anda belum terdaftar untuk absensi. Silakan hubungi HRD.', 422);
        $data = $this->post('/v1/faces/verify', [$snapshot], [
            'reference_embeddings' => json_encode($references, JSON_THROW_ON_ERROR), 'threshold' => (string) $threshold,
        ]);
        $matched = ($data['matched'] ?? false) && (float) ($data['confidence'] ?? 0) >= $threshold;
        if ($matched) $profile->update(['last_verified_at' => now()]);
        return ['matched_personnel_id' => $matched ? $expected->id : null, 'confidence' => (float) ($data['confidence'] ?? 0),
            'faces' => (int) ($data['faces'] ?? 0), 'liveness_passed' => $data['liveness_passed'] ?? null];
    }

    public function verifyBurst(Personnel $expected, array $snapshots, float $threshold): array
    {
        $profile = $expected->faceProfile()->with('samples')->first();
        $references = $profile?->samples->pluck('embedding')->filter()->values()->all() ?? [];
        if (! $profile || $references === []) {
            throw new AttendanceSecurityException('FACE_NOT_ENROLLED', 'Wajah Anda belum terdaftar untuk absensi. Silakan hubungi HRD.', 422);
        }
        $data = $this->post('/v1/faces/verify-burst', $snapshots, [
            'reference_embeddings' => json_encode($references, JSON_THROW_ON_ERROR),
            'threshold' => (string) $threshold,
        ]);
        $matched = ($data['matched'] ?? false) && ((float) ($data['confidence'] ?? 0) >= $threshold);
        if ($matched) $profile->update(['last_verified_at' => now()]);
        return [
            'matched_personnel_id' => $matched ? $expected->id : null,
            'confidence' => (float) ($data['confidence'] ?? 0), 'faces' => (int) ($data['faces'] ?? 0),
            'valid_frames' => (int) ($data['valid_frames'] ?? 0), 'matched_frames' => (int) ($data['matched_frames'] ?? 0),
            'frame_confidences' => $data['frame_confidences'] ?? [], 'best_frame_index' => (int) ($data['best_frame_index'] ?? 0),
            'liveness_passed' => $data['liveness_passed'] ?? null,
        ];
    }

    public function health(): array
    {
        if (config('face-recognition.url') === '') return ['status' => 'unavailable', 'model_loaded' => false];
        try { return Http::timeout(config('face-recognition.timeout'))->get(config('face-recognition.url').'/health')->throw()->json(); }
        catch (\Throwable) { return ['status' => 'offline', 'model_loaded' => false]; }
    }

    public function provider(): string { return 'python'; }
    public function livenessSupported(): bool { return false; }

    private function post(string $path, array $images, array $fields = []): array
    {
        try {
            $request = Http::timeout(config('face-recognition.timeout'))->withToken((string) config('face-recognition.token'));
            foreach ($images as $image) {
                $request = $request->attach(count($images) === 1 && $path !== '/v1/faces/verify-burst' ? 'image' : 'images', file_get_contents($image->getRealPath()), $image->getClientOriginalName());
            }
            foreach ($fields as $key => $value) $request = $request->attach($key, $value);
            $response = $request->post(config('face-recognition.url').$path);
            $data = $response->json();
            if (! $response->successful() || ! is_array($data)) throw new AttendanceSecurityException($data['error']['code'] ?? 'FACE_SERVICE_UNAVAILABLE', $data['error']['message'] ?? 'Layanan Face Recognition sedang tidak tersedia. Silakan hubungi HRD.', $response->status() >= 500 ? 503 : 422);
            return $data;
        } catch (AttendanceSecurityException $exception) { throw $exception; }
        catch (\Throwable) { throw new AttendanceSecurityException('FACE_SERVICE_UNAVAILABLE', 'Layanan Face Recognition sedang tidak tersedia. Silakan hubungi HRD.', 503); }
    }
}
