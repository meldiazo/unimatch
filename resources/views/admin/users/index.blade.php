@extends('layouts.ingresos')

@section('title', 'Administración | Usuarios')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">Usuarios</h1>
        <small class="text-muted">Gestiona accesos y roles del sistema.</small>
      </div>
      <a href="{{ route('admin.users.create') }}" class="btn btn-brand btn-sm">Nuevo usuario</a>
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
                <th>Correo</th>
                <th>Rol</th>
                <th>Creado</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($users as $user)
                <tr>
                  <td>{{ $user->name }}</td>
                  <td>{{ $user->email }}</td>
                  <td>{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                  <td>{{ $user->created_at->format('d/m/Y') }}</td>
                  <td class="text-right">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-link btn-sm">Editar</a>
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar usuario?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-link btn-sm text-danger">Eliminar</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">Aún no hay usuarios adicionales.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $users->links() }}
        </div>
      </div>
    </div>
  </section>
@endsection
