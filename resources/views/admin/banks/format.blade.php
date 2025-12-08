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
        $strategy = old('strategy', $config['strategy'] ?? 'fixed');
      @endphp

      @if ($readOnly)
        <div class="alert alert-warning">
          Solo la jefatura puede modificar formatos. Estás viendo la configuración vigente para este banco.
        </div>
      @endif

      <div class="card card-outline card-brand">
        <div class="card-body">
          @if ($preset)
            <div class="mb-3">
              <h6 class="text-muted mb-1">Formato fijo detectado ({{ $preset['nombre'] }})</h6>
              <p class="small text-muted mb-2">
                Columnas esperadas: {{ implode(', ', $preset['columnas']) }}.<br>
                {{ $preset['nota'] ?? '' }}
              </p>
            </div>
          @endif

          <form method="POST" action="{{ route('admin.banks.format.update', $bank) }}">
            @csrf
            @method('PUT')

            <div class="alert alert-info py-2 px-3">
              <strong>Paso 1.</strong> Elige si usas el formato fijo del banco o uno personalizado.<br>
              <strong>Paso 2.</strong> Si eliges personalizado, indica fila de encabezado, formato de fecha y el nombre exacto de cada columna tal como aparece en el Excel.
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="strategy">Modo de lectura</label>
                <select name="strategy" id="strategy" class="form-control @error('strategy') is-invalid @enderror" @disabled($readOnly)>
                  <option value="fixed" {{ $strategy === 'fixed' ? 'selected' : '' }}>Formato fijo (según banco)</option>
                  <option value="custom" {{ $strategy === 'custom' ? 'selected' : '' }}>Formato personalizado (definir columnas)</option>
                </select>
                @error('strategy')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="date_format">Formato de fecha (PHP)</label>
                <input
                  type="text"
                  name="date_format"
                  id="date_format"
                  class="form-control @error('date_format') is-invalid @enderror"
                  value="{{ old('date_format', $config['date_format'] ?? 'Y-m-d') }}"
                  placeholder="Y-m-d"
                  @disabled($readOnly || $strategy !== 'custom')
                >
                <small class="form-text text-muted">Ejemplos: <code>Y-m-d</code>, <code>d/m/Y</code>, <code>d/m/y</code>.</small>
                @error('date_format')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="header_row">Fila de encabezado</label>
                <input
                  type="number"
                  name="header_row"
                  id="header_row"
                  class="form-control @error('header_row') is-invalid @enderror"
                  value="{{ old('header_row', $config['header_row'] ?? 1) }}"
                  min="1"
                  @disabled($readOnly || $strategy !== 'custom')
                >
                <small class="form-text text-muted">Usa 1 si los títulos están en la primera fila.</small>
                @error('header_row')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="alert alert-secondary py-2 px-3 {{ $strategy === 'custom' ? '' : 'd-none' }}" id="custom-helper">
              <strong>Mapeo de columnas:</strong> indica el número de columna y/o el encabezado exacto tal como viene en el Excel (ej. “Fecha”, “Hora”, “N° Operaciones”, “Saldo”). Si una columna no existe en tu archivo, déjala vacía.
            </div>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead class="thead-light">
                  <tr>
                    <th>Campo requerido</th>
                    <th style="width: 180px;">N° de columna en Excel</th>
                    <th>Encabezado en tu Excel</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($columns as $key => $label)
                    <tr>
                      <td class="font-weight-bold">{{ $label }}</td>
                      <td>
                        <input
                          type="number"
                          name="columns_index[{{ $key }}]"
                          class="form-control form-control-sm @error('columns_index.'.$key) is-invalid @enderror"
                          value="{{ old("columns_index.$key", $config['columns_index'][$key] ?? '') }}"
                          min="1"
                          placeholder="Ej. 1"
                          @disabled($readOnly || $strategy !== 'custom')
                        >
                        @error('columns_index.'.$key)
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </td>
                      <td style="width: 320px;">
                        <input
                          type="text"
                          name="columns[{{ $key }}]"
                          class="form-control form-control-sm @error('columns.'.$key) is-invalid @enderror"
                          value="{{ old("columns.$key", $strategy === 'custom' ? ($config['columns'][$key] ?? '') : '') }}"
                          placeholder="Ej. {{ $label }}"
                          @disabled($readOnly || $strategy !== 'custom')
                        >
                        @error('columns.'.$key)
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            @if (! $readOnly)
              <button type="submit" class="btn btn-brand">Guardar formato</button>
            @endif

            @if ($preset && ! $readOnly)
              <p class="small text-muted mt-3 mb-0">
                Si el banco cambia su layout, usa “Formato personalizado” para ajustar las columnas sin depender de desarrollo.
              </p>
            @endif
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
