<?php

namespace App\Services\Events;

use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Genera un feed iCalendar (RFC 5545) de solo lectura con los eventos del grupo,
 * para suscripción en Google Calendar (u otro cliente) vía URL tokenizada.
 */
class IcalGenerator
{
    public function generate(Collection $events, string $calendarName = 'MSC Andalucía'): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MSC Andalucia//Calendario de eventos//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape($calendarName),
            'X-WR-TIMEZONE:Europe/Madrid',
        ];

        foreach ($events as $event) {
            $lines = array_merge($lines, $this->eventLines($event));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /** @return array<int, string> */
    private function eventLines(Event $event): array
    {
        $start = $event->start_at;
        $end = $event->end_at ?? $event->start_at->copy()->addHour();

        $lines = [
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@msc-andalucia',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$end->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($event->title),
        ];

        if ($event->location) {
            $lines[] = 'LOCATION:'.$this->escape($event->location);
        }

        $description = trim(($event->type->label() ?? '').($event->description ? ' — '.$event->description : ''));
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:'.$this->escape($description);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private function escape(string $value): string
    {
        $value = str_replace(["\\", "\n", ",", ";"], ["\\\\", "\\n", "\\,", "\\;"], $value);

        return $value;
    }
}
