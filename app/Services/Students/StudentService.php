<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\{Student, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};

class StudentService
{
    public function create(array $data, User $actor): Student
    {
        return DB::transaction(function () use ($data, $actor): Student {
            $photo = $data['photo'] ?? null;
            unset($data['photo']);
            $student = Student::create([...$this->clean($data), 'created_by' => $actor->id, 'updated_by' => $actor->id]);
            if ($photo instanceof UploadedFile) $this->storePhoto($student, $photo);
            activity('students')->causedBy($actor)->performedOn($student)->log("Menambahkan siswa {$student->full_name}.");

            return $student;
        });
    }

    public function update(Student $student, array $data, User $actor): Student
    {
        return DB::transaction(function () use ($student, $data, $actor): Student {
            $photo = $data['photo'] ?? null;
            unset($data['photo']);
            $student->update([...$this->clean($data), 'updated_by' => $actor->id]);
            if ($photo instanceof UploadedFile) $this->storePhoto($student, $photo);
            activity('students')->causedBy($actor)->performedOn($student)->log("Memperbarui siswa {$student->full_name}.");

            return $student;
        });
    }

    public function updatePhoto(Student $student, UploadedFile $photo, User $actor): Student
    {
        return DB::transaction(function () use ($student, $photo, $actor): Student {
            $this->storePhoto($student, $photo);
            $student->update(['updated_by' => $actor->id]);
            activity('students')->causedBy($actor)->performedOn($student)->log("Memperbarui foto siswa {$student->full_name}.");

            return $student;
        });
    }

    public function changeStatus(Student $student, string $status, ?string $note, User $actor): Student
    {
        return DB::transaction(function () use ($student, $status, $note, $actor): Student {
            $student->update(['status' => $status, 'updated_by' => $actor->id]);
            activity('students')->causedBy($actor)->performedOn($student)->withProperties(['status' => $status, 'keterangan' => $note])->log("Mengubah status siswa menjadi {$student->status_label}.");

            return $student;
        });
    }

    public function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) $value = trim($value);
            if ($value === '' || in_array(mb_strtoupper((string) $value), config('students.empty_placeholders'), true)) $value = null;
            $data[$key] = $value;
        }

        return $data;
    }

    private function storePhoto(Student $student, UploadedFile $photo): void
    {
        $oldPhoto = $student->photo_path;
        $extension = strtolower($photo->extension() ?: 'jpg');
        $path = $photo->storeAs('student-photos', "{$student->nisn}.{$extension}", 'local');
        $student->update(['photo_path' => $path]);

        if ($oldPhoto && $oldPhoto !== $path) {
            DB::afterCommit(fn () => Storage::disk('local')->delete($oldPhoto));
        }
    }
}
