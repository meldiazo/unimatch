@extends('layouts.ingresos')

@section('title', 'Administración | Estudiantes')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">Estudiantes</h1>
        <small class="text-muted">Mantén actualizado el padrón utilizado en reportes y vouchers.</small>
      </div>
      <a href="{{ route('admin.students.create') }}" class="btn btn-brand btn-sm">Nuevo estudiante</a>
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
                <th>Código</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Documento</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($students as $student)
                <tr>
                  <td>{{ $student->code }}</td>
                  <td>{{ $student->full_name }}</td>
                  <td>{{ $student->email ?? '—' }}</td>
                  <td>{{ $student->meta['document'] ?? '—' }}</td>
                  <td class="text-right">
                    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-link btn-sm">Editar</a>
                    <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar estudiante?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-link btn-sm text-danger">Eliminar</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No hay estudiantes registrados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $students->links() }}
        </div>
      </div>
    </div>
  </section>
@endsection
