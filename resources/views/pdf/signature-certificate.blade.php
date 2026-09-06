<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Firma digital — {{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 0; }
        p.subtitle { color: #64748b; margin-top: 4px; }
        p.legal { line-height: 1.6; margin-top: 24px; }
        table.audit { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.audit th, table.audit td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.audit th { background: #f1f5f9; width: 30%; }
        .signature-box { margin-top: 24px; border: 1px solid #cbd5e1; padding: 12px; text-align: center; }
        .signature-box img { max-height: 120px; max-width: 100%; }
        .muted { color: #64748b; font-size: 10px; margin-top: 16px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="subtitle">Documento firmado digitalmente por {{ $member->full_name }}</p>

    @if($signature->signable instanceof \App\Models\Consent)
        <p class="legal">{{ $signature->signable->type->legalText() }}</p>
    @endif

    <div class="signature-box">
        <img src="{{ $signature_image }}" alt="Firma">
        <p><strong>{{ $signer_name }}</strong></p>
    </div>

    <p><strong>Declaración de conformidad:</strong> el firmante declara ser el padre/madre/tutor legal
    de {{ $member->full_name }} y presta su conformidad al contenido de este documento firmando digitalmente
    a través del enlace enviado por correo electrónico.</p>

    <table class="audit">
        <tr><th>Firmante</th><td>{{ $signer_name }}</td></tr>
        <tr><th>Fecha y hora de firma</th><td>{{ $signed_at->format('d/m/Y H:i:s') }}</td></tr>
        <tr><th>Dirección IP</th><td>{{ $ip }}</td></tr>
        <tr><th>Hash del documento (SHA-256)</th><td style="word-break: break-all;">{{ $document_hash }}</td></tr>
    </table>

    <p class="muted">
        Certificado de firma electrónica simple generada por la aplicación de gestión del grupo scout,
        conforme al Reglamento (UE) 910/2014 (eIDAS), artículo 25.1. Documento generado el
        {{ now()->format('d/m/Y H:i') }}.
    </p>
</body>
</html>
