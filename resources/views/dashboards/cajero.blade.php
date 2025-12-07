@extends('layouts.ingresos')

@section('title', 'UniMatch | Cajero')
@section('panel-active-menu', 'dashboard')
@section('panel-role-label', 'Cajero')

@section('panel-content')
  @php
    $resources = [
        [
            'title' => 'Extractos cargados',
            'description' => 'Consulta el detalle tipo Excel de cada línea importada desde los bancos.',
            'view_route' => route('ingresos.statements.index'),
            'exports' => [
                ['label' => 'PDF', 'format' => 'pdf', 'icon' => 'fa-file-pdf', 'class' => 'text-danger', 'route' => route('ingresos.statements.export', ['format' => 'pdf'])],
                ['label' => 'XLS', 'format' => 'xls', 'icon' => 'fa-file-excel', 'class' => 'text-success', 'route' => route('ingresos.statements.export', ['format' => 'xls'])],
                ['label' => 'TXT', 'format' => 'txt', 'icon' => 'fa-file-alt', 'class' => '', 'route' => route('ingresos.statements.export', ['format' => 'txt'])],
            ],
        ],
        [
            'title' => 'Reporte diario de ingresos',
            'description' => 'Visualiza el libro de ventas importado y sus columnas editables.',
            'view_route' => route('ingresos.sales-report.index'),
            'exports' => [
                ['label' => 'PDF', 'format' => 'pdf', 'icon' => 'fa-file-pdf', 'class' => 'text-danger', 'route' => route('ingresos.sales-report.export', ['format' => 'pdf'])],
                ['label' => 'XLS', 'format' => 'xls', 'icon' => 'fa-file-excel', 'class' => 'text-success', 'route' => route('ingresos.sales-report.export', ['format' => 'xls'])],
                ['label' => 'TXT', 'format' => 'txt', 'icon' => 'fa-file-alt', 'class' => '', 'route' => route('ingresos.sales-report.export', ['format' => 'txt'])],
            ],
        ],
        [
            'title' => 'Reporte de conciliaciones',
            'description' => 'Descarga todas las coincidencias confirmadas (conciliadas, demasía o rechazadas).',
            'view_route' => route('ingresos.reconciliation-report.index'),
            'exports' => [
                ['label' => 'PDF', 'format' => 'pdf', 'icon' => 'fa-file-pdf', 'class' => 'text-danger', 'route' => route('ingresos.reconciliation-report.export', ['format' => 'pdf'])],
                ['label' => 'XLS', 'format' => 'xls', 'icon' => 'fa-file-excel', 'class' => 'text-success', 'route' => route('ingresos.reconciliation-report.export', ['format' => 'xls'])],
                ['label' => 'TXT', 'format' => 'txt', 'icon' => 'fa-file-alt', 'class' => '', 'route' => route('ingresos.reconciliation-report.export', ['format' => 'txt'])],
            ],
        ],
    ];
  @endphp

  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">Reportes disponibles</h1>
          <small class="text-muted">Solo lectura · utiliza los botones para ver o descargar.</small>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        @foreach ($resources as $resource)
          <div class="col-xl-4 col-md-6">
            <div class="card card-outline card-brand h-100">
              <div class="card-body d-flex flex-column">
                <h3 class="card-title mb-2">{{ $resource['title'] }}</h3>
                <p class="text-muted flex-grow-1 mb-3">{{ $resource['description'] }}</p>
                <a href="{{ $resource['view_route'] }}" class="btn btn-brand btn-sm mb-3">
                  <i class="fas fa-chart-line mr-1"></i> Ver detalle
                </a>
                <div class="d-flex flex-wrap" style="gap: 8px;">
                  @foreach ($resource['exports'] as $export)
                    <a href="{{ $export['route'] }}" class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                      <i class="fas {{ $export['icon'] }} {{ $export['class'] }} mr-1"></i>{{ $export['label'] }}
                    </a>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endsection
