<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\SchoolProfileRequest;
use App\Models\SchoolProfile;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolProfileController extends Controller
{
    public function edit(): View { return view('foundation.school-profile.edit', ['profile' => SchoolProfile::firstOrFail()]); }
    public function update(SchoolProfileRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $profile = SchoolProfile::firstOrFail(); $old = $profile->toArray(); $data = $request->safe()->except(['logo','principal_signature','stamp']);
        foreach (['logo'=>'logo_path','principal_signature'=>'principal_signature_path','stamp'=>'stamp_path'] as $input => $column) {
            if ($request->hasFile($input)) { $data[$column] = $request->file($input)->store('school-profile', 'public'); }
        }
        $profile->update($data); $logger->log('school_profile.updated', $profile, $old, $profile->fresh()->toArray());
        return back()->with('status', 'Profil madrasah diperbarui.');
    }
}
