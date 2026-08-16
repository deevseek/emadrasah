<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BriIntegrationSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean', 'briva_enabled' => 'boolean', 'payroll_enabled' => 'boolean',
            'last_connection_success' => 'boolean', 'last_connection_at' => 'datetime',
            'client_secret' => 'encrypted', 'source_account' => 'encrypted',
        ];
    }
}
