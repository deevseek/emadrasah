<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'national_identity_number', 'family_card_number', 'name', 'gender', 'birth_place', 'birth_date', 'education', 'occupation', 'monthly_income', 'phone', 'email', 'address', 'is_active'];

    protected function casts(): array
    {
        return ['gender' => Gender::class, 'birth_date' => 'date', 'monthly_income' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot(['id', 'relationship', 'is_primary', 'is_emergency_contact', 'lives_with_student', 'financially_responsible', 'notes'])
            ->withTimestamps();
    }
}
