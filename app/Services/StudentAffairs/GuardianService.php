<?php

declare(strict_types=1);

namespace App\Services\StudentAffairs;

use App\Models\Guardian;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class GuardianService
{
    public function __construct(private ActivityLogger $logger) {}

    public function save(array $data, ?Guardian $guardian = null): Guardian
    {
        return DB::transaction(function () use ($data, $guardian): Guardian {
            $guardian ??= new Guardian;
            $old = $guardian->exists ? $guardian->getOriginal() : [];

            $guardian->fill($data + ['is_active' => $data['is_active'] ?? true]);
            $guardian->save();

            $this->logger->log(
                $old === [] ? 'guardian.created' : 'guardian.updated',
                $guardian,
                $old,
                $guardian->getAttributes(),
                $old === [] ? 'Data wali ditambahkan.' : 'Data wali diperbarui.'
            );

            return $guardian;
        });
    }

    public function delete(Guardian $guardian): void
    {
        DB::transaction(function () use ($guardian): void {
            $old = $guardian->getOriginal();
            $guardian->forceFill(['is_active' => false])->save();
            $guardian->delete();
            $this->logger->log('guardian.deleted', $guardian, $old, [], 'Data wali dinonaktifkan.');
        });
    }
}
