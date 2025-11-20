hace@extends('layouts.app')

@section('body-class', 'hold-transition sidebar-mini layout-fixed')

@section('content')
  @php
    $user = auth()->user();
    $defaultRoleLabel = match ($user?->role) {
      \App\Models\User::ROLE_JEFE_CONTABILIDAD => 'Jefe de contabilidad',
      \App\Models\User::ROLE_ENCARGADO_INGRESOS => 'Encargado de ingresos',
      default => ucwords(str_replace('_', ' ', $user->role ?? 'Usuario')),
    };
    $roleLabel = trim($__env->yieldContent('panel-role-label')) ?: $defaultRoleLabel;
    $initials = collect(preg_split('/\s+/', $user->name, -1, PREG_SPLIT_NO_EMPTY))
      ->map(fn ($part) => mb_substr($part, 0, 1))
      ->take(2)
      ->implode('');
    $wrapperAttributes = trim($__env->yieldContent('panel-wrapper-attrs') ?? '');
    $wrapperAttributes = $wrapperAttributes !== '' ? ' ' . $wrapperAttributes : '';
    $rawActiveMenu = trim($__env->yieldContent('panel-active-menu') ?? '');
    $hasDynamicMenu = $rawActiveMenu === '' && request()->routeIs('dashboard');
    $activeMenu = $rawActiveMenu !== '' ? $rawActiveMenu : ($hasDynamicMenu ? 'dashboard' : null);
    $isExecutivePanel = $user?->role === \App\Models\User::ROLE_JEFE_CONTABILIDAD;
    $panelTitle = $isExecutivePanel ? 'Panel ejecutivo · Jefatura de contabilidad' : 'Panel operativo de ingresos';
    $brandLabel = $isExecutivePanel ? 'UniMatch · Jefatura' : 'UniMatch · Ingresos';
    $dashboardRoute = $isExecutivePanel ? route('admin.dashboard') : route('dashboard');
    $banksActive = request()->routeIs('admin.banks.*');
    $accountsActive = request()->routeIs('admin.bank-accounts.*');
    $settingsActive = request()->routeIs('admin.settings.reconciliation.*');
    $catalogActive = $banksActive || $accountsActive || $settingsActive;
    $usersActive = request()->routeIs('admin.users.*');
    $reportActive = request()->routeIs('reports.pagos');
  @endphp

  <div class="wrapper" id="app-wrapper"
       data-user-name="{{ $user->name }}"
       data-user-role="{{ $roleLabel }}"
       data-user-email="{{ $user->email }}"
       {!! $wrapperAttributes !!}>
    <nav class="main-header navbar navbar-expand navbar-light navbar-white">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-md-inline-block">
          <span class="navbar-brand mb-0 h5">{{ $panelTitle }}</span>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-default btn-sm">Cerrar sesión</button>
          </form>
        </li>
      </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-brand elevation-4">
      <a href="{{ $dashboardRoute }}" class="brand-link text-center">
        <span class="brand-text font-weight-light">{{ $brandLabel }}</span>
      </a>
      <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
          <div class="image">
            <span class="avatar-circle" id="sidebar-avatar">{{ $initials }}</span>
          </div>
          <div class="info">
            <span class="d-block user-name">{{ $user->name }}</span>
            <span class="text-muted user-role">{{ $roleLabel }}</span>
          </div>
        </div>
        <nav>
          @if ($isExecutivePanel)
            <ul class="nav nav-pills nav-sidebar flex-column">
              <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                  <i class="nav-icon fas fa-chart-pie"></i>
                  <p>Tablero ejecutivo</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.banks.index') }}" class="nav-link {{ $banksActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-university"></i>
                  <p>Bancos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.bank-accounts.index') }}" class="nav-link {{ $accountsActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-piggy-bank"></i>
                  <p>Cuentas bancarias</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ $usersActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-users-cog"></i>
                  <p>Gestión de usuarios</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('reports.pagos') }}" class="nav-link {{ $reportActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-file-excel"></i>
                  <p>Reporte de pagos</p>
                </a>
              </li>
            </ul>
          @else
            <ul class="nav nav-pills nav-sidebar flex-column" role="menu" data-widget="treeview" data-accordion="false">
              <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ $activeMenu === 'dashboard' ? 'active' : '' }}" data-target="dashboard">
                  <i class="nav-icon fas fa-tachometer-alt"></i>
                  <p>Tablero ejecutivo</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard', ['view' => 'matching']) }}" class="nav-link {{ $activeMenu === 'matching' ? 'active' : '' }}" data-target="matching">
                  <i class="nav-icon fas fa-random"></i>
                  <p>Conciliación</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard', ['view' => 'reconciliations']) }}" class="nav-link {{ $activeMenu === 'reconciliations' ? 'active' : '' }}" data-target="reconciliations">
                  <i class="nav-icon fas fa-file-invoice-dollar"></i>
                  <p>Facturación</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard', ['view' => 'students']) }}" class="nav-link {{ $activeMenu === 'students' ? 'active' : '' }}" data-target="students">
                  <i class="nav-icon fas fa-user-check"></i>
                  <p>Seguimiento de estudiantes</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('reports.pagos') }}" class="nav-link {{ $reportActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-file-excel"></i>
                  <p>Reporte de pagos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.banks.index') }}" class="nav-link {{ $banksActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-university"></i>
                  <p>Catálogo de bancos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.bank-accounts.index') }}" class="nav-link {{ $accountsActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-piggy-bank"></i>
                  <p>Cuentas bancarias</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('admin.settings.reconciliation.edit') }}" class="nav-link {{ $settingsActive ? 'active' : '' }}">
                  <i class="nav-icon fas fa-sliders-h"></i>
                  <p>Parámetros de conciliación</p>
                </a>
              </li>
            </ul>
          @endif
        </nav>
      </div>
    </aside>

    <div class="content-wrapper">
      @yield('panel-content')
    </div>

    <aside class="control-sidebar control-sidebar-dark"></aside>
  </div>
@endsection
