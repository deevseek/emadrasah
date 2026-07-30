<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\UpdateSchoolProfileRequest;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolProfileController extends Controller
{
    public function __construct(private SchoolProfileService $service) {}
    public function show(): View { return view('foundation.school-profile.show', ['profile' => $this->service->current(), 'title' => 'Profil Madrasah']); }
    public function update(UpdateSchoolProfileRequest $request): RedirectResponse { $this->service->update($request->user(), $request->validated()); return back()->with('status', 'Data madrasah berhasil diperbarui.'); }
}
