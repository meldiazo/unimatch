@extends('layouts.ingresos')

@section('title', 'Administración | Bancos')

@section('panel-content')
  @php
    $canManageBanks = auth()->user()->can('manage-bank-settings');
  @endphp

  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">Bancos</h1>
        <small class="text-muted">Configura catálogos para importaciones y reportes.</small>
      </div>
      @if ($canManageBanks)
        <a href="{{ route('admin.banks.create') }}" class="btn btn-brand btn-sm">Nuevo banco</a>
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
                <th>Nombre</th>
                <th>Código</th>
                <th>Estado</th>
                <th>Última actualización</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($banks as $bank)
                <tr>
                  <td>{{ $bank->name }}</td>
                  <td>{{ $bank->short_code }}</td>
                  <td>
                    <span class="badge {{ $bank->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                      {{ $bank->status === 'active' ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td>{{ $bank->updated_at->format('d/m/Y H:i') }}</td>
                  <td class="text-right">
                    <a href="{{ route('admin.banks.format', $bank) }}" class="btn btn-link btn-sm">Formato</a>
                    @if ($canManageBanks)
                      <form action="{{ route('admin.banks.destroy', $bank) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar banco?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link btn-sm text-danger">Eliminar</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">Aún no hay bancos configurados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $banks->links() }}
        </div>
      </div>
    </div>
  </section>
@endsection
