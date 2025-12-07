<table>
  <thead>
    <tr>
      <th>N°</th>
      <th>Fecha</th>
      <th>N° factura</th>
      <th>NIT/C.I.</th>
      <th>Razón social</th>
      <th>Nombre estudiante</th>
      <th>Tipo pago</th>
      <th>Monto</th>
      <th>Cuenta</th>
      <th>Estado</th>
      <th>ID</th>
      <th>Banco</th>
      <th>Fecha registro</th>
      <th>Operación</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($entries as $entry)
      <tr>
        <td>{{ $entry->legacy_number ?? '' }}</td>
        <td>{{ optional($entry->invoice_date)->format('d/m/Y') ?? '' }}</td>
        <td>{{ $entry->invoice_number ?? '' }}</td>
        <td>{{ $entry->nit_ci ?? '' }}</td>
        <td>{{ $entry->razon_social ?? '' }}</td>
        <td>{{ $entry->student_name ?? '' }}</td>
        <td>{{ $entry->payment_type ?? '' }}</td>
        <td class="text-right">{{ number_format((float) $entry->amount, 2, ',', '.') }}</td>
        <td>{{ $entry->account_label ?? '' }}</td>
        <td>{{ $entry->state_label ?? '' }}</td>
        <td>{{ $entry->custom_id ?? '' }}</td>
        <td>{{ $entry->bank_name ?? '' }}</td>
        <td>{{ optional($entry->recorded_date)->format('d/m/Y') ?? '' }}</td>
        <td>{{ $entry->operation_reference ?? '' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="14" class="text-center text-muted">No hay registros disponibles.</td>
      </tr>
    @endforelse
  </tbody>
</table>
