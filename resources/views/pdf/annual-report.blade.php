<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Memoria {{ $report['school_year'] }} — {{ $report['group_name'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.45; }
        h1 { font-size: 22px; margin-bottom: 2px; color: #0f172a; }
        h2 { font-size: 15px; margin-top: 22px; margin-bottom: 6px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        h3 { font-size: 13px; margin: 14px 0 4px; color: #0f172a; }
        .muted { color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 8px; text-align: left; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; }
        td.n { text-align: center; }
        .kpi { display: inline-block; margin-right: 18px; }
        .kpi b { font-size: 18px; color: #0f172a; }
        .bar { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 3px; }
        .bar > span { display: block; height: 100%; background: #16a34a; }
        .plan { page-break-inside: avoid; margin-bottom: 18px; }
    </style>
</head>
<body>
    <h1>Memoria del curso {{ $report['school_year'] }}</h1>
    <p class="muted">{{ $report['group_name'] }} · generada el {{ $report['generated_at'] }} · periodo {{ $report['from'] }} — {{ $report['to'] }}</p>

    <h2>Resumen del grupo</h2>
    <p>
        <span class="kpi"><b>{{ $report['events_total'] }}</b><br>eventos realizados</span>
        @foreach($report['events_by_type'] as $type => $count)
            <span class="kpi"><b>{{ $count }}</b><br>{{ $type }}</span>
        @endforeach
    </p>

    @if(! empty($report['retention']))
        <p>
            <span class="kpi"><b>+{{ $report['retention']['altas'] }}</b><br>altas del curso</span>
            <span class="kpi"><b>&minus;{{ $report['retention']['bajas'] }}</b><br>bajas del curso</span>
            <span class="kpi"><b>{{ $report['retention']['net'] > 0 ? '+' : '' }}{{ $report['retention']['net'] }}</b><br>balance neto</span>
        </p>
    @endif

    <h3>Censo actual</h3>
    @if(empty($report['census']))
        <p class="muted">Sin miembros activos registrados.</p>
    @else
        <table>
            <thead><tr><th>Rama</th><th class="n">Personas</th></tr></thead>
            <tbody>
                @foreach($report['census'] as $row)
                    <tr><td>{{ $row['label'] }}</td><td class="n">{{ $row['count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Planes de rama</h2>
    @if(empty($report['plans']))
        <p class="muted">No hay planes de rama registrados para este curso.</p>
    @endif

    @foreach($report['plans'] as $plan)
        <div class="plan">
            <h3>{{ $plan['branch_label'] }}</h3>
            @if($plan['description'])<p class="muted">{{ $plan['description'] }}</p>@endif

            <p>
                <span class="kpi"><b>{{ $plan['objectives']['percentage'] }}%</b><br>objetivos logrados</span>
                <span class="kpi"><b>{{ $plan['objectives']['logrado'] }}/{{ $plan['objectives']['total'] }}</b><br>logrados</span>
                <span class="kpi"><b>{{ $plan['objectives']['en_curso'] }}</b><br>en curso</span>
                <span class="kpi"><b>{{ $plan['objectives']['pendiente'] }}</b><br>pendientes</span>
                <span class="kpi"><b>{{ $plan['events_count'] }}</b><br>eventos de la rama</span>
                <span class="kpi"><b>{{ $plan['attendance']['avg_present'] !== null ? $plan['attendance']['avg_present'].'%' : '—' }}</b><br>asistencia media ({{ $plan['attendance']['sessions'] }} reuniones)</span>
            </p>
            <div class="bar"><span style="width: {{ $plan['objectives']['percentage'] }}%"></span></div>

            @if(! empty($plan['objectives']['by_area']))
                <table>
                    <thead><tr><th>Ámbito de desarrollo</th><th class="n">Logrados</th><th class="n">Total</th></tr></thead>
                    <tbody>
                        @foreach($plan['objectives']['by_area'] as $area)
                            <tr><td>{{ $area['area'] }}</td><td class="n">{{ $area['logrado'] }}</td><td class="n">{{ $area['total'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(! empty($plan['events']))
                <table>
                    <thead><tr><th>Evento</th><th>Tipo</th><th>Fecha</th></tr></thead>
                    <tbody>
                        @foreach($plan['events'] as $e)
                            <tr><td>{{ $e['title'] }}</td><td>{{ $e['type'] }}</td><td>{{ $e['date'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</body>
</html>
