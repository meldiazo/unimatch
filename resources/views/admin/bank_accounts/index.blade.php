@extends('layouts.ingresos')

@section('title', 'Administración | Cuentas bancarias')

@section('panel-content')
  @php
    $canManageAccounts = auth()->user()->can('manage-bank-settings');
  @endphp

  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">Cuentas bancarias</h1>
        <small class="text-muted">Define las cuentas disponibles para importaciones y registros manuales.</small>
      </div>
      @if ($canManageAccounts)
        <a href="{{ route('admin.bank-accounts.create') }}" class="btn btn-brand btn-sm">Nueva cuenta</a>
      @endif
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

      <div class="card card-outline card-brand">
        <div class="card-body p-0 table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Banco</th>
                <th>Número de cuenta</th>
                <th>Moneda</th>
                <th>Estado</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($accounts as $account)
                <tr>
                  <td>{{ $account->bank->name ?? '—' }}</td>
                  <td>{{ $account->account_number }}</td>
                  <td>{{ $account->currency }}</td>
                  <td>
                    <span class="badge {{ $account->active ? 'badge-success' : 'badge-secondary' }}">
                      {{ $account->active ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>
                  <td class="text-right">
                    @if ($canManageAccounts)
                      <a href="{{ route('admin.bank-accounts.edit', $account) }}" class="btn btn-link btn-sm">Editar</a>
                      <form action="{{ route('admin.bank-accounts.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar cuenta bancaria?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link btn-sm text-danger">Eliminar</button>
                      </form>
                    @else
                      <span class="text-muted">Solo lectura</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">Todavía no registraste cuentas.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $accounts->links() }}
        </div>
      </div>
    </div>
  </section>
@endsection
