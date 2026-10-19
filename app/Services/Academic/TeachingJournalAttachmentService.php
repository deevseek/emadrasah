<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\TeachingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TeachingJournalAttachmentService
{
    public function sync(TeachingJournal $journal, array $files, ?string $driveUrl, array $removeIds, array &$newPaths): void
    {
        $removeIds = array_unique(array_map('intval', $removeIds));
        $removed = $journal->attachments()->whereIn('id', $removeIds)->get();
        if ($removed->count() !== count($removeIds)) {
            throw ValidationException::withMessages(['remove_assignment_attachments' => 'Lampiran yang dihapus harus berasal dari jurnal ini.']);
        }
        $driveExists = filled($driveUrl) && $journal->attachments()->where('drive_url', $driveUrl)->whereNotIn('id', $removeIds)->exists();
        $addDrive = filled($driveUrl) && ! $driveExists;
        if ($journal->attachments()->count() - $removed->count() + count($files) + (int) $addDrive > 10) {
            throw ValidationException::withMessages(['assignment_files' => 'Maksimal 10 lampiran tugas untuk setiap jurnal.']);
        }
        foreach ($files as $file) {
            $path = $file->store('academic/journal-assignments/'.$journal->id, 'local');
            if (! $path) {
                throw ValidationException::withMessages(['assignment_files' => 'File tugas gagal disimpan. Silakan coba kembali.']);
            }
            $newPaths[] = $path;
            $journal->attachments()->create([
                'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
                'path' => $path, 'size' => $file->getSize(), 'mime_type' => $file->getMimeType(),
            ]);
        }
        if ($addDrive) {
            $journal->attachments()->create(['original_name' => 'File tugas Google Drive', 'drive_url' => $driveUrl]);
        }
        $journal->attachments()->whereIn('id', $removeIds)->delete();
        $paths = $removed->pluck('path')->filter()->all();
        if ($paths) {
            DB::afterCommit(fn () => Storage::disk('local')->delete($paths));
        }
    }
}
