@extends('layouts.app')

@section('body-class', 'hold-transition sidebar-mini layout-fixed')

@section('content')
  @php
    $user = auth()->user();
    $formatMenuActive = request()->routeIs('admin.banks.*') || request()->routeIs('admin.bank-accounts.*') || request()->routeIs('admin.settings.reconciliation.*');
    $userMenuActive = request()->routeIs('admin.students.*') || request()->routeIs('admin.users.*');
  @endphp
  <div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-light navbar-white">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-md-inline-block">
          <span class="navbar-brand mb-0 h5">Panel de administración</span>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a href="{{ route('dashboard') }}" class="btn btn-default btn-sm mr-2">Ir al dashboard</a>
        </li>
        <li class="nav-item">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-brand btn-sm">Cerrar sesión</button>
          </form>
        </li>
      </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-brand elevation-4">
      <a href="{{ route('dashboard') }}" class="brand-link text-center">
        <span class="brand-text font-weight-light">UniMatch · Admin</span>
      </a>
      <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
          <div class="image">
            <span class="avatar-circle">
              {{ collect(preg_split('/\s+/', $user->name, -1, PREG_SPLIT_NO_EMPTY))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
            </span>
          </div>
          <div class="info">
            <span class="d-block user-name">{{ $user->name }}</span>
            <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</small>
          </div>
        </div>
        <nav>
          <ul class="nav nav-pills nav-sidebar flex-column" role="menu" data-widget="treeview" data-accordion="false">
            <li class="nav-item {{ $formatMenuActive ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $formatMenuActive ? 'active' : '' }}">
                <i class="nav-icon fas fa-university"></i>
                <p>
                  Formatos bancarios
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="{{ route('admin.banks.index') }}" class="nav-link {{ request()->routeIs('admin.banks.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Catálogo de bancos</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('admin.bank-accounts.index') }}" class="nav-link {{ request()->routeIs('admin.bank-accounts.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Cuentas bancarias</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('admin.settings.reconciliation.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.reconciliation.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Parámetros de conciliación</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item {{ $userMenuActive ? 'menu-open' : '' }}">
              <a href="#" class="nav-link {{ $userMenuActive ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-cog"></i>
                <p>
                  Gestión de usuarios
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Estudiantes</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Usuarios internos</p>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <div class="content-wrapper">
      @yield('admin-content')
    </div>

    <aside class="control-sidebar control-sidebar-dark"></aside>
  </div>
@endsection
