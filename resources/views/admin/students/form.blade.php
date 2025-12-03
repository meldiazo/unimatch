@extends('layouts.ingresos')

@section('title', $student->exists ? 'Editar estudiante' : 'Nuevo estudiante')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">{{ $student->exists ? 'Editar estudiante' : 'Nuevo estudiante' }}</h1>
        <small class="text-muted">Registra códigos y datos de contacto.</small>
      </div>
      <a href="{{ route('admin.students.index') }}" class="btn btn-default btn-sm">Volver</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-outline card-brand">
        <div class="card-body">
          <form method="POST" action="{{ $student->exists ? route('admin.students.update', $student) : route('admin.students.store') }}">
            @csrf
            @if ($student->exists)
              @method('PUT')
            @endif

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="code">Código</label>
                <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $student->code) }}" required>
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-8">
                <label for="full_name">Nombre completo</label>
                <input type="text" name="full_name" id="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $student->full_name) }}" required>
                @error('full_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email) }}">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-6">
                <label for="document">Documento (NIT/CI)</label>
                <input type="text" name="document" id="document" class="form-control @error('document') is-invalid @enderror" value="{{ old('document', $student->meta['document'] ?? '') }}">
                @error('document')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <button type="submit" class="btn btn-brand">
              {{ $student->exists ? 'Actualizar' : 'Guardar estudiante' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
