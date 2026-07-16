<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtaqGroup extends Model
{
    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'name',
        'code',
        'employee_id',
        'btaq_level_id',
        'capacity',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(BtaqGroupStudent::class);
    }
}
