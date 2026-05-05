<?php

namespace App\Support;

use App\Models\Event;
use App\Models\StudentPersonalEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

final class FullCalendarEventPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function fromTeacherEvent(Event $event, bool $useSemanticStudentColors): array
    {
        $event->loadMissing(['eventType', 'classrooms', 'batches']);

        $allDay = (bool) ($event->getAttribute('all_day') ?? true);
        $start = Carbon::parse($event->start_date);
        $end = Carbon::parse($event->end_date);

        if ($allDay) {
            $startDay = $start->copy()->startOfDay();
            $endDay = $end->copy()->startOfDay();
            if ($endDay->lt($startDay)) {
                $endDay = $startDay->copy();
            }
            $startStr = $startDay->format('Y-m-d');
            $endStr = $endDay->copy()->addDay()->format('Y-m-d');
        } else {
            $startStr = $start->toIso8601String();
            $endStr = $end->toIso8601String();
        }

        $colors = $useSemanticStudentColors
            ? EventCalendarPalette::colorsForStudentView($event->eventType)
            : EventCalendarPalette::colorsForTeacherView($event->eventType);

        $ext = [
            'calendar' => 'et'.(int) $event->event_type_id,
            'event_type_id' => $event->event_type_id,
            'event_type_title' => $event->eventType?->title,
            'classrooms' => $event->classrooms->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'batches' => $event->batches->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'status' => (int) $event->status,
            'description' => $event->description,
            'color_code' => $event->eventType?->color_code,
            'all_day' => $allDay ? 1 : 0,
            'source' => 'teacher',
        ];

        if (Schema::hasColumn('events', 'all_classrooms')) {
            $ext['all_classrooms'] = (int) ($event->getAttribute('all_classrooms') ?? 0);
        }
        if (Schema::hasColumn('events', 'all_batches')) {
            $ext['all_batches'] = (int) ($event->getAttribute('all_batches') ?? 0);
        }

        $row = [
            'id' => 'db-'.$event->id,
            'title' => $event->title,
            'start' => $startStr,
            'end' => $endStr,
            'allDay' => $allDay,
            'extendedProps' => $ext,
        ];

        return array_merge($row, $colors);
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromStudentPersonalEvent(StudentPersonalEvent $event): array
    {
        $allDay = (bool) ($event->all_day ?? true);
        $start = Carbon::parse($event->start_at);
        $end = Carbon::parse($event->end_at);

        if ($allDay) {
            $startDay = $start->copy()->startOfDay();
            $endDay = $end->copy()->startOfDay();
            if ($endDay->lt($startDay)) {
                $endDay = $startDay->copy();
            }
            $startStr = $startDay->format('Y-m-d');
            $endStr = $endDay->copy()->addDay()->format('Y-m-d');
        } else {
            $startStr = $start->toIso8601String();
            $endStr = $end->toIso8601String();
        }

        $colors = EventCalendarPalette::personalEventColors();

        $ext = [
            'calendar' => 'personal',
            'source' => 'personal',
            'event_type_title' => 'Personal',
            'description' => $event->description,
            'all_day' => $allDay ? 1 : 0,
            'student_personal_event_id' => $event->id,
            'reminder_eligible' => $event->reminder_eligible ? 1 : 0,
        ];

        $row = [
            'id' => 'personal-'.$event->id,
            'title' => $event->title,
            'start' => $startStr,
            'end' => $endStr,
            'allDay' => $allDay,
            'extendedProps' => $ext,
        ];

        return array_merge($row, $colors);
    }
}
