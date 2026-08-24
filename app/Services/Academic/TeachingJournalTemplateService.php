<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{TeachingJournalTemplate, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
use ZipArchive;

class TeachingJournalTemplateService
{
    public function upload(string $name, UploadedFile $file, User $user): TeachingJournalTemplate
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true || $zip->locateName('word/document.xml') === false) {
            throw ValidationException::withMessages(['template' => 'Berkas harus berupa dokumen DOCX yang valid.']);
        }
        $document = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        if (! str_contains($document, '${no}') || ! str_contains($document, '${uraian_mengajar}')) {
            throw ValidationException::withMessages(['template' => 'Template wajib memiliki satu baris tabel dengan placeholder ${no} dan ${uraian_mengajar}.']);
        }

        return DB::transaction(function () use ($name, $file, $user): TeachingJournalTemplate {
            TeachingJournalTemplate::query()->update(['is_active' => false]);
            $path = $file->store('teaching-journal-templates', 'local');
            $template = TeachingJournalTemplate::create(['name' => $name, 'original_name' => $file->getClientOriginalName(), 'path' => $path, 'is_active' => true, 'uploaded_by' => $user->id]);
            activity('akademik')->causedBy($user)->performedOn($template)->log('Mengunggah template jurnal mengajar');
            return $template;
        });
    }

    public function absolutePath(TeachingJournalTemplate $template): string
    {
        abort_unless(Storage::disk('local')->exists($template->path), 404, 'Berkas template tidak ditemukan.');
        return Storage::disk('local')->path($template->path);
    }
}
