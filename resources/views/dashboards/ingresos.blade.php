@extends('layouts.ingresos')

@section('title', 'UniMatch | Panel de Ingresos')

@php
  $user = auth()->user();
  $roleLabel = 'Encargado de ingresos';
  $availableViews = ['dashboard', 'matching', 'reconciliations', 'students'];
  $requestedView = request()->query('view');
  $activeView = in_array($requestedView, $availableViews, true) ? $requestedView : 'dashboard';
@endphp

@section('panel-role-label', $roleLabel)

@section('panel-wrapper-attrs')
  data-confirm-url="{{ route('ingresos.matching.confirm') }}"
  data-diff-threshold="{{ $reconciliationSettings->difference_alert_threshold }}"
  data-shortage-threshold="{{ $reconciliationSettings->shortage_alert_threshold }}"
  data-credit-limit="{{ $reconciliationSettings->credit_max_amount }}"
  data-initial-view="{{ $activeView }}"
  data-can-manage-billing="{{ auth()->user()->hasRole(\App\Models\User::ROLE_CAJERO) ? '1' : '0' }}"
@endsection

@section('panel-active-menu', $activeView)

@section('panel-content')
  <script type="application/json" id="matching-data" class="d-none">
    {!! $matchingData ? json_encode($matchingData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) : '{}' !!}
  </script>
  <section class="content view" data-view="dashboard">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-6">
            <h1 class="m-0">Resumen general</h1>
            <small class="text-muted">Monitorea transacciones, vouchers y facturación por banco.</small>
          </div>
        </div>
      </div>
    </div>
    <div class="container-fluid">
      @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" data-auto-dismiss="6000">
          {{ session('status') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      @if (session('import_summary'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <strong>Resumen:</strong>
          Líneas importadas: {{ session('import_summary')['lines'] ?? 0 }} · ID de extracto: {{ session('import_summary')['statement_id'] ?? '—' }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="mb-4" id="importAccordion">
        <div class="card card-primary card-outline mb-3">
          <div class="card-header">
            <h4 class="card-title mb-0"><i class="fas fa-file-upload mr-2"></i>Importar extracto bancario</h4>
          </div>
          <div class="card-body">
            <form action="{{ route('imports.extracts') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="form-group">
                <label for="file">Archivo (.csv)</label>
                <input type="file" name="file" id="file" class="form-control-file @error('file') is-invalid @enderror" required>
                @error('file')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Columnas requeridas: bank_code, account_number, operation_number, amount, operation_date, value_date, description.</small>
              </div>
              <button type="submit" class="btn btn-brand btn-sm">Importar extracto</button>
            </form>
          </div>
        </div>

        <div class="card card-success card-outline mb-3">
          <div class="card-header">
            <h4 class="card-title mb-0"><i class="fas fa-clone mr-2"></i>Importar vouchers</h4>
          </div>
          <div class="card-body">
            <form action="{{ route('imports.vouchers') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="form-group">
                <label for="voucher_file">Archivo (.csv)</label>
                <input type="file" name="voucher_file" id="voucher_file" class="form-control-file @error('voucher_file') is-invalid @enderror" required>
                @error('voucher_file')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                  Columnas: student_code, operation_number, amount, bank_code, account_number, paid_at, status, payment_type.
                  (La moneda se asume BOB).
                </small>
              </div>
              <button type="submit" class="btn btn-brand btn-sm">Importar vouchers</button>
            </form>
          </div>
        </div>

        <div class="card card-warning card-outline mb-4">
          <div class="card-header">
            <h4 class="card-title mb-0"><i class="fas fa-pen-alt mr-2"></i>Registrar voucher manual</h4>
            <span class="badge badge-light">Adjunta PDF / imagen</span>
          </div>
          <div class="card-body">
            <form action="{{ route('ingresos.vouchers.store') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="form-row">
                <div class="form-group col-md-4">
                  <label for="manual-student">Estudiante</label>
                  <select name="student_id" id="manual-student" class="form-control @error('student_id') is-invalid @enderror" required>
                    <option value="">Selecciona estudiante</option>
                    @foreach ($students as $student)
                      <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                        {{ $student->full_name }} ({{ $student->code }})
                      </option>
                    @endforeach
                  </select>
                  @error('student_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="form-group col-md-4">
                  <label for="manual-bank">Banco</label>
                  <select name="bank_id" id="manual-bank" class="form-control @error('bank_id') is-invalid @enderror" required>
                    <option value="">Selecciona banco</option>
                    @foreach ($banks as $bank)
                      <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                    @endforeach
                  </select>
                  @error('bank_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="form-group col-md-4">
                  <label for="manual-account">Cuenta bancaria</label>
                  <select name="bank_account_id" id="manual-account" class="form-control @error('bank_account_id') is-invalid @enderror" required>
                    <option value="">Selecciona cuenta</option>
                    @foreach ($banks as $bank)
                      @foreach ($bank->accounts as $account)
                        <option value="{{ $account->id }}" data-bank="{{ $bank->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                          {{ $bank->short_code }} · {{ $account->account_number }}
                        </option>
                      @endforeach
                    @endforeach
                  </select>
                  @error('bank_account_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-3">
                  <label for="manual-paid-at">Fecha del pago</label>
                  <input type="date" name="paid_at" id="manual-paid-at" class="form-control @error('paid_at') is-invalid @enderror" value="{{ old('paid_at') }}" required>
                  @error('paid_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="manual-amount">Monto (Bs.)</label>
                  <input type="number" step="0.01" name="amount" id="manual-amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                  @error('amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="manual-type">Tipo de pago</label>
                  <select name="payment_type" id="manual-type" class="form-control @error('payment_type') is-invalid @enderror" required>
                    @php
                      $manualType = old('payment_type', 'Transferencia');
                    @endphp
                    <option value="Transferencia" {{ $manualType === 'Transferencia' ? 'selected' : '' }}>Transferencia</option>
                    <option value="Depósito" {{ $manualType === 'Depósito' ? 'selected' : '' }}>Depósito</option>
                    <option value="QR" {{ $manualType === 'QR' ? 'selected' : '' }}>QR</option>
                  </select>
                  @error('payment_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="manual-operation">N° de operación</label>
                  <input type="text" name="operation_number" id="manual-operation" class="form-control @error('operation_number') is-invalid @enderror" value="{{ old('operation_number') }}" required>
                  @error('operation_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="manual-reference">Detalle</label>
                  <input type="text" name="account_reference" id="manual-reference" class="form-control @error('account_reference') is-invalid @enderror" value="{{ old('account_reference') }}">
                  @error('account_reference')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="form-group">
                <label for="manual-file">Comprobante (.pdf, .jpg, .png)</label>
                <input type="file" name="voucher_file" id="manual-file" class="form-control-file @error('voucher_file') is-invalid @enderror">
                @error('voucher_file')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
              <button type="submit" class="btn btn-brand">Registrar voucher</button>
            </form>
          </div>
        </div>
      </div>

        <div class="card card-outline card-brand mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Vouchers</h3>
            <div class="input-group input-group-sm" style="width: 260px;">
              <input type="search" class="form-control" id="voucher-search" placeholder="Buscar estudiante, banco, estado">
              <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
              </div>
            </div>
          </div>
          <div class="card-body p-0 table-responsive" style="max-height: 360px; overflow-y: auto;">
            <table class="table table-hover mb-0" id="voucher-table">
              <thead class="thead-light">
                <tr>
                  <th>Fecha</th>
                  <th>Estudiante</th>
                  <th>Banco</th>
                  <th>Monto</th>
                  <th>Estado</th>
                  <th>Comprobante</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentVouchers as $voucher)
                  <tr
                    data-search="{{ strtolower(($voucher->student->full_name ?? '').' '.($voucher->bank->name ?? '').' '.$voucher->status.' '.$voucher->operation_number) }}"
                    data-voucher-id="{{ $voucher->id }}"
                  >
                    <td>{{ optional($voucher->paid_at)->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $voucher->student->full_name ?? '—' }}</td>
                    <td>{{ $voucher->bank->name ?? '—' }}</td>
                    <td>Bs {{ number_format($voucher->amount, 2, ',', '.') }}</td>
                    @php
                      $statusKey = strtolower($voucher->status);
                      $statusColors = [
                        'recibido' => 'info',
                        'conciliado' => 'success',
                        'rechazado' => 'danger',
                        'demasia' => 'warning',
                      ];
                      $badgeClass = $statusColors[$statusKey] ?? 'secondary';
                    @endphp
                    <td>
                      <span class="badge badge-{{ $badgeClass }}" data-voucher-status>{{ ucfirst($statusKey) }}</span>
                      <small class="d-block text-muted" data-voucher-reason {{ $voucher->reason ? '' : 'hidden' }}>
                        {{ $voucher->reason ?? '' }}
                      </small>
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
                    <td colspan="6" class="text-center text-muted py-4">Aún no hay vouchers registrados.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

          <div class="row">
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info elevation-2 summary-filter" data-filter-view="matching" data-filter-status="pending">
                <div class="inner">
                  <h3 id="pending-count">0</h3>
                  <p>Transacciones en espera</p>
                  <small>Última sincronización <span id="last-sync">hace 2 minutos</span></small>
                </div>
                <div class="icon">
                  <i class="fas fa-hourglass-half"></i>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-6">
              <div class="small-box bg-primary elevation-2 summary-filter" data-filter-view="matching" data-filter-status="suggested">
                <div class="inner">
                  <h3 id="suggested-count">0</h3>
                  <p>Coincidencias sugeridas</p>
                  <small>Basado en monto + detalle</small>
                </div>
                <div class="icon">
                  <i class="fas fa-lightbulb"></i>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-6">
              <div class="small-box bg-success elevation-2 summary-filter" data-filter-view="reconciliations">
                <div class="inner">
                  <h3 id="matched-count">0</h3>
                  <p>Conciliaciones completadas</p>
                  <small>Últimos 7 días</small>
                </div>
                <div class="icon">
                  <i class="fas fa-check-circle"></i>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-6">
              <div class="small-box bg-danger elevation-2 summary-filter" data-filter-view="matching" data-filter-status="flagged">
                <div class="inner">
                  <h3 id="alerts-count">0</h3>
                  <p>Alertas activas</p>
                  <small>Discrepancias actuales</small>
                </div>
                <div class="icon">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-8">
              <div class="card card-outline card-brand h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-0">
                  <h3 class="card-title mb-0">
                    <i class="fas fa-chart-line mr-2"></i>
                    Tendencia de conciliaciones
                  </h3>
                  <div class="card-tools">
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-outline-brand toggle active" data-range="7">7 días</button>
                      <button type="button" class="btn btn-outline-brand toggle" data-range="30">30 días</button>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="chart-placeholder">
                    <canvas id="trend-chart" aria-label="Tendencia de conciliaciones"></canvas>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="card card-outline card-brand h-100">
                <div class="card-header border-0 d-flex align-items-center justify-content-between">
                  <h3 class="card-title mb-0">
                    <i class="fas fa-bell mr-2"></i>
                    Alertas recientes
                  </h3>
                  <button type="button" class="btn btn-link btn-sm p-0" id="view-all-alerts">Ver todas</button>
                </div>
                <div class="card-body p-0">
                  <div class="empty-state mb-0 rounded-0 border-0" id="alerts-empty">
                    <p class="mb-0">Sin alertas críticas. Todo al día.</p>
                  </div>
                  <ul class="alert-list list-unstyled mb-0" id="alert-list"></ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content view d-none" data-view="matching">
        <div class="content-header">
          <div class="container-fluid">
            <div class="row mb-2 align-items-center">
              <div class="col-sm-6">
                <h1 class="m-0">Conciliación</h1>
                <small class="text-muted">Gestiona las coincidencias entre extractos y vouchers.</small>
              </div>
            </div>
          </div>
        </div>
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Conciliación de transacciones</h3>
                <p class="card-subtitle">Cruza vouchers, extractos y facturación para cada banco.</p>
              </div>
              <div class="card-tools">
                <div class="form-inline">
                  <div class="form-group mr-2 mb-2 mb-md-0">
                    <select class="form-control" id="bank-filter">
                      <option value="">Todos los bancos</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <select class="form-control" id="status-filter">
                      <option value="" selected>Todos</option>
                      <option value="pending">Pendientes</option>
                      <option value="suggested">Con sugerencia</option>
                      <option value="flagged">Con alerta</option>
                      <option value="matched">Conciliado</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="row no-gutters matching-layout">
                <div class="col-xl-4 col-lg-5 border-right">
                  <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Transacciones</h4>
                    <span class="badge badge-pill badge-brand" id="transaction-count">0</span>
                  </div>
                  <div class="list-body" id="transaction-list"></div>
                </div>
                <div class="col-xl-4 col-lg-4 border-right">
                  <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Vouchers</h4>
                    <span class="badge badge-pill badge-brand" id="voucher-count">0</span>
                  </div>
                  <div class="list-body" id="voucher-list">
                    <div class="empty-state" data-empty="voucher">Selecciona una transacción para ver sugerencias.</div>
                  </div>
                </div>
                <div class="col-xl-4 col-lg-3">
                  <div class="p-3 border-bottom">
                    <h4 class="mb-0">Detalle</h4>
                  </div>
                  <div class="detail-body" id="match-detail">
                    <div class="empty-state" data-empty="detail">Selecciona transacción y voucher para revisar coincidencia.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content view d-none" data-view="reconciliations">
        <div class="content-header">
          <div class="container-fluid">
            <div class="row mb-2 align-items-center">
              <div class="col-sm-6">
                <h1 class="m-0">Facturación</h1>
                <small class="text-muted">Control de operaciones conciliadas y su estado de factura.</small>
              </div>
            </div>
          </div>
        </div>
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Facturación y validación</h3>
                <p class="card-subtitle">Controla las facturas emitidas y operaciones sin facturar.</p>
              </div>
              <div class="card-tools form-inline">
                <input type="date" class="form-control mr-2" id="reconciliation-date">
                <select class="form-control" id="reconciliation-bank">
                  <option value="">Todos los bancos</option>
                </select>
              </div>
            </div>
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover" id="reconciliation-table">
                <thead class="thead-light">
                  <tr>
                    <th>Fecha</th>
                    <th>Banco</th>
                    <th>Estudiante</th>
                    <th>Monto</th>
                  <th>Estado de facturación</th>
                  <th>Estado</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <section class="content view d-none" data-view="students">
        <div class="content-header">
          <div class="container-fluid">
            <div class="row mb-2 align-items-center">
              <div class="col-sm-6">
                <h1 class="m-0">Seguimiento de estudiantes</h1>
                <small class="text-muted">Revisa estados de pagos y facturas por estudiante.</small>
              </div>
            </div>
          </div>
        </div>
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Estudiantes</h3>
                <p class="card-subtitle">Seguimiento de pagos, facturas y saldos a favor.</p>
              </div>
              <div class="card-tools form-inline">
                <input type="search" class="form-control" id="student-search" placeholder="Buscar por nombre o código">
              </div>
            </div>
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover" id="student-table">
                <thead class="thead-light">
                  <tr>
                    <th>Estudiante</th>
                    <th>Código</th>
                    <th>Último movimiento</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

  <div class="toast toast-brand" id="toast" aria-live="assertive" hidden></div>

  <div class="modal fade" id="match-modal" tabindex="-1" role="dialog" aria-labelledby="matchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="matchModalLabel">Confirmar conciliación</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="modal-body"></div>
        <div class="modal-footer">
          <button class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button class="btn btn-brand" id="confirm-match">Confirmar</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const setups = [
      { bank: 'manual-bank', account: 'manual-account' },
    ];

    setups.forEach(({ bank, account }) => {
      const bankSelect = document.getElementById(bank);
      const accountSelect = document.getElementById(account);

      if (!bankSelect || !accountSelect) {
        return;
      }

      const originalOptions = Array.from(accountSelect.querySelectorAll('option[data-bank]'));

      const renderAccounts = () => {
        const selectedBank = bankSelect.value;
        const previousValue = accountSelect.value;
        const placeholder = accountSelect.querySelector('option[value=""]');
        accountSelect.innerHTML = '';
        const defaultOption = placeholder ?? Object.assign(document.createElement('option'), {
          value: '',
          textContent: 'Selecciona cuenta',
        });
        accountSelect.appendChild(defaultOption);

        originalOptions.forEach((option) => {
          if (!selectedBank || option.dataset.bank === selectedBank) {
            accountSelect.appendChild(option.cloneNode(true));
          }
        });

        if (previousValue) {
          accountSelect.value = previousValue;
        }
      };

      renderAccounts();
      bankSelect.addEventListener('change', renderAccounts);
    });

    const searchInput = document.getElementById('voucher-search');
    const table = document.getElementById('voucher-table');
    if (searchInput && table) {
      const rows = Array.from(table.querySelectorAll('tbody tr'));
      searchInput.addEventListener('input', () => {
        const term = searchInput.value.toLowerCase();
        rows.forEach((row) => {
          const haystack = row.dataset.search || '';
          row.style.display = haystack.includes(term) ? '' : 'none';
        });
      });
    }
  });
</script>
@endpush
