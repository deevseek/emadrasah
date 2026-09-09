<?php

declare(strict_types=1);

namespace App\Services\Hrd;

use App\Contracts\FaceRecognitionService;
use App\Exceptions\AttendanceSecurityException;
use App\Models\{Personnel, PersonnelFaceProfile, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};

class PersonnelFaceEnrollmentService
{
    private const CONSISTENCY_THRESHOLD = .40;

    public function __construct(private FaceRecognitionService $faces) {}

    /** @param array<string,UploadedFile> $samples */
    public function enroll(Personnel $personnel, array $samples, User $actor): PersonnelFaceProfile
    {
        $encoded = [];
        foreach ($samples as $pose => $sample) $encoded[$pose] = $this->faces->encode($sample);
        $this->assertConsistent($encoded);

        return DB::transaction(function () use ($personnel, $samples, $encoded, $actor): PersonnelFaceProfile {
            $old = $personnel->faceProfile()->with('samples')->first();
            $paths = [];
            $primary = array_key_first($samples);
            try {
                foreach ($samples as $pose => $file) $paths[$pose] = $file->store("personnel-faces/{$personnel->id}/".now()->format('YmdHis'), 'local');
                $profile = PersonnelFaceProfile::updateOrCreate(['personnel_id' => $personnel->id], [
                    'provider' => $this->faces->provider(), 'model_name' => $encoded[$primary]['model'],
                    'model_version' => $encoded[$primary]['model_version'], 'status' => 'active',
                    'primary_reference_photo_path' => $paths[$primary], 'registered_at' => now(), 'registered_by' => $actor->id,
                ]);
                $oldPaths = $old?->samples->pluck('photo_path')->push($old->primary_reference_photo_path)->filter()->all() ?? [];
                $profile->samples()->delete();
                foreach ($encoded as $pose => $result) $profile->samples()->create([
                    'photo_path' => $paths[$pose], 'pose' => $pose, 'quality_score' => $result['quality_score'],
                    'embedding' => $result['embedding'], 'embedding_version' => $result['model_version'],
                ]);
                Storage::disk('local')->delete($oldPaths);
                activity('hrd')->causedBy($actor)->performedOn($personnel)->log($old ? 'Mendaftarkan ulang wajah personalia.' : 'Mendaftarkan wajah personalia.');
                return $profile;
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($paths);
                throw $exception;
            }
        }, 3);
    }

    private function assertConsistent(array $encoded): void
    {
        foreach ($encoded as $index => $sample) {
            $scores = [];
            foreach ($encoded as $otherIndex => $other) if ($index !== $otherIndex) $scores[] = $this->cosine($sample['embedding'], $other['embedding']);
            if ($scores === [] || max($scores) < self::CONSISTENCY_THRESHOLD) {
                throw new AttendanceSecurityException('FACE_ENROLLMENT_INCONSISTENT', 'Beberapa foto wajah tidak konsisten. Silakan ulangi pendaftaran wajah.', 422);
            }
        }
    }

    private function cosine(array $left, array $right): float
    {
        $dot = $leftNorm = $rightNorm = 0.0;
        foreach ($left as $index => $value) {
            if (! isset($right[$index]) || ! is_numeric($value) || ! is_numeric($right[$index])) return -1.0;
            $dot += (float) $value * (float) $right[$index];
            $leftNorm += (float) $value ** 2; $rightNorm += (float) $right[$index] ** 2;
        }
        return $leftNorm > 0 && $rightNorm > 0 ? $dot / (sqrt($leftNorm) * sqrt($rightNorm)) : -1.0;
    }

    public function delete(Personnel $personnel, User $actor): void
    {
        DB::transaction(function () use ($personnel, $actor): void {
            $profile = $personnel->faceProfile()->with('samples')->firstOrFail();
            $paths = $profile->samples->pluck('photo_path')->push($profile->primary_reference_photo_path)->filter()->all();
            $profile->update(['status' => 'inactive']); $profile->samples()->delete(); Storage::disk('local')->delete($paths);
            activity('hrd')->causedBy($actor)->performedOn($personnel)->log('Menghapus data wajah personalia.');
        });
    }
}
