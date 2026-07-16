<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\SchoolProfileRequest;
use App\Services\ActivityLogger;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SchoolProfileController extends Controller
{
    public function edit(SchoolProfileService $profiles): View
    {
        return view('foundation.school-profile.edit', ['profile' => $profiles->current()]);
    }

    public function update(SchoolProfileRequest $request, SchoolProfileService $profiles, ActivityLogger $logger): RedirectResponse
    {
        $profile = $profiles->current();
        $old = $profile->toArray();
        $data = $request->safe()->except(['logo', 'principal_signature', 'stamp']);
        $newFiles = [];
        $oldFiles = [];

        foreach (['logo' => 'logo_path', 'principal_signature' => 'principal_signature_path', 'stamp' => 'stamp_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $newFiles[$column] = $request->file($input)->store('school-profile', 'public');
                $data[$column] = $newFiles[$column];
                if ($profile->{$column}) {
                    $oldFiles[] = $profile->{$column};
                }
            }
        }

        try {
            DB::transaction(function () use ($profile, $data, $logger, $old): void {
                $profile->update($data);
                $logger->log('school_profile.updated', $profile, $old, $profile->fresh()->toArray());
            });
        } catch (Throwable $throwable) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $throwable;
        }

        foreach ($oldFiles as $path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('status', 'Profil madrasah berhasil diperbarui.');
    }
}
