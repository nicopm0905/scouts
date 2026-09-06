<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $type->label() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.subtitle { color: #64748b; margin-top: 0; }
        p.legal { line-height: 1.6; margin-top: 24px; }
    </style>
</head>
<body>
    <h1>{{ $type->label() }}</h1>
    <p class="subtitle">Documento de consentimiento — {{ $member->full_name }}</p>

    <p class="legal">{{ $type->legalText() }}</p>
</body>
</html>
