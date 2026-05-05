<?php

namespace App\Console\Commands;

use App\Http\Controllers\Web\UserDashboardController;
use App\Models\Event;
use App\Models\PortalUser;
use App\Models\StudentPersonalEvent;
use App\Notifications\PortalNotification;
use App\Support\EventCalendarPalette;
use App\Support\PortalDatabaseNotify;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendPortalEventReminders extends Command
{
    protected $signature = 'portal:send-event-reminders {--at= : Optional datetime (Y-m-d H:i:s) treated as now}';

    protected $description = 'Queue 12h and 6h in-app reminders for eligible teacher and personal student events';

    public function handle(): int
    {
        if (! Schema::hasTable('event_reminder_logs') || ! Schema::hasTable('notifications')) {
            return self::SUCCESS;
        }

        $now = $this->option('at')
            ? Carbon::parse($this->option('at'), config('app.timezone'))
            : now();

        $windowMinutes = 15;
        $dashboard = app(UserDashboardController::class);

        foreach ([12, 6] as $offsetHours) {
            $this->dispatchTeacherEvents($dashboard, $now, $offsetHours, $windowMinutes);
            $this->dispatchPersonalEvents($now, $offsetHours, $windowMinutes);
        }

        return self::SUCCESS;
    }

    private function dispatchTeacherEvents(
        UserDashboardController $dashboard,
        Carbon $now,
        int $offsetHours,
        int $windowMinutes
    ): void {
        if (! Schema::hasTable('events')) {
            return;
        }

        $horizon = $now->copy()->addHours(48);
        $events = Event::query()
            ->with(['eventType'])
            ->where('status', 0)
            ->where('start_date', '>', $now->format('Y-m-d H:i:s'))
            ->where('start_date', '<=', $horizon->format('Y-m-d H:i:s'))
            ->orderBy('start_date')
            ->get();

        $morph = (new PortalUser)->getMorphClass();

        foreach ($events as $event) {
            if (! EventCalendarPalette::eventTypeTitleMatchesReminderEligible($event->eventType?->title)) {
                continue;
            }

            $start = Carbon::parse($event->start_date, config('app.timezone'));
            $fireAt = $start->copy()->subHours($offsetHours);
            if ($now->lt($fireAt->copy()->subMinutes($windowMinutes)) || $now->gt($fireAt->copy()->addMinutes($windowMinutes))) {
                continue;
            }

            $eventKey = 'e:'.$event->id;
            $studentIds = $dashboard->portalStudentIdsForTeacherEvent($event);

            foreach ($studentIds as $studentId) {
                if ($this->reminderAlreadySent($eventKey, $studentId, $morph, $offsetHours)) {
                    continue;
                }
                $user = PortalUser::query()->where('role', 2)->find($studentId);
                if (! $user) {
                    continue;
                }

                $label = $offsetHours === 12 ? '12 hours' : '6 hours';
                $when = $start->format('M j, Y g:i A');
                PortalDatabaseNotify::send(
                    $user,
                    new PortalNotification(
                        'Upcoming: '.$event->title,
                        'Your '.$label.' reminder — '.$event->title.' at '.$when.'.',
                        'event_reminder',
                        [
                            'event_id' => $event->id,
                            'offset_hours' => $offsetHours,
                            'starts_at' => $start->toIso8601String(),
                        ]
                    )
                );

                $this->recordReminderSent($eventKey, $studentId, $morph, $offsetHours);
            }
        }
    }

    private function dispatchPersonalEvents(Carbon $now, int $offsetHours, int $windowMinutes): void
    {
        if (! Schema::hasTable('student_personal_events')) {
            return;
        }

        $horizon = $now->copy()->addHours(48);
        $rows = StudentPersonalEvent::query()
            ->where('reminder_eligible', true)
            ->where('start_at', '>', $now->format('Y-m-d H:i:s'))
            ->where('start_at', '<=', $horizon->format('Y-m-d H:i:s'))
            ->get();

        $morph = (new PortalUser)->getMorphClass();

        foreach ($rows as $row) {
            $start = Carbon::parse($row->start_at, config('app.timezone'));
            $fireAt = $start->copy()->subHours($offsetHours);
            if ($now->lt($fireAt->copy()->subMinutes($windowMinutes)) || $now->gt($fireAt->copy()->addMinutes($windowMinutes))) {
                continue;
            }

            $eventKey = 'p:'.$row->id;
            $studentId = (int) $row->student_id;
            if ($studentId < 1) {
                continue;
            }

            if ($this->reminderAlreadySent($eventKey, $studentId, $morph, $offsetHours)) {
                continue;
            }

            $user = PortalUser::query()->where('role', 2)->find($studentId);
            if (! $user) {
                continue;
            }

            $label = $offsetHours === 12 ? '12 hours' : '6 hours';
            $when = $start->format('M j, Y g:i A');
            PortalDatabaseNotify::send(
                $user,
                new PortalNotification(
                    'Upcoming: '.$row->title,
                    'Your '.$label.' reminder — '.$row->title.' at '.$when.'.',
                    'event_reminder',
                    [
                        'student_personal_event_id' => $row->id,
                        'offset_hours' => $offsetHours,
                        'starts_at' => $start->toIso8601String(),
                    ]
                )
            );

            $this->recordReminderSent($eventKey, $studentId, $morph, $offsetHours);
        }
    }

    private function reminderAlreadySent(string $eventKey, int $notifiableId, string $morph, int $offsetHours): bool
    {
        return DB::table('event_reminder_logs')
            ->where('event_key', $eventKey)
            ->where('notifiable_id', $notifiableId)
            ->where('notifiable_type', $morph)
            ->where('offset_hours', $offsetHours)
            ->exists();
    }

    private function recordReminderSent(string $eventKey, int $notifiableId, string $morph, int $offsetHours): void
    {
        DB::table('event_reminder_logs')->insert([
            'event_key' => $eventKey,
            'notifiable_id' => $notifiableId,
            'notifiable_type' => $morph,
            'offset_hours' => $offsetHours,
            'sent_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
