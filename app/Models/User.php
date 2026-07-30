<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'is_active', 'must_change_password', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function setUsernameAttribute(string $value): void { $this->attributes['username'] = strtolower($value); }
    public function setEmailAttribute(string $value): void { $this->attributes['email'] = strtolower($value); }
    public function getInitialsAttribute(): string { return str($this->name)->explode(' ')->take(2)->map(fn ($word) => str($word)->substr(0, 1))->join('')->upper(); }
    public function getDisplayRoleAttribute(): string { return $this->roles->first()?->display_name ?? $this->roles->first()?->name ?? 'Tanpa role'; }
    public function getHasLoggedInAttribute(): bool { return $this->last_login_at !== null; }
}
