<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subjects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subjects\SaveSubjectRequest;
use App\Models\Subject;
use App\Services\Subjects\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View { $this->authorize('viewAny', Subject::class); return view('subjects.index', ['subjects' => Subject::orderBy('sort_order')->orderBy('name')->get()]); }
    public function create(): View { $this->authorize('create', Subject::class); return view('subjects.form'); }
    public function store(SaveSubjectRequest $request, SubjectService $service): RedirectResponse { $service->create($request->user(), $request->validated()); return redirect()->route('subjects.index')->with('success', 'Mata pelajaran berhasil ditambahkan.'); }
    public function edit(Subject $subject): View { $this->authorize('update', $subject); return view('subjects.form', compact('subject')); }
    public function update(SaveSubjectRequest $request, Subject $subject, SubjectService $service): RedirectResponse { $service->update($request->user(), $subject, $request->validated()); return redirect()->route('subjects.index')->with('success', 'Mata pelajaran berhasil diperbarui.'); }
}
