<table>
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Hora</th>
      <th>Número</th>
      <th>Descripción</th>
      <th>Débito</th>
      <th>Crédito</th>
      <th>Saldo</th>
      <th>ID</th>
      <th>Mes facturado</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($lines as $line)
      <tr>
        <td>{{ optional($line->operation_date)->format('d/m/Y') ?? '' }}</td>
        <td>{{ $line->transaction_time ?? '' }}</td>
        <td>{{ $line->operation_number ?? '' }}</td>
        <td>{{ $line->description ?? '' }}</td>
        <td class="text-right">
          {{ $line->debit_amount !== null ? number_format((float) $line->debit_amount, 2, ',', '.') : '' }}
        </td>
        <td class="text-right">
          {{ $line->credit_amount !== null ? number_format((float) $line->credit_amount, 2, ',', '.') : '' }}
        </td>
        <td class="text-right">
          {{ $line->running_balance !== null ? number_format((float) $line->running_balance, 2, ',', '.') : '' }}
        </td>
        <td>{{ $line->custom_identifier ?? '' }}</td>
        <td>{{ optional($line->billing_reference_date)->format('d/m/Y') ?? '' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="9" class="text-center text-muted">No hay extractos disponibles.</td>
      </tr>
    @endforelse
  </tbody>
</table>
