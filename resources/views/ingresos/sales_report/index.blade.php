@extends('layouts.ingresos')

@section('title', 'UniMatch | Reporte diario de ingresos')
@section('panel-active-menu', 'sales_report')

@section('panel-content')
  @php
    $canManageSales = auth()->user()->hasRole([
        \App\Models\User::ROLE_ENCARGADO_INGRESOS,
        \App\Models\User::ROLE_JEFE_CONTABILIDAD,
    ]);
  @endphp

  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="h4 mb-0">Reporte diario de ingresos</h1>
          <p class="text-muted mb-0 small">Consulta y ajusta los registros importados del libro diario.</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Ingresos</a></li>
            <li class="breadcrumb-item active">Reporte diario</li>
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


      <div class="card card-outline card-primary collapsed-card">
        <div class="card-header">
          <h3 class="card-title">Filtros de búsqueda</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
              <i class="fas fa-plus"></i>
            </button>
          </div>
        </div>
        <div class="card-body" style="display: none;">
          <form action="{{ route('ingresos.sales-report.index') }}" method="GET">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="start_date">Fecha inicio</label>
                <input type="date" class="form-control" name="start_date" id="start_date" value="{{ request('start_date') }}">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="end_date">Fecha fin</label>
                <input type="date" class="form-control" name="end_date" id="end_date" value="{{ request('end_date') }}">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label for="bank">Banco</label>
                <select class="form-control" name="bank" id="bank">
                  <option value="">Todos</option>
                  @foreach($banks as $b)
                    <option value="{{ $b }}" {{ request('bank') == $b ? 'selected' : '' }}>{{ $b }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label for="status">Estado</label>
                <select class="form-control" name="status" id="status">
                  <option value="">Todos</option>
                  @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <div class="form-group w-100 d-flex">
                <button type="submit" class="btn btn-primary flex-fill mr-2">
                  <i class="fas fa-search mr-1"></i>
                </button>
                <a href="{{ route('ingresos.sales-report.index') }}" class="btn btn-default" title="Limpiar filtros">
                  <i class="fas fa-times"></i>
                </a>
              </div>
            </div>
          </div>
          </form>
        </div>
      </div>

      <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Detalle tipo Excel</h3>
          <div class="d-flex align-items-center">
            @if ($entries->total() > 0)
              <span class="text-muted small mr-3">
                Mostrando {{ $entries->firstItem() }}-{{ $entries->lastItem() }} de {{ $entries->total() }} registros
              </span>
            @endif
            <div class="btn-group btn-group-sm" role="group" aria-label="Descargar reporte diario">
              <a href="{{ route('ingresos.sales-report.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="btn btn-default">
                <i class="fas fa-file-pdf text-danger"></i> PDF
              </a>
              <a href="{{ route('ingresos.sales-report.export', array_merge(['format' => 'xls'], request()->query())) }}" class="btn btn-default">
                <i class="fas fa-file-excel text-success"></i> XLS
              </a>
              <a href="{{ route('ingresos.sales-report.export', array_merge(['format' => 'txt'], request()->query())) }}" class="btn btn-default">
                <i class="fas fa-file-alt"></i> TXT
              </a>
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="overflow-x:auto;">
            <table class="table table-striped table-bordered table-sm mb-0 w-100" style="min-width: 1500px;">
              <thead class="bg-light">
                <tr>
                  <th>N°</th>
                  <th>Fecha</th>
                  <th>N° factura</th>
                  <th>NIT/CI</th>
                  <th>Razón social</th>
                  <th>Nombre estudiante</th>
                  <th>Tipo pago</th>
                  <th class="text-right">Monto</th>
                  <th>Cuenta</th>
                  <th>Estado</th>
                  <th>ID</th>
                  <th>Banco</th>
                  <th>Fecha registro</th>
                  <th>Operación</th>
                  @if ($canManageSales)
                    <th>Acciones</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse ($entries as $entry)
                  @php
                    $entryOldId = old('_entry_id');
                    $customIdValue = $entryOldId == $entry->id ? old('custom_id') : $entry->custom_id;
                    $bankNameValue = $entryOldId == $entry->id ? old('bank_name') : $entry->bank_name;
                    $recordedDateValue = $entryOldId == $entry->id
                        ? old('recorded_date')
                        : ($entry->recorded_date ? $entry->recorded_date->format('Y-m-d') : '');
                    $operationReferenceValue = $entryOldId == $entry->id ? old('operation_reference') : $entry->operation_reference;
                  @endphp
                  <tr>
                    <td>{{ $entry->legacy_number ?? '—' }}</td>
                    <td>{{ optional($entry->invoice_date)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $entry->invoice_number ?? '—' }}</td>
                    <td>{{ $entry->nit_ci ?? '—' }}</td>
                    <td>{{ $entry->razon_social ?? '—' }}</td>
                    <td>{{ $entry->student_name ?? '—' }}</td>
                    <td>{{ $entry->payment_type ?? '—' }}</td>
                    <td class="text-right">{{ 'Bs '.number_format((float) $entry->amount, 2, ',', '.') }}</td>
                    <td>{{ $entry->account_label ?? '—' }}</td>
                    <td>{{ $entry->state_label ?? '—' }}</td>
                    <td style="width:140px; min-width:140px;">
                      @if ($canManageSales)
                        <form id="sales-entry-{{ $entry->id }}" action="{{ route('ingresos.sales-report.update', $entry) }}" method="POST">
                          @csrf
                          @method('PATCH')
                          <input type="hidden" name="_entry_id" value="{{ $entry->id }}">
                        </form>
                        <input type="text" class="form-control form-control-sm" name="custom_id" form="sales-entry-{{ $entry->id }}" value="{{ $customIdValue }}" placeholder="Ej: VR-01">
                      @else
                        {{ $entry->custom_id ?? '—' }}
                      @endif
                    </td>
                    <td style="width:150px; min-width:150px;">
                      @if ($canManageSales)
                        <input type="text" class="form-control form-control-sm" name="bank_name" form="sales-entry-{{ $entry->id }}" value="{{ $bankNameValue }}" placeholder="Banco">
                      @else
                        {{ $entry->bank_name ?? '—' }}
                      @endif
                    </td>
                    <td style="width:200px;">
                      @if ($canManageSales)
                        <input type="date" class="form-control form-control-sm" name="recorded_date" form="sales-entry-{{ $entry->id }}" value="{{ $recordedDateValue }}" min="{{ optional($entry->invoice_date)->format('Y-m-d') }}">
                      @else
                        {{ optional($entry->recorded_date)->format('d/m/Y') ?? '—' }}
                      @endif
                    </td>
                    <td style="width:200px;">
                      @if ($canManageSales)
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          name="operation_reference"
                          form="sales-entry-{{ $entry->id }}"
                          value="{{ $operationReferenceValue }}"
                          placeholder="N° operación"
                        >
                      @else
                        {{ $entry->operation_reference ?? '—' }}
                      @endif
                    </td>
                    @if ($canManageSales)
                      <td style="width:160px;">
                        <button type="submit" class="btn btn-primary btn-sm" form="sales-entry-{{ $entry->id }}">
                          Guardar
                        </button>
                        @if ($entry->batch?->source_name)
                          <span class="d-block text-muted small mt-1">{{ $entry->batch->source_name }}</span>
                        @endif
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="15" class="text-center text-muted py-4">Aún no se ha importado el reporte diario.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if ($entries->hasPages())
          <div class="card-footer d-flex justify-content-end">
            {{ $entries->links() }}
          </div>
        @endif
      </div>
    </div>
  </section>
@endsection
