<?php

declare(strict_types=1);

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultation\SendConsultationMessageRequest;
use App\Models\Student;
use App\Services\Consultation\ConsultationService;
use App\Services\ParentPortal\GuardianAccessService;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function show(Request $request, GuardianAccessService $access, ConsultationService $service): View
    {
        $guardian = $access->guardian($request->user());
        $children = $guardian->students()->with('activeClassroomMembership.classroom.homeroomPersonnel.user')->orderBy('full_name')->get();
        $student = $access->student($request->user(), (int) ($request->query('student') ?: $children->first()?->id));
        $student->load('activeClassroomMembership.classroom.homeroomPersonnel.user');
        $consultation = $service->conversationForGuardian($request->user(), $student);
        $service->markMessagesRead($consultation, $request->user());
        $messages = $consultation->messages()->with('sender')->latest()->limit(100)->get()->reverse()->values();

        return view('parent.consultation', compact('children', 'student', 'consultation', 'messages'));
    }

    public function store(SendConsultationMessageRequest $request, Student $student, GuardianAccessService $access, ConsultationService $service): RedirectResponse
    {
        $access->student($request->user(), $student->id);
        $consultation = $service->conversationForGuardian($request->user(), $student);
        $this->authorize('reply', $consultation);
        $service->send($consultation, $request->user(), $request->validated('message'));

        return redirect()->route('parent.consultation.show', ['student' => $student->id])->with('status', 'Pesan telah dikirim kepada wali kelas.');
    }

    public function messages(Request $request, \App\Models\TeacherConsultation $consultation, ConsultationService $service): JsonResponse
    {
        $this->authorize('view', $consultation);
        $service->markMessagesRead($consultation, $request->user());

        return response()->json(['messages' => $this->serializeMessages($consultation, (int) $request->query('after'))]);
    }

    private function serializeMessages(\App\Models\TeacherConsultation $consultation, int $after): array
    {
        return $consultation->messages()->with('sender')->where('id', '>', $after)->orderBy('id')->limit(100)->get()->map(fn ($message) => [
            'id' => $message->id, 'body' => $message->body, 'mine' => $message->sender_user_id === auth()->id(),
            'sender' => $message->sender->name, 'time' => $message->created_at->timezone('Asia/Jakarta')->format('H:i'),
        ])->all();
    }
}
