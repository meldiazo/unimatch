<table>
  <thead>
    <tr>
      <th>Num Caja</th>
      <th>Fecha pago estudiante</th>
      <th>Fecha recepción</th>
      <th>Num factura</th>
      <th>NIT/CI</th>
      <th>Razón social</th>
      <th>Nombre estudiante</th>
      <th>Tipo de pago</th>
      <th>Monto</th>
      <th>Cuenta</th>
      <th>Estado</th>
      <th>Num operación</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($rows as $row)
      <tr>
        <td>{{ $row['num_caja'] }}</td>
        <td>{{ $row['fecha_pago_estudiante'] }}</td>
        <td>{{ $row['fecha_recepcion'] }}</td>
        <td>{{ $row['num_factura'] }}</td>
        <td>{{ $row['nit_ci'] }}</td>
        <td>{{ $row['razon_social'] }}</td>
        <td>{{ $row['nombre_estudiante'] }}</td>
        <td>{{ $row['tipo_pago'] }}</td>
        <td>{{ $row['monto'] }}</td>
        <td>{{ $row['cuenta'] }}</td>
        <td>{{ $row['estado'] }}</td>
        <td>{{ $row['num_operacion'] }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
