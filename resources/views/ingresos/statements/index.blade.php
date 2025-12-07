@extends('layouts.ingresos')

@section('title', 'UniMatch | Extractos cargados')
@section('panel-active-menu', 'statements')

@section('panel-content')
  @php
    $canManageStatements = auth()->user()->hasRole([
        \App\Models\User::ROLE_ENCARGADO_INGRESOS,
        \App\Models\User::ROLE_JEFE_CONTABILIDAD,
    ]);
  @endphp

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
      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif
      @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('status') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif
      <div class="card card-outline card-info">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Detalle tipo Excel</h3>
          <div class="d-flex align-items-center">
            @if ($lines->total() > 0)
              <span class="text-muted small mr-3">
                Mostrando {{ $lines->firstItem() }}-{{ $lines->lastItem() }} de {{ $lines->total() }} movimientos
              </span>
            @endif
            <div class="btn-group btn-group-sm" role="group" aria-label="Descargar extractos">
              <a href="{{ route('ingresos.statements.export', ['format' => 'pdf']) }}" class="btn btn-default">
                <i class="fas fa-file-pdf text-danger"></i> PDF
              </a>
              <a href="{{ route('ingresos.statements.export', ['format' => 'xls']) }}" class="btn btn-default">
                <i class="fas fa-file-excel text-success"></i> XLS
              </a>
              <a href="{{ route('ingresos.statements.export', ['format' => 'txt']) }}" class="btn btn-default">
                <i class="fas fa-file-alt"></i> TXT
              </a>
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-striped table-bordered table-sm mb-0 w-100" style="min-width: 1000px;">
              <thead class="bg-light">
                <tr>
                  <th>Fecha</th>
                  <th>Hora</th>
                  <th>N°</th>
                  <th>Descripción</th>
                  <th class="text-right">Débito</th>
                  <th class="text-right">Crédito</th>
                  <th class="text-right">Saldo</th>
                  <th>ID</th>
                  <th>Mes fac.</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($lines as $line)
                  @php
                    $statement = $line->statement;
                    $batch = $statement?->importBatch;
                  @endphp
                  @php
                    $lineOldId = old('_line_id');
                    $customIdentifierValue = $lineOldId == $line->id
                        ? old('custom_identifier')
                        : $line->custom_identifier;
                    $billingDateValue = $lineOldId == $line->id
                        ? old('billing_reference_date')
                        : optional($line->billing_reference_date)->format('Y-m-d');
                  @endphp
                  <tr>
                    <td>{{ optional($line->operation_date)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $line->transaction_time ? \Illuminate\Support\Carbon::parse($line->transaction_time)->format('H:i:s') : '—' }}</td>
                    <td>{{ $line->operation_number ?? '—' }}</td>
                    <td>{{ $line->description ?: '—' }}</td>
                    <td class="text-right">
                      {{ $line->debit_amount !== null ? 'Bs '.number_format((float) $line->debit_amount, 2, ',', '.') : '—' }}
                    </td>
                    <td class="text-right">
                      {{ $line->credit_amount !== null ? 'Bs '.number_format((float) $line->credit_amount, 2, ',', '.') : '—' }}
                    </td>
                    <td class="text-right">
                      {{ $line->running_balance !== null ? 'Bs '.number_format((float) $line->running_balance, 2, ',', '.') : '—' }}
                    </td>
                    <td style="width: 160px;">
                      @if ($canManageStatements)
                        <form id="statement-line-{{ $line->id }}" action="{{ route('ingresos.statements.update', $line) }}" method="POST">
                          @csrf
                          @method('PATCH')
                          <input type="hidden" name="_line_id" value="{{ $line->id }}">
                        </form>
                        <input
                          type="text"
                          name="custom_identifier"
                          form="statement-line-{{ $line->id }}"
                          class="form-control form-control-sm"
                          value="{{ $customIdentifierValue }}"
                          placeholder="Ej: F-25"
                        >
                      @else
                        {{ $line->custom_identifier ?? '—' }}
                      @endif
                    </td>
                    <td style="width: 220px;">
                      @if ($canManageStatements)
                        <div class="d-flex align-items-center">
                          <input
                            type="date"
                            name="billing_reference_date"
                            form="statement-line-{{ $line->id }}"
                            class="form-control form-control-sm mr-2"
                            value="{{ $billingDateValue }}"
                            min="{{ optional($line->operation_date)->format('Y-m-d') }}"
                          >
                          <button type="submit" class="btn btn-primary btn-sm" form="statement-line-{{ $line->id }}">
                            Guardar
                          </button>
                        </div>
                      @else
                        {{ optional($line->billing_reference_date)->format('d/m/Y') ?? '—' }}
                      @endif
                      @if ($batch?->source_name)
                        <span class="d-block text-muted small mt-1">{{ $batch->source_name }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                      Aún no se han importado extractos.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
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
