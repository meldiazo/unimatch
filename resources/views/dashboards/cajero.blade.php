@extends('layouts.app')

@section('title', 'UniMatch | Consulta de Cajero')

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
          <span class="navbar-brand mb-0 h5">Consulta en ventanilla</span>
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
        <span class="brand-text font-weight-light">UniMatch · Cajero</span>
      </a>
      <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
          <div class="image">
            <span class="avatar-circle">{{ $initials }}</span>
          </div>
          <div class="info">
            <span class="d-block user-name">{{ $user->name }}</span>
            <span class="text-muted user-role">Cajero (solo lectura)</span>
          </div>
        </div>
        <nav>
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item">
              <a href="#" class="nav-link active">
                <i class="nav-icon fas fa-search"></i>
                <p>Consulta de transacciones</p>
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
              <h1 class="m-0">Consulta rápida de pagos</h1>
              <small class="text-muted">Filtra por estudiante, banco o estado para responder en ventanilla.</small>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header">
              <h3 class="card-title mb-0"><i class="fas fa-filter mr-2"></i>Filtros</h3>
            </div>
            <div class="card-body">
              <div class="form-row">
                <div class="form-group col-md-4">
                  <label for="cashier-search">Buscar estudiante / matrícula</label>
                  <input type="search" class="form-control" id="cashier-search" placeholder="Ej. Andrea López o 20210145">
                </div>
                <div class="form-group col-md-4">
                  <label for="cashier-bank">Banco</label>
                  <select id="cashier-bank" class="form-control">
                    <option selected>Todos</option>
                    <option>Banco Nacional de Bolivia</option>
                    <option>Banco Unión</option>
                  </select>
                </div>
                <div class="form-group col-md-4">
                  <label for="cashier-status">Estado</label>
                  <select id="cashier-status" class="form-control">
                    <option>Todos</option>
                    <option>Facturado</option>
                    <option>Pendiente</option>
                    <option>Pago en demasía</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="card card-outline card-brand">
            <div class="card-header">
              <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Resultados de búsqueda</h3>
              <span class="badge badge-info">Solo lectura</span>
            </div>
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Estudiante</th>
                    <th>Matrícula</th>
                    <th>Banco</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Factura</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="cashier-tbody">
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      Usa los filtros para buscar transacciones.
                    </td>
                  </tr>
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
  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('cashier-search');
    const bankSelect = document.getElementById('cashier-bank');
    const statusSelect = document.getElementById('cashier-status');
    const tbody = document.getElementById('cashier-tbody');

    const performSearch = async () => {
      const params = new URLSearchParams({
        query: searchInput.value,
        bank_id: bankSelect.value || '',
        status: statusSelect.value || '',
      });

      try {
        const response = await fetch(`/api/cashier/transactions/search?${params}`, {
          headers: { 
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (!response.ok) throw new Error('Error en la búsqueda');
        
        const data = await response.json();
        tbody.innerHTML = '';

        if (!data.data || data.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No se encontraron resultados.</td></tr>';
          return;
        }

        data.data.forEach(tx => {
          const row = document.createElement('tr');
          const statusBadge = `badge-${
            tx.status === 'recibido' ? 'info' : 
            tx.status === 'validado' ? 'success' : 
            tx.status === 'rechazado' ? 'danger' : 'warning'
          }`;

          const billingBadge = `badge-${
            tx.billing_status === 'facturado' ? 'success' :
            tx.billing_status === 'pendiente' ? 'warning' : 'secondary'
          }`;

          row.innerHTML = `
            <td>${tx.student_name}</td>
            <td><code>${tx.student_code}</code></td>
            <td>${tx.bank}</td>
            <td class="text-right"><strong>Bs ${parseFloat(tx.amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</strong></td>
            <td><span class="badge ${statusBadge}">${tx.status}</span></td>
            <td><span class="badge ${billingBadge}">${tx.billing_status}</span></td>
            <td>
              ${tx.document_path ? `
                <div class="btn-group" role="group">
                  <a href="/storage/${tx.document_path}" target="_blank" class="btn btn-link btn-sm" title="Ver voucher">
                    <i class="fas fa-file-pdf text-danger"></i> Voucher
                  </a>
                  <a href="/cashier/vouchers/${tx.id}/certificate" class="btn btn-link btn-sm" title="Descargar constancia">
                    <i class="fas fa-download text-primary"></i> Constancia
                  </a>
                </div>
              ` : '<span class="text-muted">—</span>'}
            </td>
          `;
          tbody.appendChild(row);
        });
      } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="alert alert-danger mb-0">Error al buscar. Intenta de nuevo.</td></tr>';
      }
    };

    // Ejecutar búsqueda al escribir o cambiar filtros
    [searchInput, bankSelect, statusSelect].forEach(el => {
      el.addEventListener('change', performSearch);
    });
    searchInput.addEventListener('keyup', performSearch);
  });
</script>
@endpush