<?php

declare(strict_types=1);

namespace App\Http\Controllers\StudentAffairs;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentAffairs\GuardianRequest;
use App\Models\Guardian;
use App\Models\User;
use App\Services\StudentAffairs\GuardianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuardianController extends Controller
{
    public function index(Request $request): View
    {
        $guardians = Guardian::with('students')
            ->when($request->search, fn ($query, $search) => $query->where(fn ($where) => $where
                ->where('name', 'like', "%{$search}%")
                ->orWhere('national_identity_number', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('whatsapp', 'like', "%{$search}%")))
            ->when($request->relationship, fn ($query, $relationship) => $query->whereHas('students', fn ($student) => $student->where('guardian_student.relationship', $relationship)))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('student-affairs.guardians.index', compact('guardians'));
    }


    public function export(Request $request): StreamedResponse
    {
        return response()->streamDownload(function () use ($request): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nama', 'WhatsApp', 'Pekerjaan', 'Jumlah Anak', 'Status']);
            Guardian::withCount('students')
                ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")->orWhere('whatsapp', 'like', "%{$search}%"))
                ->orderBy('name')
                ->chunk(200, function ($guardians) use ($handle): void {
                    foreach ($guardians as $guardian) {
                        fputcsv($handle, [$guardian->name, $guardian->whatsapp, $guardian->occupation, $guardian->students_count, $guardian->is_active ? 'Aktif' : 'Nonaktif']);
                    }
                });
            fclose($handle);
        }, 'data-wali-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function create(): View
    {
        return view('student-affairs.guardians.form', [
            'guardian' => new Guardian,
            'genders' => Gender::cases(),
            'users' => User::whereDoesntHave('guardian')->get(),
        ]);
    }

    public function store(GuardianRequest $request, GuardianService $service): RedirectResponse
    {
        $guardian = $service->save($request->validated());

        return redirect()->route('guardians.show', $guardian)->with('status', 'Data wali berhasil ditambahkan.');
    }

    public function show(Guardian $guardian): View
    {
        $guardian->load('students');

        return view('student-affairs.guardians.show', compact('guardian'));
    }

    public function edit(Guardian $guardian): View
    {
        return view('student-affairs.guardians.form', [
            'guardian' => $guardian,
            'genders' => Gender::cases(),
            'users' => User::whereDoesntHave('guardian')->orWhereKey($guardian->user_id)->get(),
        ]);
    }

    public function update(GuardianRequest $request, Guardian $guardian, GuardianService $service): RedirectResponse
    {
        $service->save($request->validated(), $guardian);

        return redirect()->route('guardians.show', $guardian)->with('status', 'Data wali berhasil diperbarui.');
    }

    public function destroy(Guardian $guardian, GuardianService $service): RedirectResponse
    {
        $service->delete($guardian);

        return redirect()->route('guardians.index')->with('status', 'Data wali dinonaktifkan.');
    }
}
