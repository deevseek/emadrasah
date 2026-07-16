<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\TeachingJournalStoreRequest;
use App\Models\TeachingAssignment;
use App\Models\TeachingJournal;
use App\Services\Attendance\TeachingJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TeachingJournalController extends Controller
{
    public function index(): View
    {
        $query = TeachingJournal::query()
            ->with('employee', 'classroom', 'subject')
            ->when(! auth()->user()->can('teaching-journals.view'), function ($query): void {
                $query->where('employee_id', auth()->user()->employee?->id);
            });

        return view('attendance.journals.index', [
            'journals' => $query->latest('journal_date')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('attendance.journals.form', [
            'journal' => null,
            'assignments' => $this->assignmentsForCurrentUser(),
        ]);
    }

    public function store(TeachingJournalStoreRequest $request, TeachingJournalService $service): RedirectResponse
    {
        $validated = $request->validated();
        $journal = $service->save(TeachingAssignment::query()->findOrFail($validated['teaching_assignment_id']), $validated, $validated['students'] ?? []);

        if ($validated['status'] === 'submitted') {
            $service->submit($journal);
        }

        return redirect()->route('teaching-journals.show', $journal)->with('status', 'Jurnal tersimpan.');
    }

    public function edit(TeachingJournal $teachingJournal): View
    {
        $this->authorizeOwnership($teachingJournal);

        return view('attendance.journals.form', [
            'journal' => $teachingJournal,
            'assignments' => $this->assignmentsForCurrentUser(),
        ]);
    }

    public function update(TeachingJournalStoreRequest $request, TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    {
        $this->authorizeOwnership($teachingJournal);
        $validated = $request->validated();
        $journal = $service->save(TeachingAssignment::query()->findOrFail($validated['teaching_assignment_id']), $validated + ['id' => $teachingJournal->id], $validated['students'] ?? []);

        return redirect()->route('teaching-journals.show', $journal)->with('status', 'Jurnal diperbarui.');
    }

    public function show(TeachingJournal $teachingJournal): View
    {
        $this->authorizeOwnership($teachingJournal);

        return view('attendance.journals.show', [
            'journal' => $teachingJournal->load('employee', 'classroom', 'subject', 'students'),
        ]);
    }

    public function print(TeachingJournal $teachingJournal): View
    {
        $this->authorizeOwnership($teachingJournal);

        return view('attendance.journals.show', [
            'journal' => $teachingJournal->load('employee', 'classroom', 'subject', 'students'),
            'print' => true,
        ]);
    }

    public function submit(TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    {
        $this->authorizeOwnership($teachingJournal);
        $service->submit($teachingJournal);

        return back()->with('status', 'Jurnal dikirim.');
    }

    public function verify(TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    {
        $service->verify($teachingJournal);

        return back()->with('status', 'Jurnal diverifikasi.');
    }

    public function reject(TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    {
        request()->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $service->reject($teachingJournal, request('rejection_reason'));

        return back()->with('status', 'Jurnal dikembalikan untuk diperbaiki.');
    }

    private function assignmentsForCurrentUser()
    {
        return TeachingAssignment::query()
            ->with('classroom', 'subject')
            ->where('is_active', true)
            ->when(! auth()->user()->can('teaching-journals.view'), function ($query): void {
                $query->where('employee_id', auth()->user()->employee?->id);
            })
            ->get();
    }

    private function authorizeOwnership(TeachingJournal $journal): void
    {
        abort_if(
            ! auth()->user()->can('teaching-journals.view') && $journal->employee_id !== auth()->user()->employee?->id,
            403,
            'Anda tidak berwenang membuka jurnal guru lain.'
        );
    }
}
