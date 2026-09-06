<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dossier — {{ $event->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 24px; margin-bottom: 4px; color: #0f172a; text-transform: uppercase; }
        h2 { font-size: 16px; margin-top: 20px; margin-bottom: 8px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        h3 { font-size: 14px; margin-top: 15px; margin-bottom: 6px; color: #475569; }
        .box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 16px; margin: 12px 0; background-color: #f8fafc; }
        .box p { margin: 6px 0; font-size: 12px;}
        table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: avoid; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 11px; vertical-align: top; }
        th { background-color: #f1f5f9; }
        .muted { color: #64748b; font-size: 10px; }
        .activity-card { border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 10px; border-radius: 4px; page-break-inside: avoid; }
        .activity-header { font-weight: bold; font-size: 12px; margin-bottom: 4px; color: #0f172a; }
        .badge { display: inline-block; padding: 2px 6px; background: #e2e8f0; color: #334155; border-radius: 4px; font-size: 9px; text-transform: uppercase; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>{{ $event->title }}</h1>
    <p class="muted">Dossier de Programación</p>

    <div class="box">
        <table style="border: none; margin: 0; background-color: transparent;">
            <tr>
                <td style="border: none; padding: 4px;"><strong>Tipo:</strong> {{ $event->type->label() }}</td>
                <td style="border: none; padding: 4px;"><strong>Fechas:</strong> {{ $event->start_at->format('d/m/Y H:i') }} @if($event->end_at) — {{ $event->end_at->format('d/m/Y H:i') }} @endif</td>
            </tr>
            <tr>
                <td style="border: none; padding: 4px;"><strong>Lugar:</strong> {{ $event->location ?? '-' }}</td>
                <td style="border: none; padding: 4px;"><strong>Pueblo/Ciudad:</strong> {{ $event->city ?? '-' }}</td>
            </tr>
            <tr>
                <td style="border: none; padding: 4px;"><strong>Ambientación:</strong> {{ $event->theme ?? '-' }}</td>
                <td style="border: none; padding: 4px;"><strong>Coordinador/a:</strong> {{ $event->coordinator ?? '-' }}</td>
            </tr>
            <tr>
                <td style="border: none; padding: 4px;"><strong>Eucaristía:</strong> {{ $event->eucharist ? 'Sí' : 'No' }}</td>
                <td style="border: none; padding: 4px;"><strong>Marcha/Ruta:</strong> {{ $event->hike ? 'Sí' : 'No' }}</td>
            </tr>
        </table>
    </div>

    <h2>Objetivos de la Actividad</h2>
    @php
        $allObjectives = collect();
        foreach($event->activities as $act) {
            foreach($act->objectives as $obj) {
                if(!$allObjectives->contains('id', $obj->id)) {
                    $allObjectives->push($obj);
                }
            }
        }
        $groupedObjectives = $allObjectives->groupBy('development_area');
    @endphp

    @if($groupedObjectives->isEmpty())
        <p class="muted">No hay objetivos vinculados a las actividades de este evento.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Ámbito</th>
                    <th style="width: 30%;">Contenido</th>
                    <th style="width: 50%;">Objetivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedObjectives as $area => $objectives)
                    @foreach($objectives as $index => $obj)
                    <tr>
                        @if($index === 0)
                            <td rowspan="{{ $objectives->count() }}"><strong>{{ $area ?: 'Sin ámbito' }}</strong></td>
                        @endif
                        <td>{{ $obj->content }}</td>
                        <td>{{ $obj->description }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Material total del evento</h2>
    @if(empty($materialTotals))
        <p class="muted">No hay material con cantidades registrado en las actividades.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 55%;">Material</th>
                    <th style="width: 15%; text-align:center;">Cantidad</th>
                    <th style="width: 30%;">Inventario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($materialTotals as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td style="text-align:center; font-weight:bold;">{{ $item['total_quantity'] }}</td>
                    <td>
                        @if($item['inventory_item_id'] === null)
                            <span class="muted">No es de inventario</span>
                        @elseif($item['enough'])
                            Disponible
                        @else
                            <span style="color:#b45309;">Faltan {{ $item['total_quantity'] - $item['available_quantity'] }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>

    <h2>Programación de Actividades</h2>
    
    @php
        $activitiesByDay = $event->activities->groupBy('day_number');
    @endphp

    @if($activitiesByDay->isEmpty())
        <p class="muted">No hay actividades programadas en este evento.</p>
    @else
        @foreach($activitiesByDay as $day => $activitiesForDay)
            <h3 style="background-color: #e2e8f0; padding: 6px 10px; border-radius: 4px;">{{ $day ?: 'Día no especificado' }}</h3>
            
            @php
                $activitiesBySlot = $activitiesForDay->groupBy('time_slot');
            @endphp
            
            @foreach(['Mañana', 'Tarde I', 'Tarde II', 'Noche', ''] as $slot)
                @if($activitiesBySlot->has($slot))
                    <h4 style="margin-bottom: 4px; color: #0f172a;">{{ $slot ?: 'Sin franja' }}</h4>
                    @foreach($activitiesBySlot->get($slot) as $act)
                        <div class="activity-card">
                            <div class="activity-header">
                                Actividad {{ $act->activity_number ? $act->activity_number . ' - ' : '' }}{{ $act->title }} 
                                <span class="badge">{{ $act->duration_minutes }} min</span>
                            </div>
                            
                            @if($act->objectives_text)
                                <div><strong>Objetivos específicos:</strong><br>{!! nl2br(e($act->objectives_text)) !!}</div><br>
                            @endif
                            
                            @if($act->development)
                                <div><strong>Desarrollo:</strong><br>{!! nl2br(e($act->development)) !!}</div><br>
                            @endif
                            
                            @if($act->materials_text)
                                <div><strong>Materiales:</strong><br>{!! nl2br(e($act->materials_text)) !!}</div>
                            @elseif($act->materials->count() > 0)
                                <div><strong>Materiales:</strong> 
                                    {{ $act->materials->map(function($m) { return $m->name . ' (x' . $m->quantity . ')'; })->join(', ') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            @endforeach
        @endforeach
    @endif
</body>
</html>
