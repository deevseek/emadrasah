<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Role extends \Spatie\Permission\Models\Role
{
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'is_system',
    ];

    protected function casts(): array { return ['is_system' => 'boolean']; }
    public function scopeInDisplayOrder(Builder $query): Builder
    {
        $order=collect(config('roles.system_order',[]))->map(fn(string $name,int $index):string=>"WHEN '".str_replace("'","''",$name)."' THEN {$index}")->implode(' ');
        return $query->orderByRaw("CASE name {$order} ELSE 999 END")->orderBy('display_name');
    }
}
