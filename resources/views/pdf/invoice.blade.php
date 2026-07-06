<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        .totals { margin-top: 16px; width: 40%; margin-left: auto; }
        .totals td { border: none; padding: 4px 8px; }
        .header { display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $group['name'] }}</h1>
            <p class="muted">
                @if($group['tax_id']) NIF: {{ $group['tax_id'] }}<br>@endif
                @if($group['address']) {{ $group['address'] }}@endif
                @if($group['postal_code'] || $group['city']) , {{ $group['postal_code'] }} {{ $group['city'] }}@endif
                <br>
                @if($group['email']) {{ $group['email'] }} @endif
                @if($group['phone']) · {{ $group['phone'] }} @endif
            </p>
        </div>
        <div>
            <h1>Factura {{ $invoice->number }}</h1>
            <p class="muted">Fecha: {{ $invoice->date?->format('d/m/Y') }}</p>
        </div>
    </div>

    <p><strong>Cliente:</strong> {{ $invoice->supplier_or_client }}</p>

    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Base</th>
                <th>IVA</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->concept }}</td>
                <td>{{ number_format((float) $invoice->amount, 2, ',', '.') }} €</td>
                <td>{{ number_format((float) $invoice->vat, 2, ',', '.') }} €</td>
                <td>{{ number_format((float) $invoice->amount + (float) $invoice->vat, 2, ',', '.') }} €</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Base imponible</td>
            <td>{{ number_format((float) $invoice->amount, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <td class="muted">IVA</td>
            <td>{{ number_format((float) $invoice->vat, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td><strong>{{ number_format((float) $invoice->amount + (float) $invoice->vat, 2, ',', '.') }} €</strong></td>
        </tr>
    </table>
</body>
</html>
