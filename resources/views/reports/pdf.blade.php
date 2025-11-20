<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pagos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h3>Reporte de Pagos y Facturación</h3>
    <p>Generado: {{ $generatedAt->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
        <tr>
            <th># Caja</th>
            <th>Fecha pago estudiante</th>
            <th>Fecha recepción</th>
            <th>N° factura</th>
            <th>NIT/CI</th>
            <th>Razón social</th>
            <th>Nombre estudiante</th>
            <th>Tipo de pago</th>
            <th>Monto (Bs)</th>
            <th>Cuenta</th>
            <th>Estado</th>
            <th>N° operación</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['num_caja'] ?? '—' }}</td>
                <td>{{ $row['fecha_pago_estudiante'] ?? '—' }}</td>
                <td>{{ $row['fecha_recepcion'] ?? '—' }}</td>
                <td>{{ $row['num_factura'] ?? '—' }}</td>
                <td>{{ $row['nit_ci'] ?? '—' }}</td>
                <td>{{ $row['razon_social'] ?? '—' }}</td>
                <td>{{ $row['nombre_estudiante'] ?? '—' }}</td>
                <td>{{ $row['tipo_pago'] ?? '—' }}</td>
                <td class="text-right">{{ number_format($row['monto'] ?? 0, 2, ',', '.') }}</td>
                <td>{{ $row['cuenta'] ?? '—' }}</td>
                <td>{{ $row['estado'] ?? '—' }}</td>
                <td>{{ $row['num_operacion'] ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
