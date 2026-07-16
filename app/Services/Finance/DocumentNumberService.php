<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(string $type, ?string $format = null): string
    {
        return DB::transaction(function () use ($type, $format): string {
            $year = (int) now()->format('Y');
            $month = (int) now()->format('m');
            $row = DocumentSequence::query()
                ->where('document_type', $type)
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first();
            if (! $row) {
                $row = DocumentSequence::create([
                    'document_type' => $type,
                    'year' => $year,
                    'month' => $month,
                    'last_number' => 0,
                    'format' => $format ?? strtoupper($type).'/{YEAR}/{MONTH}/{SEQ}',
                ]);
            }
            $row->increment('last_number');

            return strtr($row->format, [
                '{YEAR}' => (string) $year,
                '{MONTH}' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                '{SEQ}' => str_pad((string) $row->last_number, 5, '0', STR_PAD_LEFT),
            ]);
        });
    }
}
