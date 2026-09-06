<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Material — {{ $event->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 22px; margin-bottom: 2px; color: #0f172a; text-transform: uppercase; }
        .muted { color: #64748b; font-size: 10px; }
        h2 { font-size: 14px; margin-top: 22px; margin-bottom: 6px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; font-size: 11px; text-transform: uppercase; }
        td.qty { text-align: center; font-weight: bold; width: 60px; }
        td.check { width: 40px; text-align: center; color: #94a3b8; }
        .warn { color: #b45309; font-size: 10px; }
        .ok { color: #15803d; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $event->title }}</h1>
    <p class="muted">
        Lista de material · {{ $event->start_at->format('d/m/Y') }}@if($event->end_at) — {{ $event->end_at->format('d/m/Y') }}@endif
        @if($event->location) · {{ $event->location }} @endif
    </p>

    <h2>Material de las actividades</h2>
    @if(empty($list['items']))
        <p class="muted">Ninguna actividad del evento tiene material con cantidades registrado.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th class="check">✔</th>
                    <th>Material</th>
                    <th style="text-align:center;">Cantidad</th>
                    <th>Inventario del grupo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($list['items'] as $item)
                <tr>
                    <td class="check">☐</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="qty">{{ $item['total_quantity'] }}</td>
                    <td>
                        @if($item['inventory_item_id'] === null)
                            <span class="muted">No es de inventario</span>
                        @elseif($item['enough'])
                            <span class="ok">Disponible ({{ $item['available_quantity'] }} libres en esas fechas)</span>
                        @else
                            <span class="warn">Solo {{ $item['available_quantity'] }} libres — faltan {{ $item['total_quantity'] - $item['available_quantity'] }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(! empty($list['free_text']))
        <h2>Notas de material por actividad</h2>
        <table>
            <thead>
                <tr><th style="width: 35%;">Actividad</th><th>Material (texto libre)</th></tr>
            </thead>
            <tbody>
                @foreach($list['free_text'] as $row)
                <tr>
                    <td>{{ $row['activity'] }}</td>
                    <td>{!! nl2br(e($row['text'])) !!}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
