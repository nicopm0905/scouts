<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Circular — {{ $event->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 16px; margin: 12px 0; }
        .box p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 8px; text-align: left; font-size: 11px; }
        th { background-color: #f1f5f9; }
        .muted { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $event->title }}</h1>
    <p class="muted">Circular informativa para familias</p>

    <div class="box">
        <p><strong>Tipo:</strong> {{ $event->type->label() }}</p>
        <p><strong>Fechas:</strong>
            {{ $event->start_at->format('d/m/Y H:i') }}
            @if($event->end_at) — {{ $event->end_at->format('d/m/Y H:i') }} @endif
        </p>
        @if($event->location)
            <p><strong>Lugar:</strong> {{ $event->location }}</p>
        @endif
        <p><strong>Precio:</strong> {{ $price !== null ? number_format($price, 2, ',', '.').' €' : 'Por confirmar' }}</p>
        @if($event->description)
            <p><strong>Material / información adicional:</strong><br>{{ $event->description }}</p>
        @endif
    </div>

    <p><strong>Enlace de inscripción</strong></p>
    <p class="muted">Cada familia recibe un enlace personal para confirmar la inscripción y subir la autorización firmada. Referencia interna:</p>
    <table>
        <thead>
            <tr><th>Participante</th><th>Enlace de inscripción</th></tr>
        </thead>
        <tbody>
            @forelse($links as $link)
                <tr><td>{{ $link['name'] }}</td><td>{{ $link['url'] }}</td></tr>
            @empty
                <tr><td colspan="2">Aún no hay inscripciones generadas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted">Documento generado el {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>
