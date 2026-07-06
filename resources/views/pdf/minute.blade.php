<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $minute->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .subtitle { color: #64748b; margin-top: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        td, th { padding: 4px 6px; text-align: left; vertical-align: top; }
        .meta td { border-bottom: 1px solid #e2e8f0; }
        .meta td:first-child { font-weight: bold; width: 140px; }
        .attendees li { margin-bottom: 2px; }
        .item { margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; }
        .item h3 { font-size: 13px; margin: 0 0 4px; }
        .item .label { font-weight: bold; color: #475569; }
        .footer { margin-top: 30px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>{{ $minute->title }}</h1>
    <p class="subtitle">
        {{ $minute->type === 'actas_asamblea' ? 'Acta de asamblea' : 'Acta de consejo' }}
    </p>

    <table class="meta">
        <tr>
            <td>Fecha</td>
            <td>{{ $minute->held_on->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Lugar</td>
            <td>{{ $minute->location ?? '—' }}</td>
        </tr>
        <tr>
            <td>Redactado por</td>
            <td>{{ $minute->creator?->name ?? '—' }}</td>
        </tr>
    </table>

    <h3>Asistentes</h3>
    @if ($minute->attendees->isEmpty())
        <p>Sin asistentes registrados.</p>
    @else
        <ul class="attendees">
            @foreach ($minute->attendees as $attendee)
                <li>{{ $attendee->full_name }}</li>
            @endforeach
        </ul>
    @endif

    <h3>Orden del día</h3>
    @if ($minute->items->isEmpty())
        <p>Sin puntos registrados.</p>
    @else
        @foreach ($minute->items as $index => $item)
            <div class="item">
                <h3>{{ $index + 1 }}. {{ $item->topic }}</h3>
                @if ($item->discussion)
                    <p><span class="label">Desarrollo:</span> {{ $item->discussion }}</p>
                @endif
                @if ($item->agreement)
                    <p><span class="label">Acuerdo:</span> {{ $item->agreement }}</p>
                @endif
            </div>
        @endforeach
    @endif

    <p class="footer">Documento generado automáticamente por la plataforma de gestión — MSC Andalucía.</p>
</body>
</html>
