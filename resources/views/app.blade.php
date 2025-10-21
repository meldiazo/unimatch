@extends('layouts.app')

@section('title', 'UniMatch')

@section('body-class', 'hold-transition sidebar-mini layout-fixed')

@section('content')
  <div class="login-page" id="login-screen">
    <div class="login-box">
      <div class="login-logo">
        <span class="text-brand">UniMatch</span>
      </div>
      <div class="card card-outline card-brand">
        <div class="card-header text-center">
          <h3 class="mb-0">Módulo de Conciliación</h3>
          <p class="text-muted mb-0">Ingresa con tu correo institucional</p>
        </div>
        <div class="card-body">
          <form id="login-form">
            <div class="input-group mb-3">
              <input type="email" class="form-control" id="login-email" placeholder="Correo institucional" required value="contabilidad@uni.edu">
              <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" class="form-control" id="login-password" placeholder="Contraseña" required value="123456">
              <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock"></span></div>
              </div>
            </div>
            <div class="row align-items-center mb-3">
              <div class="col-6">
                <div class="icheck-brand">
                  <input type="checkbox" id="login-remember" checked>
                  <label for="login-remember">Recordarme</label>
                </div>
              </div>
              <div class="col-6 text-right">
                <button type="submit" class="btn btn-brand">Ingresar</button>
              </div>
            </div>
            <p class="text-danger small text-center" id="login-error" hidden>Credenciales inválidas. Intenta nuevamente.</p>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="wrapper d-none" id="app-wrapper">
    <nav class="main-header navbar navbar-expand navbar-light navbar-white">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-md-inline-block">
          <span class="navbar-brand mb-0 h5">Módulo de conciliación</span>
        </li>
      </ul>
      <form class="form-inline ml-auto">
        <div class="input-group input-group-sm">
          <input class="form-control form-control-navbar" type="search" placeholder="Buscar transacciones, vouchers o estudiantes" aria-label="Buscar" id="global-search">
          <div class="input-group-append">
            <button class="btn btn-navbar" type="submit" disabled>
              <i class="fas fa-search"></i>
            </button>
          </div>
        </div>
      </form>
      <ul class="navbar-nav ml-3">
        <li class="nav-item">
          <button class="btn btn-outline-brand btn-sm" id="new-voucher" disabled>Nuevo voucher</button>
        </li>
        <li class="nav-item ml-2">
          <button class="btn btn-default btn-sm" id="logout-button">Cerrar sesión</button>
        </li>
      </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-brand elevation-4">
      <a href="#" class="brand-link text-center">
        <span class="brand-text font-weight-light">UniMatch Contabilidad</span>
      </a>
      <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
          <div class="image">
            <span class="avatar-circle" id="sidebar-avatar">NP</span>
          </div>
          <div class="info">
            <a href="#" class="d-block user-name">Norma Paris</a>
            <span class="text-muted user-role">Analista contable</span>
          </div>
        </div>
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
            <li class="nav-item">
              <a href="#" class="nav-link active" data-target="dashboard">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Panel general</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" data-target="matching">
                <i class="nav-icon fas fa-random"></i>
                <p>Coincidencias</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" data-target="reconciliations">
                <i class="nav-icon fas fa-check-circle"></i>
                <p>Conciliaciones</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" data-target="students">
                <i class="nav-icon fas fa-user-graduate"></i>
                <p>Estudiantes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" data-target="banks">
                <i class="nav-icon fas fa-university"></i>
                <p>Bancos</p>
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
              <h1 class="m-0">Resumen general</h1>
              <small class="text-muted">Monitorea transacciones QR y vouchers para seis bancos.</small>
            </div>
            <div class="col-sm-6 text-sm-right">
              <div class="btn-group" role="group" aria-label="Rango">
                <button type="button" class="btn btn-outline-brand btn-sm toggle active" data-range="7">7 días</button>
                <button type="button" class="btn btn-outline-brand btn-sm toggle" data-range="30">30 días</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content view" data-view="dashboard">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-3 col-sm-6 mb-3">
              <div class="info-box">
                <span class="info-box-icon bg-brand"><i class="fas fa-hourglass-half"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Transacciones en espera</span>
                  <span class="info-box-number" id="pending-count">0</span>
                  <span class="info-box-desc">Última sincronización <span id="last-sync">hace 2 minutos</span></span>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
              <div class="info-box">
                <span class="info-box-icon bg-brand-light"><i class="fas fa-lightbulb"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Coincidencias sugeridas</span>
                  <span class="info-box-number" id="suggested-count">0</span>
                  <span class="info-box-desc">Basado en monto + referencia</span>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
              <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Conciliaciones completadas</span>
                  <span class="info-box-number" id="matched-count">0</span>
                  <span class="info-box-desc">Últimos 7 días</span>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
              <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Alertas</span>
                  <span class="info-box-number" id="alerts-count">0</span>
                  <span class="info-box-desc">Discrepancias actuales</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-7">
              <div class="card card-outline card-brand">
                <div class="card-header">
                  <h3 class="card-title">Tendencia de conciliaciones</h3>
                </div>
                <div class="card-body">
                  <div class="chart-placeholder">
                    <canvas id="trend-chart" aria-label="Tendencia de conciliaciones"></canvas>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="card card-outline card-brand">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h3 class="card-title">Alertas recientes</h3>
                  <button class="btn btn-link btn-sm" id="view-all-alerts">Ver todas</button>
                </div>
                <div class="card-body">
                  <div class="empty-state" id="alerts-empty">
                    <p class="mb-0">Sin alertas críticas. Todo al día.</p>
                  </div>
                  <ul class="alert-list" id="alert-list"></ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content view d-none" data-view="matching">
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Coincidencias pendientes</h3>
                <p class="card-subtitle">Selecciona una transacción para sugerir el voucher correcto.</p>
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
                      <option value="pending">Pendientes</option>
                      <option value="suggested">Con sugerencia</option>
                      <option value="flagged">Con alerta</option>
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
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Conciliaciones recientes</h3>
                <p class="card-subtitle">Historial de conciliaciones confirmadas por banco.</p>
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
                    <th>Voucher</th>
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
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Estudiantes</h3>
                <p class="card-subtitle">Revisa pagos recientes y su estado de conciliación.</p>
              </div>
              <div class="card-tools form-inline">
                <input type="search" class="form-control mr-2" id="student-search" placeholder="Buscar por nombre o matrícula">
                <select class="form-control" id="student-status">
                  <option value="">Todos</option>
                  <option value="matched">Conciliado</option>
                  <option value="pending">Pendiente</option>
                </select>
              </div>
            </div>
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover" id="student-table">
                <thead class="thead-light">
                  <tr>
                    <th>Estudiante</th>
                    <th>Matrícula</th>
                    <th>Programa</th>
                    <th>Último pago</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <section class="content view d-none" data-view="banks">
        <div class="container-fluid">
          <div class="card card-outline card-brand">
            <div class="card-header d-md-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title">Bancos conectados</h3>
                <p class="card-subtitle">Configura parámetros de importación para cada banco.</p>
              </div>
              <button class="btn btn-brand" id="sync-banks" disabled>Sincronizar</button>
            </div>
            <div class="card-body">
              <div class="row" id="bank-grid"></div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <aside class="control-sidebar control-sidebar-dark"></aside>
  </div>

  <div class="toast toast-brand" id="toast" aria-live="assertive" hidden></div>

  <div class="modal-overlay d-none" id="match-modal" role="dialog" aria-modal="true">
    <div class="modal-card">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Confirmar coincidencia</h3>
        <button class="close" data-close-modal>&times;</button>
      </div>
      <div class="modal-body" id="modal-body"></div>
      <div class="modal-footer">
        <button class="btn btn-default" data-close-modal>Cancelar</button>
        <button class="btn btn-brand" id="confirm-match">Confirmar</button>
      </div>
    </div>
  </div>
@endsection
