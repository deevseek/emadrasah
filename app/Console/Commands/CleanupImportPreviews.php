<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Academic\Imports\ImportPreviewStore;
use Illuminate\Console\Command;

class CleanupImportPreviews extends Command
{
    protected $signature = 'imports:cleanup-previews';
    protected $description = 'Membersihkan berkas dan metadata preview impor yang kedaluwarsa';

    public function handle(ImportPreviewStore $store): int
    {
        $count = $store->cleanupExpired();
        $this->info("{$count} preview kedaluwarsa dibersihkan.");

        return self::SUCCESS;
    }
}
