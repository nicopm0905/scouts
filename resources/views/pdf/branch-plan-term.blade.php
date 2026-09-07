<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Plan de rama {{ $sheet['branch_label'] }} — trimestre {{ $sheet['term'] }}</title>
    <style>
        /* A4 apaisado: la tabla de programación tiene diez columnas. */
        @page { margin: 30mm 12mm 16mm 12mm; }

        body { font-family: Arial, Helvetica, sans-serif; font-size: 7.5pt; color: #000; }

        table { width: 100%; border-collapse: collapse; }
        td, th { border: 0.75pt solid #3a3a3a; padding: 2px 4px; vertical-align: top; text-align: left; }

        /* Cabecera del impreso: GRUPO SCOUT / RAMA / CURSO / TRIMESTRE. */
        .head { margin-bottom: 8px; }
        .head td { border: none; padding: 0 6px 3px 0; }
        .head .k {
            background: #6C2E91; color: #fff; font-weight: bold;
            font-size: 9pt; padding: 3px 8px; text-align: center;
        }
        .head .v {
            background: #f0f0f0; border: 0.75pt solid #3a3a3a;
            font-weight: bold; font-size: 9pt; padding: 3px 8px;
        }

        /* Tabla de programación. */
        .plan th {
            background: #6C2E91; border-color: #3a3a3a;
            color: #fff; font-weight: bold; font-size: 7.5pt; text-align: center; padding: 3px;
        }
        .plan .sub th { background: #8B57AC; font-size: 7pt; }
        .plan td { height: 34px; font-size: 7pt; }
        .scope-cell { color: #fff; font-weight: bold; font-size: 7.5pt; text-align: center; vertical-align: middle; }

        /* Calendario del trimestre. */
        .cal { margin-top: 10px; }
        .cal th { background: #6C2E91; border-color: #3a3a3a; color: #fff; text-align: center; font-size: 9pt; padding: 3px; }
        .cal .rowlbl { background: #D9531E; border-color: #3a3a3a; color: #fff; font-weight: bold; width: 9%; font-size: 7.5pt; }
        .cal td { font-size: 6.5pt; text-align: center; }
        .cal td.acts { text-align: left; height: 52px; }

        /* Evaluación del trimestre. */
        .eval { margin-top: 10px; }
        .eval th { background: #D9531E; border-color: #3a3a3a; color: #fff; text-align: center; font-size: 8pt; padding: 3px; }
        .eval td { height: 30px; font-size: 7pt; }

        /* Ficha de cada actividad. */
        .card { margin-bottom: 10px; page-break-inside: avoid; }
        .card td { font-size: 7.5pt; }
        .card .f { background: #D9531E; color: #fff; font-weight: bold; width: 11%; vertical-align: middle; }

        h2 { font-size: 10pt; margin: 12px 0 5px; color: #6C2E91; text-transform: uppercase; letter-spacing: .3px; }
        .empty { color: #555; font-style: italic; font-size: 7.5pt; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @include('pdf.partials.msc-letterhead', ['mscLandscape' => true])

    <table class="head">
        <tr>
            <td class="k" style="width: 12%;">GRUPO SCOUT</td>
            <td class="v" style="width: 26%;">{{ mb_strtoupper($sheet['group_name']) }}</td>
            <td style="width: 4%;"></td>
            <td class="k" style="width: 8%;">RAMA</td>
            <td class="v">{{ mb_strtoupper($sheet['branch_label']) }}</td>
        </tr>
        <tr>
            <td class="k">CURSO</td>
            <td class="v">{{ $sheet['school_year'] }}</td>
            <td></td>
            <td class="k">TRIMESTRE</td>
            <td class="v">{{ $sheet['term_label'] }}</td>
        </tr>
    </table>

    {{-- ---------- Programación: un objetivo por fila ---------- --}}
    <table class="plan">
        <tr>
            <th rowspan="2" style="width: 10%;">Ámbitos</th>
            <th rowspan="2" style="width: 10%;">Líneas</th>
            <th rowspan="2" style="width: 16%;">Contenido</th>
            <th rowspan="2" style="width: 17%;">¿Cómo estamos?</th>
            <th colspan="2" style="width: 19%;">¿Qué queremos conseguir?</th>
            <th colspan="4" style="width: 28%;">Actividades</th>
        </tr>
        <tr class="sub">
            <th style="width: 7%;">Verbo</th>
            <th style="width: 12%;">Complemento</th>
            <th style="width: 6%;">Tipo</th>
            <th style="width: 11%;">Nombre</th>
            <th style="width: 6%;">Encargado</th>
            <th style="width: 5%;">Fecha</th>
        </tr>
        @forelse($sheet['rows'] as $row)
            @php $first = $row['activities'][0] ?? null; @endphp
            <tr>
                <td class="scope-cell" style="background: {{ $row['scope_color'] }};">{{ $row['scope_label'] }}</td>
                <td>{{ $row['line'] }}</td>
                <td>{{ $row['content'] }}</td>
                <td>{{ $row['current_situation'] }}</td>
                <td>{{ $row['goal_verb'] }}</td>
                <td>{{ $row['goal_complement'] }}</td>
                <td>{{ $first['type'] ?? '' }}</td>
                <td>{{ $first['title'] ?? '' }}</td>
                <td>{{ $first['owner'] ?? '' }}</td>
                <td>{{ $first['date'] ?? '' }}</td>
            </tr>
            {{-- Un objetivo puede trabajarse con más de una actividad. --}}
            @foreach(array_slice($row['activities'], 1) as $extra)
                <tr>
                    <td colspan="6"></td>
                    <td>{{ $extra['type'] }}</td>
                    <td>{{ $extra['title'] }}</td>
                    <td>{{ $extra['owner'] }}</td>
                    <td>{{ $extra['date'] }}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="10" class="empty">Este trimestre todavía no tiene objetivos.</td></tr>
        @endforelse
    </table>

    {{-- ---------- Calendario del trimestre ---------- --}}
    <table class="cal">
        <tr><th colspan="{{ count($sheet['calendar']['columns']) + 1 }}">CALENDARIO {{ $sheet['calendar']['year'] }}</th></tr>
        @if(empty($sheet['calendar']['columns']))
            <tr>
                <td class="rowlbl">DÍA</td>
                <td class="acts empty">No hay reuniones ni salidas de la rama en este trimestre.</td>
            </tr>
        @else
            <tr>
                <td class="rowlbl">DÍA</td>
                @foreach($sheet['calendar']['columns'] as $col)<td>{{ $col['day'] }}</td>@endforeach
            </tr>
            <tr>
                <td class="rowlbl">MES</td>
                @foreach($sheet['calendar']['columns'] as $col)<td>{{ $col['month'] }}</td>@endforeach
            </tr>
            <tr>
                <td class="rowlbl">ACTIVIDADES</td>
                @foreach($sheet['calendar']['columns'] as $col)<td class="acts">{{ $col['activities'] }}</td>@endforeach
            </tr>
        @endif
    </table>

    {{-- ---------- Evaluación del trimestre ---------- --}}
    <h2>Evaluación del trimestre</h2>
    <table class="eval">
        <tr>
            <th colspan="2" style="width: 40%;">OBJETIVO</th>
            <th style="width: 28%;">ACTIVIDAD</th>
            <th style="width: 32%;">¿CÓMO HA SALIDO?</th>
        </tr>
        @forelse($sheet['rows'] as $row)
            <tr>
                <td style="width: 10%;">{{ $row['goal_verb'] }}</td>
                <td>{{ $row['goal_complement'] }}</td>
                <td>{{ collect($row['activities'])->pluck('title')->implode(', ') }}</td>
                <td>{{ $row['evaluation'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty">Sin objetivos que evaluar.</td></tr>
        @endforelse
    </table>

    {{-- ---------- Ficha de cada actividad ---------- --}}
    @if(! empty($sheet['activity_sheets']))
        <div class="page-break"></div>
        <h2>Fichas de actividad</h2>

        @foreach($sheet['activity_sheets'] as $card)
            <table class="card">
                <tr>
                    <td class="f">Nombre actividad</td><td colspan="5">{{ $card['title'] }}</td>
                </tr>
                <tr>
                    <td class="f">Encargado</td><td style="width: 22%;">{{ $card['owner'] }}</td>
                    <td class="f">Fecha</td><td style="width: 22%;">{{ $card['date'] }}</td>
                    <td class="f">Sitio</td><td>{{ $card['place'] }}</td>
                </tr>
                <tr>
                    <td class="f">Área</td><td>{{ $card['scope_label'] }}</td>
                    <td class="f">Subárea</td><td>{{ $card['line'] }}</td>
                    <td class="f">Duración</td><td>{{ $card['duration'] }}</td>
                </tr>
                <tr>
                    <td class="f">Objetivo</td><td>{{ $card['goal_verb'] }}</td>
                    <td colspan="4">{{ $card['goal_complement'] }}</td>
                </tr>
                <tr><td class="f">Materiales</td><td colspan="5">{{ $card['materials'] }}</td></tr>
                <tr><td class="f">Desarrollo</td><td colspan="5" style="height: 52px;">{!! nl2br(e($card['development'])) !!}</td></tr>
                <tr><td class="f">Evaluación</td><td colspan="5" style="height: 40px;">{!! nl2br(e($card['evaluation'])) !!}</td></tr>
            </table>
        @endforeach
    @endif
</body>
</html>
