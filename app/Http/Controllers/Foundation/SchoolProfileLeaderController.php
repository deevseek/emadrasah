<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\UpdateSchoolProfileLeaderRequest;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\Http\RedirectResponse;

class SchoolProfileLeaderController extends Controller
{
    public function __construct(private SchoolProfileService $service) {}
    public function update(UpdateSchoolProfileLeaderRequest $request): RedirectResponse { $this->service->updateLeader($request->user(), $request->validated()); return back()->with('status', 'Data kepala madrasah berhasil diperbarui.'); }
}
