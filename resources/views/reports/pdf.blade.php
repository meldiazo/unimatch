<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte diario de ingresos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h3>Reporte diario de ingresos</h3>
    <p>Generado: {{ $generatedAt->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
        <tr>
            <th>Nro</th>
            <th>Fecha</th>
            <th>N° factura</th>
            <th>NIT/CI</th>
            <th>Razón social</th>
            <th>Nombre estudiante</th>
            <th>Tipo de pago</th>
            <th>Monto (Bs)</th>
            <th>Cuenta</th>
            <th>Estado</th>
            <th>ID</th>
            <th>Banco</th>
            <th>Fecha registro</th>
            <th>N° operación</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['nro'] ?? '—' }}</td>
                <td>{{ $row['fecha'] ?? '—' }}</td>
                <td>{{ $row['numero_factura'] ?? '—' }}</td>
                <td>{{ $row['nit_ci'] ?? '—' }}</td>
                <td>{{ $row['razon_social'] ?? '—' }}</td>
                <td>{{ $row['nombre_estudiante'] ?? '—' }}</td>
                <td>{{ $row['tipo_pago'] ?? '—' }}</td>
                <td class="text-right">{{ number_format($row['monto'] ?? 0, 2, ',', '.') }}</td>
                <td>{{ $row['cuenta'] ?? '—' }}</td>
                <td>{{ $row['estado'] ?? '—' }}</td>
                <td>{{ $row['custom_id'] ?? '—' }}</td>
                <td>{{ $row['banco'] ?? '—' }}</td>
                <td>{{ $row['fecha_registro'] ?? '—' }}</td>
                <td>{{ $row['operation_reference'] ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
