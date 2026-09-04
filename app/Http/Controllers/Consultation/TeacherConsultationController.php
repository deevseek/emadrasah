<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consultation\SendConsultationMessageRequest;
use App\Models\TeacherConsultation;
use App\Services\Consultation\ConsultationService;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\View\View;

class TeacherConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $consultations = TeacherConsultation::query()->where('teacher_user_id', $request->user()->id)
            ->with(['student', 'guardian'])->withCount(['messages as unread_count' => fn ($query) => $query->where('sender_user_id', '!=', $request->user()->id)->whereNull('read_at')])
            ->orderByDesc('last_message_at')->paginate(20);

        return view('consultations.index', compact('consultations'));
    }

    public function show(Request $request, TeacherConsultation $consultation, ConsultationService $service): View
    {
        $this->authorize('view', $consultation);
        $service->markMessagesRead($consultation, $request->user());
        $consultation->load(['student', 'guardian', 'classroom']);
        $messages = $consultation->messages()->with('sender')->latest()->limit(100)->get()->reverse()->values();

        return view('consultations.show', compact('consultation', 'messages'));
    }

    public function store(SendConsultationMessageRequest $request, TeacherConsultation $consultation, ConsultationService $service): RedirectResponse
    {
        $this->authorize('reply', $consultation);
        $service->send($consultation, $request->user(), $request->validated('message'));

        return redirect()->route('consultations.show', $consultation)->with('status', 'Jawaban telah dikirim kepada orang tua/wali.');
    }

    public function messages(Request $request, TeacherConsultation $consultation, ConsultationService $service): JsonResponse
    {
        $this->authorize('view', $consultation);
        $service->markMessagesRead($consultation, $request->user());
        $messages = $consultation->messages()->with('sender')->where('id', '>', (int) $request->query('after'))->orderBy('id')->limit(100)->get()->map(fn ($message) => [
            'id' => $message->id, 'body' => $message->body, 'mine' => $message->sender_user_id === $request->user()->id,
            'sender' => $message->sender->name, 'time' => $message->created_at->timezone('Asia/Jakarta')->format('H:i'),
        ])->all();

        return response()->json(compact('messages'));
    }
}
