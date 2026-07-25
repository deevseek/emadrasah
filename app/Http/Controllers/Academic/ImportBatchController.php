<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;
use App\Http\Controllers\Controller;use App\Models\ImportBatch;use Illuminate\Http\RedirectResponse;use Illuminate\Support\Facades\DB;
class ImportBatchController extends Controller{public function rollback(ImportBatch$importBatch):RedirectResponse{DB::transaction(function()use($importBatch){$importBatch->teachingAssignments()->where('import_batch_id',$importBatch->id)->update(['is_active'=>false]);$importBatch->schedules()->where('import_batch_id',$importBatch->id)->delete();$importBatch->update(['status'=>'rolled_back','finished_at'=>now()]);});return back()->with('status','Batch berhasil di-rollback. Data lama tidak disentuh.');}}
