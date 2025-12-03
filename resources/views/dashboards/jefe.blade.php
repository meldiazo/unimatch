@extends('layouts.ingresos')

@php
  $availableViews = ['dashboard', 'facturacion', 'students'];
  $requestedView = request()->query('view');
  $activeView = in_array($requestedView, $availableViews, true) ? $requestedView : 'dashboard';
  $totals = $dashboard['totals'];
  $alerts = $dashboard['alerts'];
  $bankSummaries = $dashboard['bankSummaries'];
  $facturacion = $dashboard['facturacion'];
  $students = $dashboard['students'];
@endphp

@section('panel-role-label', 'Jefatura de contabilidad')
@section('panel-active-menu', $activeView)
@section('panel-wrapper-attrs', 'data-initial-view="'.$activeView.'"')

@section('panel-content')
  <section class="content jef-view {{ $activeView === 'dashboard' ? '' : 'd-none' }}">
    <div class="content-header">
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
    </div>

    <section class="content">
      <div class="container-fluid">
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
                <span class="badge badge-danger">{{ $alerts->count() }}</span>
              </div>
              <div class="card-body p-0" style="max-height: 360px; overflow-y: auto;">
                <ul class="list-group list-group-flush mb-0">
                  @forelse ($alerts as $alert)
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
                </tr>
              </thead>
              <tbody>
                @forelse ($bankSummaries as $bank)
                  <tr>
                    <td>{{ $bank['bank'] }} <small class="text-muted">({{ $bank['short_code'] }})</small></td>
                    <td class="text-right">Bs {{ number_format($bank['total'], 2, ',', '.') }}</td>
                    <td class="text-right">Bs {{ number_format($bank['facturado'], 2, ',', '.') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">Aún no hay saldos registrados.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </section>

  <section class="content jef-view {{ $activeView === 'facturacion' ? '' : 'd-none' }}">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-6">
            <h1 class="m-0">Facturación</h1>
            <small class="text-muted">Conciliaciones recientes y su estado de factura.</small>
          </div>
        </div>
      </div>
    </div>
    <section class="content">
      <div class="container-fluid">
        <div class="card card-outline card-brand">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Operaciones conciliadas</h3>
            <span class="badge badge-info">{{ $facturacion->count() }} registros</span>
          </div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Fecha</th>
                  <th>Estudiante</th>
                  <th>Banco</th>
                  <th class="text-right">Monto</th>
                  <th>Estado</th>
                  <th>Facturación</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($facturacion as $voucher)
                  @php
                    $statusClass = [
                      'conciliado' => 'success',
                      'demasia' => 'warning',
                    ][strtolower($voucher->status)] ?? 'secondary';
                    $billingClass = $voucher->billing_status === 'facturado' ? 'success' : 'warning';
                  @endphp
                  <tr>
                    <td>{{ optional($voucher->paid_at)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $voucher->student->full_name ?? '—' }}</td>
                    <td>{{ $voucher->bank->name ?? '—' }}</td>
                    <td class="text-right">Bs {{ number_format($voucher->amount, 2, ',', '.') }}</td>
                    <td><span class="badge badge-{{ $statusClass }}">{{ ucfirst($voucher->status) }}</span></td>
                    <td><span class="badge badge-{{ $billingClass }}">{{ ucfirst($voucher->billing_status ?? 'pendiente') }}</span></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No hay registros disponibles.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </section>

  <section class="content jef-view {{ $activeView === 'students' ? '' : 'd-none' }}">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-6">
            <h1 class="m-0">Seguimiento de estudiantes</h1>
            <small class="text-muted">Consulta últimos pagos y saldos a favor.</small>
          </div>
        </div>
      </div>
    </div>
    <section class="content">
      <div class="container-fluid">
        <div class="card card-outline card-brand">
          <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-user-graduate mr-2"></i>Histórico</h3>
            <span class="badge badge-info">{{ $students->count() }} registros</span>
          </div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Estudiante</th>
                  <th>Código</th>
                  <th>Último pago</th>
                  <th class="text-right">Saldo a favor</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($students as $student)
                  <tr>
                    <td>{{ $student['name'] }}</td>
                    <td><code>{{ $student['code'] ?? '—' }}</code></td>
                    <td>{{ $student['last_payment'] ?? '—' }}</td>
                    <td class="text-right">Bs {{ number_format($student['balance'], 2, ',', '.') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">Sin registros.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
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
