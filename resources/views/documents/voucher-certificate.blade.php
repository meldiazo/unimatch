<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Pago</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .logo { font-size: 24px; font-weight: bold; color: #0066cc; }
        .subtitle { color: #666; font-size: 12px; }
        .content { margin-bottom: 30px; }
        .field-row { display: flex; margin-bottom: 12px; }
        .field-label { width: 150px; font-weight: bold; color: #333; }
        .field-value { flex: 1; border-bottom: 1px dotted #999; padding-bottom: 4px; }
        .status { padding: 8px 12px; background-color: #e8f4f8; border-left: 4px solid #0066cc; margin: 15px 0; }
        .footer { text-align: center; font-size: 11px; color: #999; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">UniMatch</div>
        <div class="subtitle">Portal de Pagos y Facturación</div>
    </div>

    <div class="content">
        <h2 style="text-align: center; color: #0066cc;">CONSTANCIA DE PAGO</h2>

        <div class="field-row">
            <div class="field-label">Estudiante:</div>
            <div class="field-value">{{ $voucher->student?->full_name ?? 'N/A' }}</div>
        </div>

        <div class="field-row">
            <div class="field-label">Matrícula:</div>
            <div class="field-value">{{ $voucher->student?->code ?? 'N/A' }}</div>
        </div>

        <div class="field-row">
            <div class="field-label">Banco:</div>
            <div class="field-value">{{ $voucher->bank?->name ?? 'N/A' }}</div>
        </div>

        <div class="field-row">
            <div class="field-label">Número de Operación:</div>
            <div class="field-value">{{ $voucher->operation_number ?? '—' }}</div>
        </div>

        <div class="field-row">
            <div class="field-label">Monto (Bs):</div>
            <div class="field-value">{{ number_format($voucher->amount, 2, ',', '.') }}</div>
        </div>

        <div class="field-row">
            <div class="field-label">Fecha de Pago:</div>
            <div class="field-value">{{ $voucher->paid_at?->format('d/m/Y') ?? '—' }}</div>
        </div>

        <div class="field-row">
            <div class="field-label">Tipo de Pago:</div>
            <div class="field-value">{{ $voucher->payment_type ?? '—' }}</div>
        </div>

        <div class="status">
            <strong>Estado del Voucher:</strong> 
            {{ ucfirst($voucher->status) }}
            @if ($voucher->transaction)
                | <strong>Conciliado:</strong> {{ $voucher->transaction->matched_at?->format('d/m/Y H:i') ?? '—' }}
            @endif
        </div>

        <div class="field-row">
            <div class="field-label">Estado Factura:</div>
            <div class="field-value">{{ ucfirst($voucher->billing_status ?? 'pendiente') }}</div>
        </div>
    </div>

    <div class="footer">
        <p>Esta constancia fue generada automáticamente por el sistema UniMatch.</p>
        <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>