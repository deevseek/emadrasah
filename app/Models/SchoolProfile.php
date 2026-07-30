<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolProfile extends Model
{
    protected $fillable = ['name', 'short_name', 'education_level', 'status', 'nsm', 'npsn', 'address', 'village', 'district', 'city', 'province', 'postal_code', 'phone', 'whatsapp', 'email', 'website', 'head_name', 'head_nip', 'logo_path', 'updated_by'];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function getDisplayAddressAttribute(): string
    {
        return collect([$this->address, $this->village, $this->district, $this->city, $this->province, $this->postal_code])->filter()->join(', ');
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return $this->exists ? asset('storage/'.$this->logo_path) : asset($this->logo_path);
    }

    public function getInitialsAttribute(): string
    {
        $initials = str($this->display_name)
            ->squish()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word) => str($word)->substr(0, 1))
            ->join('');

        return str($initials)->upper()->toString();
    }

    public function getCompletenessPercentageAttribute(): int
    {
        $values = [$this->name, $this->education_level, $this->status, $this->nsm, $this->npsn, $this->address, $this->district, $this->city, $this->province, $this->phone ?: $this->whatsapp, $this->head_name, $this->logo_path];

        return (int) round(collect($values)->filter(fn ($value) => filled($value))->count() / count($values) * 100);
    }
}
