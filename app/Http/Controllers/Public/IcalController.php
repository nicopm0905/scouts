<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\Events\IcalGenerator;
use Illuminate\Http\Response;

/** Feed iCalendar de solo lectura del calendario de eventos, vía token personal. */
class IcalController extends Controller
{
    public function show(string $token, IcalGenerator $generator): Response
    {
        User::where('ical_token', $token)->firstOrFail();

        $events = Event::query()->orderBy('start_at')->get();

        $ics = $generator->generate($events);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="calendario-msc-andalucia.ics"',
        ]);
    }
}
