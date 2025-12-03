@extends('layouts.ingresos')

@section('title', 'Conciliaciones revisadas | UniMatch')

@section('panel-role-label', 'Jefatura de contabilidad')
@section('panel-active-menu', 'admin-reconciliations')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">Conciliaciones revisadas</h1>
          <small class="text-muted">Vouchers conciliados, rechazados o marcados como demasía.</small>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-outline card-brand mb-3">
        <div class="card-header">
          <h3 class="card-title mb-0"><i class="fas fa-filter mr-2"></i>Filtros</h3>
        </div>
        <div class="card-body">
          <form method="GET" class="form-row">
            <div class="form-group col-md-3">
              <label for="start_date">Desde</label>
              <input type="date" class="form-control" name="start_date" id="start_date" value="{{ $filters['start_date'] }}">
            </div>
            <div class="form-group col-md-3">
              <label for="end_date">Hasta</label>
              <input type="date" class="form-control" name="end_date" id="end_date" value="{{ $filters['end_date'] }}">
            </div>
            <div class="form-group col-md-3">
              <label for="bank_id">Banco</label>
              <select class="form-control" name="bank_id" id="bank_id">
                <option value="">Todos</option>
                @foreach ($banks as $bank)
                  <option value="{{ $bank->id }}" {{ $filters['bank_id'] == $bank->id ? 'selected' : '' }}>
                    {{ $bank->name }} ({{ $bank->short_code }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="status">Estado</label>
              <select class="form-control" name="status" id="status">
                <option value="">Todos</option>
                @foreach ($availableStatuses as $value => $label)
                  <option value="{{ $value }}" {{ $filters['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3 mt-3">
              <button type="submit" class="btn btn-brand btn-block">Aplicar</button>
            </div>
            <div class="form-group col-md-3 mt-3">
              <a href="{{ route('admin.reconciliations.index') }}" class="btn btn-default btn-block">Limpiar</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card card-outline card-brand">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Conciliaciones</h3>
          <span class="badge badge-info">{{ $vouchers->total() }} registros</span>
        </div>
        <div class="card-body p-0 table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Fecha pago</th>
                <th>Estudiante</th>
                <th>Banco</th>
                <th class="text-right">Monto (Bs)</th>
                <th class="text-right">Demasía (Bs)</th>
                <th>Estado</th>
                <th>Motivo</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($vouchers as $voucher)
                @php
                  $status = strtolower($voucher->status);
                  $badgeClasses = [
                    'conciliado' => 'badge-success',
                    'demasia' => 'badge-warning',
                    'rechazado' => 'badge-danger',
                  ];
                  $badgeClass = $badgeClasses[$status] ?? 'badge-secondary';
                @endphp
                <tr>
                  <td>{{ optional($voucher->paid_at)->format('d/m/Y') ?? '—' }}</td>
                  <td>
                    {{ $voucher->student->full_name ?? '—' }}
                    <small class="d-block text-muted">{{ $voucher->student->code ?? '—' }}</small>
                  </td>
                  <td>{{ optional($voucher->bankAccount?->bank)->name ?? '—' }}</td>
                  <td class="text-right">{{ number_format($voucher->amount, 2, ',', '.') }}</td>
                  <td class="text-right">
                    @if ($status === 'demasia' && $voucher->transaction)
                      {{ number_format(abs($voucher->transaction->difference_amount ?? 0), 2, ',', '.') }}
                    @else
                      —
                    @endif
                  </td>
                  <td><span class="badge {{ $badgeClass }}">{{ $availableStatuses[$status] ?? ucfirst($status) }}</span></td>
                  <td>{{ $voucher->reason ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">No hay conciliaciones con estos filtros.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $vouchers->links() }}
        </div>
      </div>
    </div>
  </section>
@endsection
