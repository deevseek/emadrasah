<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\BriIntegrationSetting;
use App\Models\User;
use App\Services\Settings\EnvironmentSyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BriSettingService
{
    public function __construct(private EnvironmentSyncService $environment) {}

    public function update(User $actor, array $data, ?UploadedFile $privateKey, ?UploadedFile $publicKey): BriIntegrationSetting
    {
        if (! $this->environment->writable()) throw new RuntimeException('Pengaturan berhasil divalidasi tetapi file .env tidak dapat ditulis oleh user PHP-FPM.');
        $originalEnv = null; $newFiles = []; $oldFiles = [];
        DB::beginTransaction();
        try {
            $setting = BriIntegrationSetting::query()->lockForUpdate()->firstOrNew();
            $changed = [];
            foreach ($data as $key => $value) {
                if (in_array($key, ['client_secret','source_account','registered_account_number'], true) && ($value === null || $value === '')) continue;
                if ($setting->{$key} !== $value) $changed[] = $key;
                $setting->{$key} = $value;
            }
            foreach (['private_key_path'=>$privateKey,'public_key_path'=>$publicKey] as $column => $file) {
                if (! $file) continue;
                $oldFiles[] = $setting->{$column};
                $path = $file->store('keys', 'bri_private');
                if (! $path) throw new RuntimeException('Penyimpanan key BRI gagal.');
                Storage::disk('bri_private')->setVisibility($path, 'private');
                $newFiles[] = $path; $setting->{$column} = $path; $changed[] = $column;
            }
            $setting->updated_by = $actor->id;
            $setting->env_synced_at = now();
            $setting->save();
            $originalEnv = $this->environment->sync(BriEnvironmentMapper::fromSetting($setting));
            Artisan::call('config:clear');
            DB::commit();
            foreach (array_filter($oldFiles) as $old) Storage::disk('bri_private')->delete($old);
            $safe = collect($changed)->map(fn (string $key) => match ($key) {
                'client_secret' => 'Mengubah Client Secret BRI',
                'source_account' => 'Mengubah rekening sumber BRI',
                'registered_account_number' => 'Mengubah rekening terdaftar BRI',
                default => $key,
            })->values()->all();
            activity('bri-settings')->causedBy($actor)->withProperties(['field'=>$safe,'environment'=>$setting->environment,'env_sync'=>'berhasil'])->log('Mengubah konfigurasi integrasi BRI.');
            return $setting;
        } catch (Throwable $exception) {
            DB::rollBack();
            foreach ($newFiles as $path) Storage::disk('bri_private')->delete($path);
            if ($originalEnv !== null) $this->environment->restore($originalEnv);
            throw $exception;
        }
    }
}
