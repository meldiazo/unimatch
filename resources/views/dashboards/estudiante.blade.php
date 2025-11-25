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
              <a href="#" class="nav-link active">
                <i class="nav-icon fas fa-receipt"></i>
                <p>Mis vouchers</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#balance-card" class="nav-link">
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
              <h1 class="m-0">Mis pagos y comprobantes</h1>
              <small class="text-muted">Sube tus vouchers y monitorea su estado.</small>
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

          <div class="card card-outline card-brand mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0"><i class="fas fa-upload mr-2"></i>Subir nuevo voucher</h3>
              <span class="badge badge-info">Adjunta PDF o imagen</span>
            </div>
            <div class="card-body">
              @if (! $studentRecord)
                <div class="alert alert-warning">
                  No encontramos tu registro en el padrón de estudiantes. Comunícate con ingresos para habilitar tu acceso.
                </div>
              @else
                <form action="{{ route('student.vouchers.store') }}" method="POST" enctype="multipart/form-data">
                  @csrf
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="student-code">Código de estudiante</label>
                      <input
                        type="text"
                        name="student_code"
                        id="student-code"
                        class="form-control @error('student_code') is-invalid @enderror"
                        value="{{ old('student_code', $studentRecord->code ?? '') }}"
                        placeholder="Ej: 20210001"
                        required
                      >
                      @error('student_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="form-group col-md-6">
                      <label for="student-bank">Banco (opcional)</label>
                      <select name="bank_id" id="student-bank" class="form-control @error('bank_id') is-invalid @enderror">
                        <option value="">Selecciona banco</option>
                        @foreach ($banks as $bank)
                          <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                        @endforeach
                      </select>
                      @error('bank_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="form-group col-md-6">
                      <label for="student-account-select">Cuenta bancaria (opcional)</label>
                      <select name="bank_account_id" id="student-account-select" class="form-control @error('bank_account_id') is-invalid @enderror">
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
                    <div class="form-group col-md-3">
                      <label for="student-paid-at">Fecha del pago</label>
                      <input type="date" name="paid_at" id="student-paid-at" class="form-control @error('paid_at') is-invalid @enderror" value="{{ old('paid_at') }}" required>
                      @error('paid_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="form-group col-md-3">
                      <label for="student-amount">Monto (Bs.)</label>
                      <input type="number" step="0.01" name="amount" id="student-amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                      @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="student-payment-type">Tipo de pago</label>
                      <input type="text" name="payment_type" id="student-payment-type" class="form-control @error('payment_type') is-invalid @enderror" value="{{ old('payment_type', 'Transferencia') }}" required>
                      @error('payment_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="form-group col-md-4">
                      <label for="student-operation">N° de operación</label>
                      <input type="text" name="operation_number" id="student-operation" class="form-control @error('operation_number') is-invalid @enderror" value="{{ old('operation_number') }}" required>
                      @error('operation_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="student-account">Referencia de cuenta (opcional)</label>
                      <input type="text" name="account_reference" id="student-account" class="form-control @error('account_reference') is-invalid @enderror" value="{{ old('account_reference') }}" placeholder="Ej: Caja central">
                      @error('account_reference')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="student-file">Comprobante (.pdf, .jpg, .png)</label>
                    <input type="file" name="voucher_file" id="student-file" class="form-control-file @error('voucher_file') is-invalid @enderror" required>
                    @error('voucher_file')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>
                  <button type="submit" class="btn btn-brand">Enviar voucher</button>
                </form>
              @endif
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
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($studentVouchers as $voucher)
                    <tr>
                      <td>{{ optional($voucher->paid_at)->format('d/m/Y') ?? '—' }}</td>
                      <td>{{ $voucher->bank->name ?? '—' }}</td>
                      <td>Bs {{ number_format($voucher->amount, 2, ',', '.') }}</td>
                      <td>
                        <span class="badge {{ $voucher->status === 'recibido' ? 'badge-info' : 'badge-success' }}">
                          {{ ucfirst($voucher->status) }}
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
                      <td>
                        @if ($voucher->status === 'rechazado')
                          <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#replaceModal{{ $voucher->id }}">
                            <i class="fas fa-redo"></i> Reemplazar
                          </button>
                          
                          <!-- Modal para reemplazar -->
                          <div class="modal fade" id="replaceModal{{ $voucher->id }}" tabindex="-1">
                            <div class="modal-dialog">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title">Reemplazar voucher rechazado</h5>
                                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <form action="{{ route('student.vouchers.replace', $voucher) }}" method="POST" enctype="multipart/form-data">
                                  @csrf
                                  @method('PATCH')
                                  <div class="modal-body">
                                    <div class="alert alert-info">
                                      <strong>Motivo del rechazo:</strong> {{ $voucher->reason ?? 'No especificado' }}
                                    </div>
                                    <div class="form-group">
                                      <label for="file{{ $voucher->id }}">Nuevo comprobante (PDF, JPG, PNG)</label>
                                      <input type="file" name="voucher_file" id="file{{ $voucher->id }}" class="form-control-file" required>
                                    </div>
                                    <div class="form-group">
                                      <label for="notes{{ $voucher->id }}">Notas (opcional)</label>
                                      <textarea name="notes" id="notes{{ $voucher->id }}" class="form-control" rows="2" placeholder="Explica qué cambios realizaste..."></textarea>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-brand">Enviar nuevo comprobante</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4">
                        Aún no registraste vouchers.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </div>

    <aside class="control-sidebar control-sidebar-dark"></aside>
  </div>
@endsection

@push('scripts')
<script>
  const setupStudentAccounts = () => {
    const bankSelect = document.getElementById('student-bank');
    const accountSelect = document.getElementById('student-account-select');

    if (!bankSelect || !accountSelect) {
      return;
    }

    const baseOptions = Array.from(accountSelect.querySelectorAll('option[data-bank]'));
    const placeholder = accountSelect.querySelector('option[value=""]');

    const renderAccounts = () => {
      const selectedBank = bankSelect.value;
      const previous = accountSelect.value;
      accountSelect.innerHTML = '';

      if (placeholder) {
        accountSelect.appendChild(placeholder.cloneNode(true));
      }

      baseOptions.forEach((option) => {
        if (!selectedBank || option.dataset.bank === selectedBank) {
          accountSelect.appendChild(option.cloneNode(true));
        }
      });

      if (previous) {
        accountSelect.value = previous;
      }
    };

    renderAccounts();
    bankSelect.addEventListener('change', renderAccounts);
  };

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
    setupStudentAccounts();
    loadStudentBalance();
  });
</script>
@endpush
