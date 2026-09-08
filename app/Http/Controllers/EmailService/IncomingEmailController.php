<?php

declare(strict_types=1);

namespace App\Http\Controllers\EmailService;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailService\StoreIncomingEmailRequest;
use App\Models\IncomingEmail;
use App\Services\EmailService\IncomingEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncomingEmailController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $filter = (string) $request->query('filter');
        $emails = IncomingEmail::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('subject', 'like', "%{$search}%")
                    ->orWhere('from_address', 'like', "%{$search}%")
                    ->orWhere('from_name', 'like', "%{$search}%");
            }))
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest('received_at')
            ->paginate(20)
            ->withQueryString();

        return view('email-service.inbox', [
            'emails' => $emails,
            'unreadCount' => IncomingEmail::query()->whereNull('read_at')->count(),
        ]);
    }

    public function show(Request $request, IncomingEmail $incomingEmail, IncomingEmailService $service): View
    {
        return view('email-service.incoming-show', ['email' => $service->markAsRead($incomingEmail, $request->user())]);
    }

    public function store(StoreIncomingEmailRequest $request, IncomingEmailService $service): JsonResponse
    {
        $email = $service->receive($request->validated(), $request->file('attachments', []));

        return response()->json(['id' => $email->id], 202);
    }

    public function attachment(IncomingEmail $incomingEmail, int $attachment): StreamedResponse
    {
        $file = ($incomingEmail->attachments ?? [])[$attachment] ?? null;
        abort_unless(is_array($file) && Storage::disk('local')->exists($file['path'] ?? ''), 404);

        return Storage::disk('local')->download($file['path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
