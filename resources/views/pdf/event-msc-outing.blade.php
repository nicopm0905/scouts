<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha de salida — {{ $event->title }}</title>
    <style>
        /* Márgenes calcados al impreso: hueco arriba para el logo, ancho a la
           izquierda para el texto legal en vertical y sitio abajo para el pie. */
        @page { margin: 53mm 10mm 15mm 28mm; }

        body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; color: #000; }

        table { width: 100%; border-collapse: collapse; }
        td, th { border: 0.75pt solid #3a3a3a; padding: 2px 4px; vertical-align: top; text-align: left; }

        /* Bandas de sección: DATOS SALIDA y ESTRUCTURA en morado, los ámbitos en su color. */
        .band td {
            background: #6C2E91; border-color: #6C2E91;
            color: #fff; font-weight: bold; font-size: 10.5pt;
            padding: 3px 6px; letter-spacing: .2px;
        }
        .scope-band td { color: #fff; font-weight: bold; font-size: 10.5pt; padding: 3px 6px; }

        /* Etiquetas naranjas del bloque de datos y de la rejilla de estructura. */
        .lbl {
            background: #D9531E; border-color: #3a3a3a;
            color: #fff; font-weight: bold; font-size: 8.5pt;
            vertical-align: middle;
        }
        .lbl .sub { display: block; font-weight: normal; font-style: italic; font-size: 6pt; }
        .val { background: #fff; font-size: 9pt; vertical-align: middle; height: 26px; }

        /* Cabecera de las tablas de ámbito: fondo blanco y texto negro, como en el papel. */
        .scope-table th { background: #fff; font-weight: bold; font-size: 9pt; padding: 2px 4px; }
        .scope-table td { height: 36px; font-size: 8pt; }
        .obj { vertical-align: middle; }
        .act { vertical-align: middle; text-align: center; }
        .num { width: 4.7%; font-weight: bold; font-size: 9pt; }

        /* El desplegable de CONTENIDO se dibuja como en el formulario original:
           un recuadro con el texto en el color del ámbito y su botón de flecha. */
        .combo { width: 100%; border-collapse: collapse; }
        .combo td { border: 0.75pt solid #9a9a9a; padding: 2px 3px; font-size: 7.5pt; vertical-align: middle; height: 30px; }
        .combo td.arrow {
            width: 14px; text-align: center; background: #eceff3;
            font-family: DejaVu Sans, sans-serif; font-size: 6pt; color: #333;
        }
        /* Los valores de GRUPO, CURSO y RAMA también son desplegables en el papel. */
        .combo.plain td { border: none; height: auto; font-size: 9pt; padding: 0 3px; }
        .combo.plain td.arrow { border: 0.75pt solid #9a9a9a; }

        /* Rejilla ESTRUCTURA. */
        .grid th { background: #D9531E; border-color: #3a3a3a; color: #fff; font-weight: bold; font-size: 9pt; }
        .grid td { font-size: 7.5pt; }
        .slot {
            background: #D9531E; border-color: #3a3a3a;
            color: #fff; font-weight: bold; font-size: 8.5pt; vertical-align: top;
        }
        .slot .hour { font-weight: normal; font-size: 7.5pt; margin-top: 3px; }
        .slot .hour .box {
            display: inline-block; background: #fff; color: #000;
            border: 0.5pt solid #b0b0b0; padding: 0 5px; font-weight: bold;
        }
        .cell-num { width: 4.5%; font-weight: bold; font-size: 9pt; vertical-align: top; }
        .cell-day { vertical-align: top; }
        .cell-day .act-title { font-weight: bold; }

        .gap { height: 12px; border: none; }
        .page-break { page-break-before: always; }
        .note { font-size: 8.5pt; color: #444; }
    </style>
</head>
<body>
    @include('pdf.partials.msc-letterhead')

    {{-- ============ Página 1: datos de la salida y los tres ámbitos ============ --}}
    <table class="band"><tr><td>DATOS SALIDA</td></tr></table>

    <table>
        {{-- Rejilla de 8 columnas; las filas largas se montan con colspan. --}}
        <tr>
            <td class="lbl" style="width: 10.6%;">RESPONSABLE</td>
            <td class="val" colspan="5">{{ $sheet['coordinator'] }}</td>
            <td class="lbl" style="width: 17.2%;">CURSO</td>
            <td class="val" style="width: 19.5%;">
                <table class="combo plain"><tr><td>{{ $sheet['school_year'] }}</td><td class="arrow">&#9660;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td class="lbl">GRUPO</td>
            <td class="val" colspan="5">
                <table class="combo plain"><tr><td>{{ $sheet['group_name'] }}</td><td class="arrow">&#9660;</td></tr></table>
            </td>
            <td class="lbl">RAMA</td>
            <td class="val">
                <table class="combo plain"><tr><td>{{ implode(', ', $sheet['branches']) }}</td><td class="arrow">&#9660;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td class="lbl">NIÑOS</td>
            <td class="val" style="width: 7.5%;">{{ $sheet['children'] }}</td>
            <td class="lbl" style="width: 16.4%;">RESPONSABLES<span class="sub">Sin titulación</span></td>
            <td class="val" style="width: 5.8%;">{{ $sheet['leaders_untrained'] }}</td>
            <td class="lbl" style="width: 18.1%;">RESPONSABLES<span class="sub">Con titulación</span></td>
            <td class="val" style="width: 5%;">{{ $sheet['leaders_trained'] }}</td>
            <td class="lbl">FECHA ACTIV.</td>
            <td class="val">{{ $sheet['dates'] }}</td>
        </tr>
        <tr>
            <td class="lbl">LUGAR</td>
            <td class="val" colspan="7">{{ $sheet['location'] }}</td>
        </tr>
    </table>

    @foreach($sheet['scopes'] as $scope)
        <table><tr><td class="gap"></td></tr></table>

        <table class="scope-band">
            <tr>
                <td style="background: {{ $scope['color'] }}; border-color: {{ $scope['color'] }};">
                    PLAN DE RAMA: ÁMBITO {{ $scope['label'] }}
                </td>
            </tr>
        </table>

        <table class="scope-table">
            <tr>
                <th style="width: 33%;">CONTENIDO</th>
                <th style="width: 33%;">OBJETIVO</th>
                <th style="width: 29.3%;">ACTIVIDAD</th>
                <th class="num">Nº</th>
            </tr>
            @foreach($scope['rows'] as $row)
                <tr>
                    <td>
                        <table class="combo">
                            <tr>
                                <td style="color: {{ $scope['color'] }};">{{ $row['content'] }}</td>
                                <td class="arrow">&#9660;</td>
                            </tr>
                        </table>
                    </td>
                    <td class="obj">{{ $row['goal'] }}</td>
                    <td class="act">{{ $row['activity'] }}</td>
                    <td class="num">{{ $row['number'] }}</td>
                </tr>
            @endforeach
            {{-- El impreso siempre lleva cuatro filas por ámbito. --}}
            @for($i = 0; $i < $scope['blank_rows']; $i++)
                <tr>
                    <td>
                        <table class="combo">
                            <tr>
                                <td style="color: {{ $scope['color'] }};">Elige uno...</td>
                                <td class="arrow">&#9660;</td>
                            </tr>
                        </table>
                    </td>
                    <td class="obj"></td>
                    <td class="act"></td>
                    <td class="num"></td>
                </tr>
            @endfor
        </table>
    @endforeach

    {{-- ============ Página 2: la rejilla de estructura ============ --}}
    <div class="page-break"></div>

    <table class="band"><tr><td>ESTRUCTURA</td></tr></table>

    @php $days = $sheet['structure']['days']; @endphp

    @if(empty($days))
        <p class="note">
            La salida no tiene fechas asignadas, así que no se puede montar la rejilla de estructura.
            Ponle fecha de inicio y de fin al evento y vuelve a generar la ficha.
        </p>
    @else
        @php
            // El impreso reparte el ancho entre la columna de franjas y los días.
            $dayWidth = round((100 - 11 - (count($days) * 4.5)) / count($days), 2);
        @endphp
        <table class="grid">
            <tr>
                <th style="width: 11%;">&nbsp;</th>
                @foreach($days as $day)
                    <th style="width: 4.5%; text-align: center;">Nº</th>
                    <th style="width: {{ $dayWidth }}%;">{{ $day['label'] }}</th>
                @endforeach
            </tr>
            @foreach($sheet['structure']['rows'] as $row)
                <tr>
                    <td class="slot" style="height: 115px;">
                        {{ $row['label'] }}
                        <div class="hour">De <span class="box">{{ $row['from'] }}</span></div>
                        <div class="hour">a <span class="box">{{ $row['to'] }}</span></div>
                    </td>
                    @foreach($row['cells'] as $cell)
                        <td class="cell-num">
                            @foreach($cell as $act){{ $act['number'] }}@if(! $loop->last)<br>@endif @endforeach
                        </td>
                        <td class="cell-day">
                            @foreach($cell as $act)
                                <span class="act-title">{{ $act['title'] }}:</span>
                                {{ \Illuminate\Support\Str::limit($act['development'] ?? '', 200) }}
                                @if(! $loop->last)<br>@endif
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif

    @if(! empty($sheet['unplaced']))
        <p class="note" style="margin-top: 8px;">
            <strong>Sin colocar en la rejilla:</strong>
            @foreach($sheet['unplaced'] as $item){{ $item['title'] }} ({{ mb_strtolower($item['reason']) }})@if(! $loop->last); @endif @endforeach.
            Asígnales una franja horaria en la ficha de la actividad para que salgan en su hueco.
        </p>
    @endif
</body>
</html>
