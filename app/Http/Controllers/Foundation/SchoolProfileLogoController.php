<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\UpdateSchoolProfileLogoRequest;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolProfileLogoController extends Controller
{
    public function __construct(private SchoolProfileService $service) {}
    public function update(UpdateSchoolProfileLogoRequest $request): RedirectResponse { $this->service->replaceLogo($request->user(), $request->file('logo')); return back()->with('status', 'Logo madrasah berhasil diperbarui.'); }
    public function destroy(Request $request): RedirectResponse { $this->service->deleteLogo($request->user()); return back()->with('status', 'Logo madrasah berhasil dihapus.'); }
}
