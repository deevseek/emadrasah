<?php

declare(strict_types=1);
namespace App\Services\StudentAffairs;
use App\Models\Student; use App\Models\StudentDocument; use App\Services\ActivityLogger; use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\Storage;
class StudentDocumentService { public function __construct(private ActivityLogger $logger) {} public function upload(Student $student, array $data, UploadedFile $file): StudentDocument { $data['file_path']=$file->store('student-documents','public'); $data['uploaded_by']=Auth::id(); $document=$student->documents()->create($data); $this->logger->log('student.document.uploaded',$document,[],$document->getAttributes(),'Dokumen siswa diunggah.'); return $document; } public function delete(StudentDocument $document): void { Storage::disk('public')->delete($document->file_path); $document->delete(); $this->logger->log('student.document.deleted',$document,[],[],'Dokumen siswa dihapus.'); } }
