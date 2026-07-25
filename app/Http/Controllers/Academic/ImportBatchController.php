<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\{ImportBatch, LessonSchedule};
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ImportBatchController extends Controller
{
    public function rollback(ImportBatch $importBatch): RedirectResponse
    {
        DB::transaction(function () use ($importBatch): void {
            $importBatch->teachingAssignments()->where('import_batch_id', $importBatch->id)->update(['is_active' => false]);
            foreach ($importBatch->metadata['updated_schedule_snapshots'] ?? [] as $id => $attributes) {
                unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);
                LessonSchedule::whereKey($id)->update($attributes);
            }
            $updatedIds = array_map('intval', array_keys($importBatch->metadata['updated_schedule_snapshots'] ?? []));
            $importBatch->schedules()->whereNotIn('id', $updatedIds)->delete();
            $importBatch->update(['status' => 'rolled_back', 'finished_at' => now()]);
        });

        return back()->with('status', 'Batch berhasil di-rollback. Perubahan jadwal lama telah dipulihkan.');
    }
}
