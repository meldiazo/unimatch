@extends('layouts.ingresos')

@section('title', 'UniMatch | Reporte de conciliaciones')
@section('panel-active-menu', 'reconciliation-report')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="h4 mb-0">Reporte de conciliaciones</h1>
          <p class="text-muted mb-0 small">Consulta las coincidencias confirmadas (conciliadas, en demasía o rechazadas).</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Ingresos</a></li>
            <li class="breadcrumb-item active">Reporte de conciliaciones</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Detalle tipo Excel</h3>
            <div class="d-flex align-items-center">
              @if ($transactions->total() > 0)
                <span class="text-muted small mr-3">Mostrando {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} de {{ $transactions->total() }} registros</span>
              @endif
              <div class="btn-group btn-group-sm" role="group" aria-label="Descargar conciliaciones">
                <a href="{{ route('ingresos.reconciliation-report.export', ['format' => 'pdf']) }}" class="btn btn-default">
                  <i class="fas fa-file-pdf text-danger"></i> PDF
                </a>
                <a href="{{ route('ingresos.reconciliation-report.export', ['format' => 'xls']) }}" class="btn btn-default">
                  <i class="fas fa-file-excel text-success"></i> XLS
                </a>
                <a href="{{ route('ingresos.reconciliation-report.export', ['format' => 'txt']) }}" class="btn btn-default">
                  <i class="fas fa-file-alt"></i> TXT
                </a>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x:auto;">
              <table class="table table-striped table-bordered table-sm mb-0 w-100" style="min-width: 1700px;">
                <thead class="bg-light">
                <tr>
                  <th>N°</th>
                  <th>Fecha reporte</th>
                  <th>N° factura</th>
                  <th>NIT/C.I.</th>
                  <th>Razón social</th>
                  <th>Nombre estudiante</th>
                  <th>Tipo pago</th>
                  <th class="text-right">Monto</th>
                  <th>Cuenta</th>
                  <th>Estado</th>
                  <th>ID</th>
                  <th>Banco</th>
                  <th>Fecha conciliación</th>
                  <th>Operación</th>
                  <th>Asignado por</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($transactions as $transaction)
                  @php
                    $entry = $transaction->salesEntry;
                    $line = $transaction->line;
                    $bank = optional($line?->statement?->account?->bank);
                    $accountLabel = $entry->account_label
                        ?? $line?->statement?->account?->number
                        ?? '—';
                    $statusLabel = $transaction->status ? ucfirst($transaction->status) : 'Sin estado';
                    $statusClass = match ($transaction->status) {
                      'demasia' => 'badge-warning',
                      'rechazado' => 'badge-danger',
                      default => 'badge-success',
                    };
                  @endphp
                  <tr>
                    <td>{{ $transaction->id }}</td>
                    <td>{{ optional($entry?->invoice_date)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $entry?->invoice_number ?? '—' }}</td>
                    <td>{{ $entry?->nit_ci ?? '—' }}</td>
                    <td>{{ $entry?->razon_social ?? '—' }}</td>
                    <td>{{ $entry?->student_name ?? $transaction->student?->full_name ?? '—' }}</td>
                    <td>{{ $entry?->payment_type ?? '—' }}</td>
                    <td class="text-right">{{ 'Bs '.number_format((float) ($entry?->amount ?? $line?->amount ?? 0), 2, ',', '.') }}</td>
                    <td>{{ $accountLabel }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td>{{ $entry?->custom_id ?? '—' }}</td>
                    <td>{{ $entry?->bank_name ?? $bank?->name ?? '—' }}</td>
                    <td>{{ optional($transaction->matched_at)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $entry?->operation_reference ?? $line?->operation_number ?? '—' }}</td>
                    <td>{{ $transaction->matchedBy?->name ?? '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="15" class="text-center text-muted py-4">Aún no hay conciliaciones registradas.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if ($transactions->hasPages())
          <div class="card-footer d-flex justify-content-end">
            {{ $transactions->links() }}
          </div>
        @endif
      </div>
    </div>
  </section>
@endsection
