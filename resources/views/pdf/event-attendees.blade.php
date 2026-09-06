<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de asistentes — {{ $event->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 0; }
        p.subtitle { color: #64748b; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background-color: #f1f5f9; }
        .muted { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $event->title }}</h1>
    <p class="subtitle">
        {{ $event->type->label() }} ·
        {{ $event->start_at->format('d/m/Y') }}
        @if($event->end_at) — {{ $event->end_at->format('d/m/Y') }} @endif
        @if($event->location) · {{ $event->location }} @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rama</th>
                <th>Teléfono de contacto</th>
                <th>Datos médicos</th>
                @if($showPayment ?? false)
                    <th>Pago</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['role_label'] }}</td>
                    <td>{{ $row['phone'] ?? '—' }}</td>
                    <td>{{ $row['health_summary'] }}</td>
                    @if($showPayment ?? false)
                        <td>{{ $row['payment_label'] ?? '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ ($showPayment ?? false) ? 5 : 4 }}">No hay inscritos confirmados todavía.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted">Documento generado el {{ now()->format('d/m/Y H:i') }} — Uso interno, contiene datos sensibles.</p>
</body>
</html>
