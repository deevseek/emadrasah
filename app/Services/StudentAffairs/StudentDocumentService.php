<?php

declare(strict_types=1);

namespace App\Services\StudentAffairs;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentDocumentService
{
    public function __construct(private ActivityLogger $logger) {}

    public function upload(Student $student, array $data, UploadedFile $file): StudentDocument
    {
        $path = $file->store('student-documents', 'public');

        try {
            return DB::transaction(function () use ($student, $data, $path): StudentDocument {
                $document = $student->documents()->create($data + [
                    'file_path' => $path,
                    'uploaded_by' => Auth::id(),
                ]);

                $this->logger->log('student.document.uploaded', $document, [], $document->getAttributes(), 'Dokumen siswa diunggah.');

                return $document;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function delete(StudentDocument $document): void
    {
        $path = $document->file_path;

        DB::transaction(function () use ($document): void {
            $old = $document->getOriginal();
            $document->delete();
            $this->logger->log('student.document.deleted', $document, $old, [], 'Dokumen siswa dihapus.');
        });

        Storage::disk('public')->delete($path);
    }
}
