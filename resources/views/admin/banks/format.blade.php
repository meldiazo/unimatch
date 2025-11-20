@extends('layouts.ingresos')

@section('title', 'Formato bancario · '.$bank->name)

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">Formato de importación · {{ $bank->name }}</h1>
        <small class="text-muted">Define cómo se nombran las columnas en el archivo CSV del banco.</small>
      </div>
      <div class="btn-group btn-group-sm">
        <a href="{{ route('admin.banks.index') }}" class="btn btn-default">Volver</a>
        <a href="{{ route('admin.banks.edit', $bank) }}" class="btn btn-outline-brand">Configurar banco</a>
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

      @php
        $readOnly = empty($canManageFormats);
      @endphp

      @if ($readOnly)
        <div class="alert alert-warning">
          Solo la jefatura puede modificar formatos. Estás viendo la configuración vigente para este banco.
        </div>
      @endif

      <div class="card card-outline card-brand">
        <div class="card-body">
          <p class="text-muted">
            Completa el nombre exacto de cada columna según aparece en el archivo que entrega el banco.
            Si el campo no existe, déjalo vacío. Estos valores se usarán para mapear automáticamente cada carga.
          </p>
          <form method="POST" action="{{ route('admin.banks.format.update', $bank) }}">
            @csrf
            @method('PUT')

            <div class="table-responsive">
              <table class="table table-striped">
                <thead class="thead-light">
                  <tr>
                    <th>Campo del sistema</th>
                    <th>Nombre en el CSV</th>
                    <th>Descripción</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($columns as $key => $label)
                    <tr>
                      <td><code>{{ $key }}</code></td>
                      <td style="width: 320px;">
                        <input
                          type="text"
                          name="columns[{{ $key }}]"
                          class="form-control form-control-sm @error('columns.'.$key) is-invalid @enderror"
                          value="{{ old("columns.$key", $config['columns'][$key] ?? '') }}"
                          placeholder="Ej. Nro Operación"
                          @disabled($readOnly)
                        >
                        @error('columns.'.$key)
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </td>
                      <td class="text-muted text-sm">{{ $label }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="form-group mt-3">
              <label for="date_format">Formato de fecha (PHP)</label>
              <input
                type="text"
                name="date_format"
                id="date_format"
                class="form-control @error('date_format') is-invalid @enderror"
                value="{{ old('date_format', $config['date_format'] ?? 'Y-m-d') }}"
                placeholder="Y-m-d"
                @disabled($readOnly)
              >
              <small class="form-text text-muted">Ejemplos: <code>Y-m-d</code>, <code>d/m/Y</code>, <code>d-m-Y H:i</code>.</small>
              @error('date_format')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

              @if (! $readOnly)
                <button type="submit" class="btn btn-brand">Guardar formato</button>
              @endif
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
