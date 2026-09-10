<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{RfidAttendanceEvent, RfidDevice, StudentAttendance, User};

class LiveAttendanceService
{
    public function __construct(private AcademicAccessService $access) {}

    public function feed(User $user, int $classroomId, string $date, int $cursor): array
    {
        $this->access->ensureClassroom($user, $classroomId);
        $events = RfidAttendanceEvent::with(['student:id,full_name,nisn', 'attendance:id,status,source,scanned_at'])
            ->where(fn ($query) => $query->where('classroom_id', $classroomId)->orWhereNull('classroom_id'))
            ->whereDate('scanned_at', $date)
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit(50)
            ->get();
        $counts = StudentAttendance::where('classroom_id', $classroomId)
            ->whereDate('attendance_date', $date)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $reader = RfidDevice::query()->where('is_active', true)->where('device_type', 'reader')->latest('last_seen_at')->first(['last_seen_at']);

        return [
            'success' => true,
            'reader' => [
                'online' => $reader?->isOnline() ?? false,
                'last_seen_at' => $reader?->last_seen_at?->toIso8601String(),
            ],
            'cursor' => (int) ($events->last()?->id ?? $cursor),
            'events' => $events->map(fn (RfidAttendanceEvent $event): array => [
                'id' => $event->id,
                'success' => $event->success,
                'student_id' => $event->student_id,
                'student_name' => $event->student?->full_name,
                'nis' => $event->student?->nisn,
                'status' => $event->attendance?->status?->value,
                'status_label' => $event->attendance?->status?->label(),
                'attendance_time' => $event->attendance?->scanned_at?->format('H:i:s'),
                'source' => $event->attendance?->source?->value,
                'scanned_at' => $event->scanned_at->format('H:i:s'),
                'code' => $event->result_code,
                'message' => $event->message,
            ])->values(),
            'counts' => collect(['present', 'sick', 'permitted', 'absent'])
                ->mapWithKeys(fn (string $status): array => [$status => (int) ($counts[$status] ?? 0)]),
        ];
    }
}
