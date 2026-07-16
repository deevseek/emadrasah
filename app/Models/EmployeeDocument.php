<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeeDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $fillable = ['employee_id', 'type', 'document_number', 'document_date', 'description', 'file_path', 'file_name', 'mime_type', 'file_size', 'uploaded_by'];

    protected function casts(): array
    {
        return ['type' => EmployeeDocumentType::class, 'document_date' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
