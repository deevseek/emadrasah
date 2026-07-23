<?php

declare(strict_types=1);

namespace App\Services\StudentAffairs;

use App\Models\Student;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentService
{
    public function __construct(private ActivityLogger $logger) {}

    public function save(array $data, ?Student $student = null, ?UploadedFile $photo = null): Student
    {
        $storedPath = null;
        $oldPhotoPath = null;

        if ($photo instanceof UploadedFile) {
            $storedPath = $photo->store('student-photos', 'public');
            $data['photo_path'] = $storedPath;
        }

        unset($data['photo']);
        foreach (['phone'] as $phoneField) {
            if (! empty($data[$phoneField])) {
                $data[$phoneField] = preg_replace('/[^0-9+]/', '', (string) $data[$phoneField]);
            }
        }

        try {
            $student = DB::transaction(function () use ($data, $student, &$oldPhotoPath): Student {
                $student ??= new Student;
                $old = $student->exists ? $student->getOriginal() : [];
                $oldPhotoPath = $student->photo_path;

                $student->fill($data + ['is_active' => $data['is_active'] ?? true]);
                $student->save();

                $this->logger->log(
                    $old === [] ? 'student.created' : 'student.updated',
                    $student,
                    $old,
                    $student->getAttributes(),
                    $old === [] ? 'Data siswa ditambahkan.' : 'Data siswa diperbarui.'
                );

                return $student;
            });
        } catch (\Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }

        if ($storedPath !== null && filled($oldPhotoPath) && $oldPhotoPath !== $storedPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return $student;
    }

    public function deleteMany(array $studentIds): int
    {
        return DB::transaction(function () use ($studentIds): int {
            $deleted = 0;

            Student::query()
                ->whereIn('id', $studentIds)
                ->lockForUpdate()
                ->get()
                ->each(function (Student $student) use (&$deleted): void {
                    $old = $student->getOriginal();
                    $student->forceFill(['is_active' => false])->save();
                    $student->delete();

                    $this->logger->log('student.deleted', $student, $old, [], 'Data siswa dinonaktifkan melalui hapus massal.');
                    $deleted++;
                });

            return $deleted;
        });
    }

    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            $old = $student->getOriginal();
            $student->forceFill(['is_active' => false])->save();
            $student->delete();

            $this->logger->log('student.deleted', $student, $old, [], 'Data siswa dinonaktifkan.');
        });
    }
}
