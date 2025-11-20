@extends('layouts.ingresos')

@section('title', $bank->exists ? 'Editar banco' : 'Nuevo banco')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">{{ $bank->exists ? 'Editar banco' : 'Nuevo banco' }}</h1>
        <small class="text-muted">Define nombre, código y formato de importación.</small>
      </div>
      <a href="{{ route('admin.banks.index') }}" class="btn btn-default btn-sm">Volver</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-outline card-brand">
        <div class="card-body">
          <form method="POST" action="{{ $bank->exists ? route('admin.banks.update', $bank) : route('admin.banks.store') }}">
            @csrf
            @if ($bank->exists)
              @method('PUT')
            @endif

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="name">Nombre</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $bank->name) }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-3">
                <label for="short_code">Código</label>
                <input type="text" name="short_code" id="short_code" class="form-control @error('short_code') is-invalid @enderror" value="{{ old('short_code', $bank->short_code) }}" required>
                @error('short_code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-3">
                <label for="status">Estado</label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                  <option value="active" {{ old('status', $bank->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                  <option value="inactive" {{ old('status', $bank->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
                @error('status')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="form-group">
              <label for="format_config">Configuración de formato (JSON)</label>
              <textarea name="format_config" id="format_config" rows="6" class="form-control @error('format_config') is-invalid @enderror" placeholder='{"columns":{"operation_number":"operacion"}}'>{{ old('format_config', $bank->format_config ? json_encode($bank->format_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
              @error('format_config')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <button type="submit" class="btn btn-brand">
              {{ $bank->exists ? 'Actualizar' : 'Crear banco' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
