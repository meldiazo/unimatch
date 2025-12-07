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
      <th>Fecha conciliación</th>
      <th>Operación</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($transactions as $transaction)
      @php
        $entry = $transaction->salesEntry;
        $line = $transaction->line;
        $bank = optional($line?->statement?->account?->bank);
        $account = $entry?->account_label ?? $line?->statement?->account?->number ?? '—';
        $amount = $entry?->amount ?? $line?->amount ?? 0;
      @endphp
      <tr>
        <td>{{ $transaction->id }}</td>
        <td>{{ optional($entry?->invoice_date)->format('d/m/Y') ?? '' }}</td>
        <td>{{ $entry?->invoice_number ?? '' }}</td>
        <td>{{ $entry?->nit_ci ?? '' }}</td>
        <td>{{ $entry?->razon_social ?? '' }}</td>
        <td>{{ $entry?->student_name ?? $transaction->student?->full_name ?? '' }}</td>
        <td>{{ $entry?->payment_type ?? '' }}</td>
        <td class="text-right">{{ number_format((float) $amount, 2, ',', '.') }}</td>
        <td>{{ $account }}</td>
        <td>{{ ucfirst($transaction->status ?? 'desconocido') }}</td>
        <td>{{ $entry?->custom_id ?? '' }}</td>
        <td>{{ $entry?->bank_name ?? $bank?->name ?? '' }}</td>
        <td>{{ optional($transaction->matched_at)->format('d/m/Y H:i') ?? '' }}</td>
        <td>{{ $entry?->operation_reference ?? $line?->operation_number ?? '' }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="14" class="text-center text-muted">Aún no hay conciliaciones registradas.</td>
      </tr>
    @endforelse
  </tbody>
</table>
