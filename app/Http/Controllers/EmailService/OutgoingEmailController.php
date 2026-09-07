<?php

declare(strict_types=1);

namespace App\Http\Controllers\EmailService;

use App\Enums\OutgoingEmailStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailService\StoreOutgoingEmailRequest;
use App\Models\OutgoingEmail;
use App\Services\EmailService\OutgoingEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutgoingEmailController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');
        $emails = OutgoingEmail::query()
            ->with('user')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('subject', 'like', "%{$search}%")
                    ->orWhere('to_addresses', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            }))
            ->when(OutgoingEmailStatus::tryFrom($status), fn ($query, $validStatus) => $query->where('status', $validStatus->value))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('email-service.index', compact('emails'));
    }

    public function create(): View
    {
        return view('email-service.create');
    }

    public function store(StoreOutgoingEmailRequest $request, OutgoingEmailService $service): RedirectResponse
    {
        $email = $service->create($request->user(), $request->validated(), $request->file('attachments', []));
        $message = match ($email->status) {
            OutgoingEmailStatus::Draft => 'Draft email berhasil disimpan.',
            OutgoingEmailStatus::Sent => 'Email berhasil dikirim.',
            OutgoingEmailStatus::Failed => 'Email belum berhasil dikirim. Silakan periksa kembali atau coba kirim ulang.',
            default => 'Email masuk dalam antrean pengiriman.',
        };

        return redirect()->route('email-service.show', $email)->with('status', $message);
    }

    public function show(OutgoingEmail $outgoingEmail): View
    {
        return view('email-service.show', ['email' => $outgoingEmail->load('user')]);
    }

    public function resend(Request $request, OutgoingEmail $outgoingEmail, OutgoingEmailService $service): RedirectResponse
    {
        $email = $service->resend($request->user(), $outgoingEmail);
        $message = $email->status === OutgoingEmailStatus::Sent
            ? 'Email berhasil dikirim.'
            : ($email->status === OutgoingEmailStatus::Failed
                ? 'Email belum berhasil dikirim. Silakan periksa kembali atau coba kirim ulang.'
                : 'Email masuk dalam antrean pengiriman.');

        return redirect()->route('email-service.show', $email)->with('status', $message);
    }

    public function sendDraft(Request $request, OutgoingEmail $outgoingEmail, OutgoingEmailService $service): RedirectResponse
    {
        $email = $service->sendDraft($request->user(), $outgoingEmail);
        $message = $email->status === OutgoingEmailStatus::Sent
            ? 'Email berhasil dikirim.'
            : ($email->status === OutgoingEmailStatus::Failed
                ? 'Email belum berhasil dikirim. Silakan periksa kembali atau coba kirim ulang.'
                : 'Email masuk dalam antrean pengiriman.');

        return redirect()->route('email-service.show', $email)->with('status', $message);
    }

    public function attachment(OutgoingEmail $outgoingEmail, int $attachment): StreamedResponse
    {
        $file = ($outgoingEmail->attachments ?? [])[$attachment] ?? null;
        abort_unless(is_array($file) && Storage::disk('local')->exists($file['path'] ?? ''), 404);

        return Storage::disk('local')->download($file['path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
