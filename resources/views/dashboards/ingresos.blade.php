@extends('layouts.ingresos')

@section('title', 'UniMatch | Panel de Ingresos')

@php
  $user = auth()->user();
  $roleLabel = 'Encargado de ingresos';
  $availableViews = ['dashboard', 'matching', 'students'];
  $requestedView = request()->query('view');
  $activeView = in_array($requestedView, $availableViews, true) ? $requestedView : 'dashboard';
@endphp

@section('panel-role-label', $roleLabel)

@section('panel-wrapper-attrs')
  data-confirm-url="{{ route('ingresos.matching.confirm') }}"
  data-sales-report-url="{{ url('/ingresos/reporte-diario') }}"
  data-statement-url="{{ url('/ingresos/extractos') }}"
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
            <small class="text-muted">Monitorea extractos, reporte diario y facturación por banco.</small>
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
        @foreach ($banks as $bank)
          @php
            $wasSubmitted = (int) old('bank_id') === $bank->id;
          @endphp
          <div class="card card-primary card-outline mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <h4 class="card-title mb-0">
                  <i class="fas fa-file-upload mr-2"></i>Importar extracto · {{ $bank->name }}
                </h4>
                <small class="d-block text-muted">Formato dedicado para {{ $bank->short_code }}.</small>
              </div>
            </div>
            <div class="card-body">
              <form action="{{ route('imports.extracts') }}" method="POST" enctype="multipart/form-data" class="bank-import-form">
                @csrf
                <input type="hidden" name="bank_id" value="{{ $bank->id }}">
                <div class="form-group">
                  <label for="file-{{ $bank->id }}">Archivo (.xls, .xlsx o .csv)</label>
                  <input
                    type="file"
                    name="file"
                    id="file-{{ $bank->id }}"
                    accept=".xls,.xlsx,.csv"
                    class="d-none file-input-es @error('file') {{ $wasSubmitted ? 'is-invalid' : '' }} @enderror"
                    required
                    data-label="file-label-{{ $bank->id }}"
                    data-placeholder="Ningún archivo seleccionado"
                    oninvalid="this.setCustomValidity('Selecciona un archivo para importar.')"
                    oninput="this.setCustomValidity('')"
                  >
                  <div class="input-group file-picker">
                    <div class="input-group-prepend">
                      <button type="button" class="btn btn-outline-secondary file-input-trigger" data-target="file-{{ $bank->id }}">
                        Seleccionar archivo
                      </button>
                    </div>
                    <input type="text" class="form-control file-input-display" id="file-label-{{ $bank->id }}" value="Ningún archivo seleccionado" readonly>
                  </div>
                  @if ($wasSubmitted)
                    @error('file')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  @endif
                  <small class="form-text text-muted">
                    Se extraerán columnas de Fecha, Hora, Oficina, Descripción, Referencia, Código de transacción, Débitos, Créditos y Saldo.
                  </small>
                </div>
                <button type="submit" class="btn btn-brand btn-sm">
                  Importar extracto {{ $bank->short_code }}
                </button>
              </form>
            </div>
          </div>
        @endforeach

        <div class="card card-info card-outline mb-3">
          <div class="card-header">
            <h4 class="card-title mb-0"><i class="fas fa-book mr-2"></i>Subir reporte diario de ingresos</h4>
          </div>
          <div class="card-body">
            <form action="{{ route('imports.sales_book') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="form-group">
                <label for="sales-book-file">Archivo (.xls, .xlsx o .csv)</label>
                <input
                  type="file"
                  name="sales_book_file"
                  id="sales-book-file"
                  class="d-none file-input-es @error('sales_book_file') is-invalid @enderror"
                  accept=".xls,.xlsx,.csv"
                  required
                  data-label="sales-book-file-label"
                  data-placeholder="Ningún archivo seleccionado"
                >
                <div class="input-group file-picker">
                  <div class="input-group-prepend">
                    <button type="button" class="btn btn-outline-secondary file-input-trigger" data-target="sales-book-file">
                      Seleccionar archivo
                    </button>
                  </div>
                  <input type="text" class="form-control file-input-display" id="sales-book-file-label" value="Ningún archivo seleccionado" readonly>
                </div>
                @error('sales_book_file')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                  Columnas esperadas: Nro, Fecha, Número factura, NIT/C.I., Razón social, Nombre estudiante,
                  Tipo de pago, Monto, Cuenta, Estado.
                </small>
              </div>
              <button type="submit" class="btn btn-brand btn-sm">Importar reporte diario</button>
            </form>
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
              <div class="small-box bg-success elevation-2">
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
                <small class="text-muted">Gestiona las coincidencias entre extractos y el reporte diario de ingresos.</small>
              </div>
            </div>
          </div>
        </div>
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Conciliación de transacciones</h3>
                <p class="card-subtitle">Cruza extractos bancarios y el reporte diario para cada banco.</p>
              </div>
              <div class="card-tools">
                <div class="form-inline">
                  <div class="form-group mr-2 mb-2 mb-md-0">
                    <select class="form-control" id="bank-filter">
                      <option value="">Todos los bancos</option>
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
                    <h4 class="mb-0">Reporte diario</h4>
                    <span class="badge badge-pill badge-brand" id="report-count">0</span>
                  </div>
                  <div class="list-body" id="report-entry-list">
                    <div class="empty-state" data-empty="report">Selecciona una transacción para ver registros sugeridos.</div>
                  </div>
                </div>
                <div class="col-xl-4 col-lg-3">
                  <div class="p-3 border-bottom">
                    <h4 class="mb-0">Detalle</h4>
                  </div>
                  <div class="detail-body" id="match-detail">
                    <div class="empty-state" data-empty="detail">Selecciona transacción y registro para revisar coincidencia.</div>
                  </div>
                </div>
              </div>
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
                <small class="text-muted">Consulta los saldos acreditados por pagos en demasía.</small>
              </div>
            </div>
          </div>
        </div>
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Estudiantes</h3>
                <p class="card-subtitle">Listado de saldos acreditados por demasía.</p>
              </div>
              <div class="card-tools form-inline">
                <input type="search" class="form-control" id="student-search" placeholder="Buscar por nombre">
              </div>
            </div>
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover" id="student-table">
                <thead class="thead-light">
                  <tr>
                    <th>Estudiante</th>
                    <th>Saldo acreditado</th>
                    <th>Fecha de acreditación</th>
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

    document.querySelectorAll('.file-input-trigger').forEach((button) => {
      button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.target);
        if (target) {
          target.click();
        }
      });
    });

    document.querySelectorAll('.file-input-es').forEach((input) => {
      const label = document.getElementById(input.dataset.label);
      const placeholder = input.dataset.placeholder || 'Ningún archivo seleccionado';

      const updateLabel = () => {
        if (label) {
          label.value = (input.files && input.files.length > 0)
            ? Array.from(input.files).map((file) => file.name).join(', ')
            : placeholder;
        }
      };

      input.addEventListener('change', () => {
        input.setCustomValidity('');
        updateLabel();
      });

      input.addEventListener('invalid', () => {
        if (input.validity.valueMissing) {
          input.setCustomValidity('Selecciona un archivo.');
        }
      });

      input.addEventListener('input', () => input.setCustomValidity(''));

      updateLabel();
    });
  });
</script>
@endpush
