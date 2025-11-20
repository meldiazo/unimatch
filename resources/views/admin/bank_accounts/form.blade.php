@extends('layouts.ingresos')

@section('title', $account->exists ? 'Editar cuenta bancaria' : 'Nueva cuenta bancaria')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">{{ $account->exists ? 'Editar cuenta' : 'Nueva cuenta bancaria' }}</h1>
        <small class="text-muted">Asigna la cuenta a un banco y define moneda/estado.</small>
      </div>
      <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-default btn-sm">Volver</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-outline card-brand">
        <div class="card-body">
          <form method="POST" action="{{ $account->exists ? route('admin.bank-accounts.update', $account) : route('admin.bank-accounts.store') }}">
            @csrf
            @if ($account->exists)
              @method('PUT')
            @endif

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="bank_id">Banco</label>
                <select name="bank_id" id="bank_id" class="form-control @error('bank_id') is-invalid @enderror" required>
                  <option value="">Selecciona una opción</option>
                  @foreach ($banks as $bank)
                    <option value="{{ $bank->id }}" {{ old('bank_id', $account->bank_id) == $bank->id ? 'selected' : '' }}>
                      {{ $bank->name }} ({{ $bank->short_code }})
                    </option>
                  @endforeach
                </select>
                @error('bank_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-6">
                <label for="account_number">Número de cuenta</label>
                <input type="text" name="account_number" id="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number', $account->account_number) }}" required>
                @error('account_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="currency">Moneda</label>
                <input type="text" name="currency" id="currency" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $account->currency ?? 'BOB') }}" required>
                @error('currency')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <div class="form-check mt-4 pt-2">
                  <input type="checkbox" name="active" id="active" class="form-check-input" value="1" {{ old('active', $account->active ?? true) ? 'checked' : '' }}>
                  <label for="active" class="form-check-label">Cuenta activa</label>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-brand">
              {{ $account->exists ? 'Actualizar' : 'Crear cuenta' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
