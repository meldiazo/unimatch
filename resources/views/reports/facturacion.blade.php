@extends('layouts.ingresos')

@section('title', 'Reporte de Pagos | UniMatch')

@php
  $user = auth()->user();
  $roleLabel = $user && $user->role ? ucfirst(str_replace('_', ' ', $user->role)) : 'Encargado de ingresos';
@endphp

@section('panel-role-label', $roleLabel)
@section('panel-active-menu', 'report')

@section('panel-wrapper-attrs')
  data-user-name="{{ $user->name }}"
  data-user-role="{{ $roleLabel }}"
  data-user-email="{{ $user->email }}"
@endsection

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-8">
          <h1 class="m-0">Reporte de Pagos y Facturación</h1>
          <small class="text-muted">Datos consolidados desde vouchers recibidos.</small>
        </div>
        <div class="col-sm-4 text-sm-right">
          <a href="{{ route('reports.pagos', array_merge(request()->all(), ['export' => 'txt'])) }}"
            class="btn btn-sm btn-outline-brand mr-2">
            <i class="fas fa-file-alt mr-1"></i> Descargar TXT
          </a>
          <a href="{{ route('reports.pagos', array_merge(request()->all(), ['export' => 'pdf'])) }}"
            class="btn btn-sm btn-outline-brand mr-2">
            <i class="fas fa-file-download mr-1"></i> Descargar PDF
          </a>
          <span class="badge badge-light">Generado: {{ $generatedAt->format('d/m/Y H:i') }}</span>
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
              <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] }}"
                class="form-control">
            </div>
            <div class="form-group col-md-3">
              <label for="end_date">Hasta</label>
              <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] }}" class="form-control">
            </div>
            <div class="form-group col-md-3">
              <label for="bank_id">Banco</label>
              <select name="bank_id" id="bank_id" class="form-control">
                <option value="">Todos</option>
                @foreach ($banks as $bank)
                  <option value="{{ $bank->id }}" {{ $filters['bank_id'] == $bank->id ? 'selected' : '' }}>
                    {{ $bank->name }} ({{ $bank->short_code }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="billing_status">Estado facturación</label>
              <select name="billing_status" id="billing_status" class="form-control">
                <option value="">Todos</option>
                <option value="facturado" {{ $filters['billing_status'] === 'facturado' ? 'selected' : '' }}>Facturado
                </option>
                <option value="pendiente" {{ $filters['billing_status'] === 'pendiente' ? 'selected' : '' }}>Pendiente
                </option>
              </select>
            </div>
            <div class="form-group col-md-3 mt-3">
              <button type="submit" class="btn btn-brand btn-block">Aplicar filtros</button>
            </div>
            <div class="form-group col-md-3 mt-3">
              <a href="{{ route('reports.pagos') }}" class="btn btn-default btn-block">Limpiar</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card card-outline card-brand">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0"><i class="fas fa-table mr-2"></i>Vista tipo Excel</h3>
          <span class="badge badge-info">Datos actuales</span>
        </div>
        <div class="card-body p-0 table-responsive">
          <table class="table table-sm table-striped table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Nro</th>
                <th>Fecha</th>
                <th>N° factura</th>
                <th>NIT/CI</th>
                <th>Razón social</th>
                <th>Nombre estudiante</th>
                <th>Tipo de pago</th>
                <th class="text-right">Monto (Bs)</th>
                <th>Cuenta</th>
                <th>Estado</th>
                <th>ID</th>
                <th>Banco</th>
                <th>Fecha registro</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($rows as $row)
                <tr>
                  <td>{{ $row['nro'] }}</td>
                  <td>{{ $row['fecha'] }}</td>
                  <td>{{ $row['numero_factura'] }}</td>
                  <td>{{ $row['nit_ci'] }}</td>
                  <td>{{ $row['razon_social'] }}</td>
                  <td>{{ $row['nombre_estudiante'] }}</td>
                  <td>{{ $row['tipo_pago'] }}</td>
                  <td class="text-right">{{ number_format($row['monto'], 2, ',', '.') }}</td>
                  <td>{{ $row['cuenta'] }}</td>
                  <td>
                    <span
                      class="badge {{ Str::contains(strtolower($row['estado']), 'facturado') ? 'badge-success' : 'badge-warning' }}">
                      {{ $row['estado'] }}
                    </span>
                  </td>
                  <td>{{ $row['custom_id'] }}</td>
                  <td>{{ $row['banco'] }}</td>
                  <td>{{ $row['fecha_registro'] }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="13" class="text-center text-muted py-4">No hay información cargada.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
@endsection