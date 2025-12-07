@extends('layouts.app')

@section('title', 'UniMatch | Portal del Estudiante')

@section('body-class', 'hold-transition sidebar-mini layout-fixed')

@section('content')
  @php
      $user = auth()->user();
      $initials = collect(preg_split('/\s+/', $user->name, -1, PREG_SPLIT_NO_EMPTY))
          ->map(fn ($part) => mb_substr($part, 0, 1))
          ->take(2)
          ->implode('');
      $availableViews = ['vouchers', 'balance'];
      $requestedView = request()->query('view');
      $activeView = in_array($requestedView, $availableViews, true) ? $requestedView : 'vouchers';
  @endphp

  <div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-light navbar-white">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-md-inline-block">
          <span class="navbar-brand mb-0 h5">Portal de pagos</span>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item ml-2">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-default btn-sm">Cerrar sesión</button>
          </form>
        </li>
      </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-brand elevation-4">
      <a href="#" class="brand-link text-center">
        <span class="brand-text font-weight-light">UniMatch · Estudiante</span>
      </a>
      <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
          <div class="image">
            <span class="avatar-circle">{{ $initials }}</span>
          </div>
          <div class="info">
            <span class="d-block user-name">{{ $user->name }}</span>
            <span class="text-muted user-role">Estudiante</span>
          </div>
        </div>
        <nav>
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item">
              <a href="{{ route('dashboard') }}" class="nav-link {{ $activeView === 'vouchers' ? 'active' : '' }}">
                <i class="nav-icon fas fa-receipt"></i>
                <p>Mis vouchers</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('dashboard', ['view' => 'balance']) }}" class="nav-link {{ $activeView === 'balance' ? 'active' : '' }}">
                <i class="nav-icon fas fa-wallet"></i>
                <p>Saldos a favor</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
              <h1 class="m-0">
                {{ $activeView === 'balance' ? 'Saldo a favor' : 'Mis pagos y comprobantes' }}
              </h1>
              <small class="text-muted">
                {{ $activeView === 'balance' ? 'Consulta los créditos disponibles y sus movimientos.' : 'Sube tus vouchers y monitorea su estado.' }}
              </small>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="container-fluid">
          @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              {{ session('status') }}
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          @endif

          @if ($activeView === 'balance')
            <div class="card card-outline card-brand mb-4" id="balance-card">
              <div class="card-header bg-gradient-info">
                <h3 class="card-title mb-0"><i class="fas fa-wallet mr-2"></i>Saldo a favor</h3>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="info-box bg-light">
                      <span class="info-box-icon bg-info"><i class="fas fa-money-bill-wave"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Saldo disponible</span>
                        <span class="info-box-number" id="balance-amount">Bs 0.00</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-box bg-light">
                      <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Última actualización</span>
                        <span class="info-box-number text-sm" id="balance-updated">Cargando...</span>
                      </div>
                    </div>
                  </div>
                </div>

                <hr>

                <h5>Movimientos recientes</h5>
                <div class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th class="text-right">Monto</th>
                      </tr>
                    </thead>
                    <tbody id="balance-movements">
                      <tr>
                        <td colspan="3" class="text-center text-muted py-3">Cargando movimientos...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @else
            <div class="card card-outline card-brand mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-ban mr-2"></i>Registro de vouchers deshabilitado</h3>
              </div>
              <div class="card-body">
                <div class="alert alert-info mb-0">
                  El registro manual de vouchers ya no está disponible. Todas las conciliaciones se realizan a partir de los extractos bancarios y el reporte diario de ingresos.
                  Si necesitas regularizar un pago, comunícate con el área de ingresos.
                </div>
              </div>
            </div>

          <div class="card card-outline card-brand">
            <div class="card-header">
              <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Historial de vouchers</h3>
            </div>
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Fecha</th>
                    <th>Banco</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Comprobante</th>
                </tr>
                </thead>
                <tbody>
                  @forelse ($studentVouchers as $voucher)
                    <tr>
                      <td>{{ optional($voucher->paid_at)->format('d/m/Y') ?? '—' }}</td>
                      <td>{{ $voucher->bank->name ?? '—' }}</td>
                      <td>Bs {{ number_format($voucher->amount, 2, ',', '.') }}</td>
                      <td>
                        @php
                          $studentStatus = strtolower($voucher->status);
                          $statusPills = [
                            'recibido' => 'info',
                            'conciliado' => 'success',
                            'rechazado' => 'danger',
                            'demasia' => 'warning',
                          ];
                          $studentBadge = $statusPills[$studentStatus] ?? 'secondary';
                        @endphp
                        <span class="badge badge-{{ $studentBadge }}">
                          {{ ucfirst($studentStatus) }}
                        </span>
                        @if ($voucher->reason)
                          <small class="d-block text-muted mt-1">{{ $voucher->reason }}</small>
                        @endif
                      </td>
                      <td>
                        @if ($voucher->document_url)
                          <a href="{{ $voucher->document_url }}" target="_blank" class="btn btn-link btn-sm"><i class="fas fa-file-alt"></i> Ver</a>
                        @else
                          <span class="text-muted">Sin archivo</span>
                        @endif
                      </td>
                      
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">
                        Aún no registraste vouchers.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          @endif
        </div>
      </section>
    </div>

    <aside class="control-sidebar control-sidebar-dark"></aside>
  </div>
@endsection

@push('scripts')
<script>
  const loadStudentBalance = async () => {
    try {
      const response = await fetch('/api/student/balance', {
        headers: { 'Accept': 'application/json' },
      });
      const data = await response.json();

      const amountEl = document.getElementById('balance-amount');
      const updatedEl = document.getElementById('balance-updated');
      const tbody = document.getElementById('balance-movements');

      if (amountEl) {
        amountEl.textContent = `Bs ${(parseFloat(data.balance ?? 0)).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
      }

      if (updatedEl) {
        updatedEl.textContent = data.updated_at ?? 'N/D';
      }

      if (!tbody) {
        return;
      }

      tbody.innerHTML = '';
      const movements = Array.isArray(data.movements) ? data.movements : [];

      if (!movements.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Sin movimientos.</td></tr>';
        return;
      }

      movements.forEach((mov) => {
        const row = document.createElement('tr');
        const amountClass = mov.type === 'credit' ? 'text-success' : 'text-danger';
        const sign = mov.type === 'credit' ? '+' : '';

        row.innerHTML = `
          <td>${mov.date}</td>
          <td>${mov.description}</td>
          <td class="text-right ${amountClass} font-weight-bold">${sign}Bs ${Math.abs(mov.amount).toFixed(2)}</td>
        `;
        tbody.appendChild(row);
      });
    } catch (error) {
      console.error('Error cargando saldo:', error);
      const tbody = document.getElementById('balance-movements');
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="3" class="alert alert-danger">Error al cargar saldo.</td></tr>';
      }
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert.alert-dismissible .close').forEach((btn) => {
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        const alert = btn.closest('.alert');
        if (alert) {
          alert.classList.remove('show');
          alert.addEventListener('transitionend', () => alert.remove(), { once: true });
          setTimeout(() => alert.remove(), 200);
        }
      });
    });

    @if ($activeView === 'balance')
      loadStudentBalance();
    @endif
  });
</script>
@endpush
