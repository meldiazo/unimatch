@extends('layouts.ingresos')

@section('title', $user->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">{{ $user->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
        <small class="text-muted">Asigna nombre, correo y rol.</small>
      </div>
      <a href="{{ route('admin.users.index') }}" class="btn btn-default btn-sm">Volver</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-outline card-brand">
        <div class="card-body">
          <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if ($user->exists)
              @method('PUT')
            @endif

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="name">Nombre completo</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-6">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="role">Rol</label>
                <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                  @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" {{ old('role', $user->role ?? '') === $value ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                @error('role')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="password">{{ $user->exists ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" {{ $user->exists ? '' : 'required' }}>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <button type="submit" class="btn btn-brand">
              {{ $user->exists ? 'Actualizar' : 'Crear usuario' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
