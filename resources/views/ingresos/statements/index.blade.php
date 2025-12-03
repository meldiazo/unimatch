@extends('layouts.ingresos')

@section('title', 'UniMatch | Extractos cargados')
@section('panel-active-menu', 'statements')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="h4 mb-0">Extractos cargados</h1>
          <p class="text-muted mb-0 small">Revisa el histórico de líneas importadas como si estuvieras en Excel.</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Ingresos</a></li>
            <li class="breadcrumb-item active">Extractos cargados</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-outline card-info">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Detalle tipo Excel</h3>
          @if ($lines->total() > 0)
            <span class="text-muted small">
              Mostrando {{ $lines->firstItem() }}-{{ $lines->lastItem() }} de {{ $lines->total() }} movimientos
            </span>
          @endif
        </div>
        <div class="card-body p-0 table-responsive">
          <table class="table table-striped table-bordered table-sm mb-0">
            <thead class="bg-light">
              <tr>
                <th>Banco</th>
                <th>Cuenta</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>N° operación</th>
                <th>Descripción</th>
                <th>Referencia</th>
                <th class="text-right">Débito</th>
                <th class="text-right">Crédito</th>
                <th class="text-right">Saldo</th>
                <th>Archivo</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($lines as $line)
                @php
                  $statement = $line->statement;
                  $account = $statement?->account;
                  $bank = $account?->bank;
                  $batch = $statement?->importBatch;
                @endphp
                <tr>
                  <td>{{ $bank?->name ?? '—' }}</td>
                  <td>{{ $account?->account_number ?? '—' }}</td>
                  <td>{{ optional($line->operation_date)->format('d/m/Y') ?? '—' }}</td>
                  <td>{{ $line->transaction_time ? \Illuminate\Support\Carbon::parse($line->transaction_time)->format('H:i:s') : '—' }}</td>
                  <td>{{ $line->operation_number }}</td>
                  <td>{{ $line->description ?: '—' }}</td>
                  <td>{{ trim($line->reference ?? '') !== '' ? $line->reference : '—' }}</td>
                  <td class="text-right">
                    {{ $line->debit_amount !== null ? 'Bs '.number_format((float) $line->debit_amount, 2, ',', '.') : '—' }}
                  </td>
                  <td class="text-right">
                    {{ $line->credit_amount !== null ? 'Bs '.number_format((float) $line->credit_amount, 2, ',', '.') : '—' }}
                  </td>
                  <td class="text-right">
                    {{ $line->running_balance !== null ? 'Bs '.number_format((float) $line->running_balance, 2, ',', '.') : '—' }}
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="small">{{ $batch?->source_name ?? '—' }}</span>
                      @if ($batch?->created_at)
                        <span class="text-muted small">{{ $batch->created_at->format('d/m/Y H:i') }}</span>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center text-muted py-4">
                    Aún no se han importado extractos.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($lines->hasPages())
          <div class="card-footer d-flex justify-content-end">
            {{ $lines->links() }}
          </div>
        @endif
      </div>
    </div>
  </section>
@endsection
