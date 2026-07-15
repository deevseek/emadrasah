<?php

declare(strict_types=1);
namespace App\Services\StudentAffairs;
use App\Models\Student; use App\Services\ActivityLogger; use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Storage;
class StudentService { public function __construct(private ActivityLogger $logger) {} public function save(array $data, ?Student $student=null, ?UploadedFile $photo=null): Student { return DB::transaction(function() use($data,$student,$photo){ unset($data['photo']); $student ??= new Student; $old=$student->exists?$student->getOriginal():[]; if($photo){ if($student->photo_path) Storage::disk('public')->delete($student->photo_path); $data['photo_path']=$photo->store('student-photos','public'); } $data['is_active']=$data['is_active']??true; $student->fill($data)->save(); $this->logger->log($old?'student.updated':'student.created',$student,$old,$student->getAttributes(),'Data siswa disimpan.'); return $student; }); } }
