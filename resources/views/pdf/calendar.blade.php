<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Calendario de Eventos — {{ $branchName }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        p.subtitle { color: #64748b; margin-top: 0; margin-bottom: 16px; font-size: 13px; }
        
        .month-container { page-break-inside: avoid; margin-bottom: 30px; }
        .month-title { font-size: 16px; font-weight: bold; margin-bottom: 8px; color: #0f172a; text-transform: capitalize; }
        
        table.calendar { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.calendar th { background-color: #f1f5f9; padding: 6px; border: 1px solid #cbd5e1; text-align: center; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        table.calendar td { border: 1px solid #cbd5e1; vertical-align: top; height: 80px; padding: 4px; width: 14.28%; }
        
        .day-number { font-weight: bold; color: #64748b; font-size: 11px; margin-bottom: 4px; display: block; text-align: right; }
        .day-today { color: #2563eb; }
        
        .event-item { margin-bottom: 4px; padding: 3px; border-radius: 3px; background-color: #f8fafc; border-left: 3px solid #64748b; font-size: 9px; line-height: 1.2; overflow: hidden; }
        .event-title { font-weight: bold; color: #0f172a; display: block; }
        .event-time { color: #475569; }
        
        .empty-day { background-color: #f8fafc; }
        .muted { color: #64748b; font-size: 10px; margin-top: 24px; text-align: center; }
        
        /* Colores por tipo */
        .type-reunion { border-left-color: #2563eb; }
        .type-salida { border-left-color: #f59e0b; }
        .type-acampada { border-left-color: #ea580c; }
        .type-campamento { border-left-color: #dc2626; }
    </style>
</head>
<body>
    <h1>Calendario de Eventos</h1>
    <p class="subtitle">Rama: {{ $branchName }}</p>

    @if($events->isEmpty())
        <p>No hay eventos registrados para este filtro.</p>
    @else
        @php
            // Agrupar eventos por mes
            $months = [];
            foreach ($events as $event) {
                $monthKey = $event->start_at->format('Y-m');
                if (!isset($months[$monthKey])) {
                    $months[$monthKey] = [
                        'date' => $event->start_at->copy()->startOfMonth(),
                        'events' => []
                    ];
                }
                $months[$monthKey]['events'][] = $event;
            }
        @endphp

        @foreach($months as $monthData)
            @php
                $firstDay = $monthData['date'];
                $daysInMonth = $firstDay->daysInMonth;
                $startDayOfWeek = $firstDay->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
                
                // Organizar eventos por día
                $eventsByDay = [];
                foreach ($monthData['events'] as $event) {
                    $day = $event->start_at->day;
                    $eventsByDay[$day][] = $event;
                }
            @endphp

            <div class="month-container">
                <div class="month-title">{{ $firstDay->translatedFormat('F Y') }}</div>
                <table class="calendar">
                    <thead>
                        <tr>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                            <th>Sábado</th>
                            <th>Domingo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            {{-- Celdas vacías al principio del mes --}}
                            @for($i = 1; $i < $startDayOfWeek; $i++)
                                <td class="empty-day"></td>
                            @endfor

                            {{-- Días del mes --}}
                            @for($day = 1; $day <= $daysInMonth; $day++)
                                @php
                                    $currentCell = $startDayOfWeek + $day - 1;
                                    $isToday = \Carbon\Carbon::today()->format('Y-m-d') === $firstDay->copy()->day($day)->format('Y-m-d');
                                @endphp

                                <td>
                                    <span class="day-number {{ $isToday ? 'day-today' : '' }}">{{ $day }}</span>
                                    
                                    @if(isset($eventsByDay[$day]))
                                        @foreach($eventsByDay[$day] as $event)
                                            <div class="event-item type-{{ strtolower($event->type->name ?? 'otro') }}">
                                                <span class="event-time">{{ $event->start_at->format('H:i') }}</span>
                                                <span class="event-title">{{ $event->title }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </td>

                                {{-- Salto de fila al terminar la semana --}}
                                @if($currentCell % 7 == 0 && $day != $daysInMonth)
                                    </tr><tr>
                                @endif
                            @endfor

                            {{-- Celdas vacías al final del mes --}}
                            @php
                                $endDayOfWeek = $firstDay->copy()->endOfMonth()->dayOfWeekIso;
                            @endphp
                            @if($endDayOfWeek < 7)
                                @for($i = $endDayOfWeek; $i < 7; $i++)
                                    <td class="empty-day"></td>
                                @endfor
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <p class="muted">Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
