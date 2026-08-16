<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\BriIntegrationSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BriSettingService
{
    public function update(User $actor, array $data, ?UploadedFile $privateKey, ?UploadedFile $publicKey): BriIntegrationSetting
    {
        return DB::transaction(function () use ($actor, $data, $privateKey, $publicKey): BriIntegrationSetting {
            $setting = BriIntegrationSetting::query()->firstOrNew();
            $changed = [];
            foreach ($data as $key => $value) {
                if (in_array($key, ['client_secret', 'source_account'], true) && ($value === null || $value === '')) continue;
                if ($setting->{$key} !== $value) $changed[] = $key;
                $setting->{$key} = $value;
            }
            foreach (['private_key_path'=>['file'=>$privateKey,'label'=>'BRI Private Key diperbarui'],'public_key_path'=>['file'=>$publicKey,'label'=>'BRI Public Key diperbarui']] as $column=>$item) {
                if (! $item['file']) continue;
                $old = $setting->{$column};
                $setting->{$column} = $item['file']->store('keys', 'bri_private');
                if ($old) Storage::disk('bri_private')->delete($old);
                $changed[] = $item['label'];
            }
            $setting->updated_by = $actor->id;
            $setting->save();
            $safe = collect($changed)->map(fn ($key) => $key === 'client_secret' ? 'BRI Client Secret diperbarui' : ($key === 'source_account' ? 'Rekening sumber payroll BRI diperbarui' : $key))->values()->all();
            activity('bri-settings')->causedBy($actor)->withProperties(['perubahan'=>$safe])->log('Mengubah konfigurasi integrasi BRI.');
            return $setting;
        });
    }
}
