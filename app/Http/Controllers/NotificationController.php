<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->limit(10)->get()->map(fn (DatabaseNotification $notification): array => [
            'id' => $notification->id,
            'module' => $notification->data['module_label'] ?? 'Sistem',
            'message' => $notification->data['message'] ?? 'Terdapat pembaruan.',
            'actor' => $notification->data['actor'] ?? null,
            'url' => $notification->data['url'] ?? route('dashboard'),
            'read_url' => route('notifications.read', $notification),
            'created_at' => $notification->created_at?->diffForHumans(),
            'read' => $notification->read_at !== null,
        ]);

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse|JsonResponse
    {
        abort_unless($notification->notifiable_type === $request->user()::class && (int) $notification->notifiable_id === $request->user()->id, 404);
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
        }

        return redirect()->to((string) ($notification->data['url'] ?? route('notifications.index')));
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['unread_count' => 0])
            : back()->with('status', 'Semua notifikasi telah ditandai dibaca.');
    }
}
