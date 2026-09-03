<?php

declare(strict_types=1);

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personnel\EnrollOwnFaceRequest;
use App\Services\Hrd\PersonnelFaceEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersonnelProfileController extends Controller
{
    public function show(Request $request): View
    {
        $personnel = $request->user()->personnel()->with(['faceProfile.samples'])->firstOrFail();

        return view('personnel.profile', [
            'title' => 'Profil Saya',
            'personnel' => $personnel,
            'user' => $request->user()->loadMissing('roles'),
        ]);
    }

    public function enroll(EnrollOwnFaceRequest $request, PersonnelFaceEnrollmentService $service): RedirectResponse
    {
        $personnel = $request->user()->personnel()->where('is_active', true)->firstOrFail();
        $service->enroll($personnel, [
            'front' => $request->file('front'),
            'left' => $request->file('left'),
            'right' => $request->file('right'),
        ], $request->user());

        return back()->with('status', 'Wajah Anda berhasil didaftarkan.');
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $profile = $request->user()->personnel?->faceProfile;
        abort_unless($profile && Storage::disk('local')->exists($profile->primary_reference_photo_path), 404);

        return response()->file(Storage::disk('local')->path($profile->primary_reference_photo_path), [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
