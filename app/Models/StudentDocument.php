<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentDocumentType; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StudentDocument extends Model { protected $fillable=['student_id','document_type','document_number','file_path','issued_at','expires_at','notes','uploaded_by','uploaded_at']; protected function casts(): array { return ['document_type'=>StudentDocumentType::class,'issued_at'=>'date','expires_at'=>'date','uploaded_at'=>'datetime']; } public function student(): BelongsTo { return $this->belongsTo(Student::class); } public function uploader(): BelongsTo { return $this->belongsTo(User::class,'uploaded_by'); } }
