@extends('layouts.ingresos')

@section('title', 'UniMatch | Panel Ejecutivo')

@section('panel-role-label', 'Jefatura de contabilidad')
@section('panel-active-menu', 'dashboard')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1 class="m-0">Visión financiera consolidada</h1>
          <small class="text-muted">Estados de facturación, extractos y alertas críticas.</small>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right mb-0">
            <li class="breadcrumb-item active">Jefatura · Tablero</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      @php
          $totals = $dashboard['totals'];
      @endphp
      <div class="row">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-primary elevation-2">
            <div class="inner">
              <h3>Bs {{ number_format($totals['facturado_hoy'], 2, ',', '.') }}</h3>
              <p>Facturado hoy</p>
            </div>
            <div class="icon">
              <i class="fas fa-file-invoice-dollar"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success elevation-2">
            <div class="inner">
              <h3>{{ number_format($totals['operaciones_facturadas']) }}</h3>
              <p>Operaciones facturadas</p>
            </div>
            <div class="icon">
              <i class="fas fa-check-circle"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning elevation-2">
            <div class="inner">
              <h3>{{ number_format($totals['operaciones_sin_factura']) }}</h3>
              <p>Operaciones sin factura</p>
            </div>
            <div class="icon">
              <i class="fas fa-exclamation-circle"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger elevation-2">
            <div class="inner">
              <h3>{{ number_format($totals['alertas']) }}</h3>
              <p>Alertas críticas</p>
            </div>
            <div class="icon">
              <i class="fas fa-bell"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-8">
          <div class="card card-outline card-brand h-100">
            <div class="card-header border-0">
              <h3 class="card-title mb-0"><i class="fas fa-chart-line mr-2"></i>Tendencia de facturación (7 días)</h3>
            </div>
            <div class="card-body" style="height: 320px;">
              <div class="h-100">
                <canvas id="executive-trend" height="280" aria-label="Tendencia de facturación"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card card-outline card-danger h-100">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Alertas críticas</h3>
              <span class="badge badge-danger">{{ $dashboard['alerts']->count() }}</span>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                @forelse ($dashboard['alerts'] as $alert)
                  <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                      <strong>{{ $alert['student'] }}</strong>
                      <small class="text-muted">{{ $alert['date'] }}</small>
                    </div>
                    <p class="mb-1 text-sm text-muted">{{ $alert['bank'] }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="badge badge-warning">Diferencia: {{ number_format($alert['difference'], 2, ',', '.') }}</span>
                      <span class="text-sm text-muted">{{ number_format($alert['amount'], 2, ',', '.') }} Bs</span>
                    </div>
                  </li>
                @empty
                  <li class="list-group-item text-center text-muted">Sin alertas activas.</li>
                @endforelse
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-info alert-dismissible fade show" role="alert">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div>
            <strong>Formatos bancarios</strong>
            <p class="mb-0 text-sm">Actualiza los campos esperados para cada banco antes de cargar extractos.</p>
          </div>
          <a href="{{ route('admin.banks.index') }}" class="btn btn-outline-brand btn-sm mt-2 mt-md-0">
            Gestionar formatos
          </a>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="card card-outline card-brand">
        <div class="card-header border-0 d-flex justify-content-between align-items-center">
          <div>
            <h3 class="card-title mb-0"><i class="fas fa-university mr-2"></i>Saldos por banco</h3>
            <p class="card-subtitle text-muted mb-0">Comparativo de montos facturados vs. recibidos.</p>
          </div>
        </div>
        <div class="card-body p-0 table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Banco</th>
                <th class="text-right">Total recibido</th>
                <th class="text-right">Facturado</th>
                <th>Avance</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($dashboard['bankSummaries'] as $bank)
                <tr>
                  <td>{{ $bank['bank'] }} <small class="text-muted">({{ $bank['short_code'] }})</small></td>
                  <td class="text-right">Bs {{ number_format($bank['total'], 2, ',', '.') }}</td>
                  <td class="text-right">Bs {{ number_format($bank['facturado'], 2, ',', '.') }}</td>
                  <td style="width:220px;">
                    @php
                        $progress = $bank['total'] > 0 ? min(100, round(($bank['facturado'] / $bank['total']) * 100)) : 0;
                    @endphp
                    <div class="progress progress-sm">
                      <div class="progress-bar bg-brand" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">{{ $progress }} %</small>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Aún no hay saldos registrados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('executive-trend');
    const data = @json($dashboard['trend']);

    if (ctx && window.Chart && data) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.labels,
          datasets: [
            {
              label: 'Facturado',
              data: data.facturado,
              borderColor: '#28a745',
              backgroundColor: 'rgba(40, 167, 69, 0.1)',
              tension: 0.3,
              fill: true,
            },
            {
              label: 'Pendiente',
              data: data.pendiente,
              borderColor: '#ffc107',
              backgroundColor: 'rgba(255, 193, 7, 0.15)',
              tension: 0.3,
              fill: true,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
            },
          },
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    }
  });
</script>
@endpush
