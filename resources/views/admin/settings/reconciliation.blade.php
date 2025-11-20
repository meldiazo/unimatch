@extends('layouts.ingresos')

@section('title', 'Parámetros de conciliación')

@section('panel-content')
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div>
        <h1 class="m-0">Parámetros de conciliación</h1>
        <small class="text-muted">Configura los umbrales que generan alertas y límites de crédito.</small>
      </div>
      <a href="{{ route('admin.banks.index') }}" class="btn btn-default btn-sm">Volver</a>
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
        $readOnly = empty($canManageSettings);
        $statusList = collect($settings->voucher_statuses ?? [])->implode(PHP_EOL);
      @endphp

      @if ($readOnly)
        <div class="alert alert-warning">
          Solo la jefatura puede modificar estos parámetros. Estás viendo la configuración vigente.
        </div>
      @endif

      <div class="card card-outline card-brand">
        <div class="card-body">
          <form method="POST" action="{{ route('admin.settings.reconciliation.update') }}">
            @csrf
            @method('PUT')

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="difference_alert_threshold">Umbral de alerta por diferencia (Bs)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  name="difference_alert_threshold"
                  id="difference_alert_threshold"
                  class="form-control @error('difference_alert_threshold') is-invalid @enderror"
                  value="{{ old('difference_alert_threshold', $settings->difference_alert_threshold) }}"
                  @disabled($readOnly)
                  required
                >
                <small class="text-muted">Diferencias mayores a este valor marcarán la transacción como alerta.</small>
                @error('difference_alert_threshold')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="shortage_alert_threshold">Umbral de alerta por faltante (Bs)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  name="shortage_alert_threshold"
                  id="shortage_alert_threshold"
                  class="form-control @error('shortage_alert_threshold') is-invalid @enderror"
                  value="{{ old('shortage_alert_threshold', $settings->shortage_alert_threshold) }}"
                  @disabled($readOnly)
                  required
                >
                <small class="text-muted">Si el voucher es menor al extracto por encima de este monto se mostrará alerta crítica.</small>
                @error('shortage_alert_threshold')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="credit_max_amount">Crédito máximo permitido (Bs)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  name="credit_max_amount"
                  id="credit_max_amount"
                  class="form-control @error('credit_max_amount') is-invalid @enderror"
                  value="{{ old('credit_max_amount', $settings->credit_max_amount) }}"
                  @disabled($readOnly)
                  required
                >
                <small class="text-muted">Límite de excedente que puede registrarse automáticamente como saldo a favor.</small>
                @error('credit_max_amount')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <hr>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="voucher_statuses">Estados permitidos para vouchers</label>
                <textarea
                  name="voucher_statuses"
                  id="voucher_statuses"
                  rows="6"
                  class="form-control @error('voucher_statuses') is-invalid @enderror"
                  placeholder="Un estado por línea"
                  @disabled($readOnly)
                >{{ trim(old('voucher_statuses', $statusList)) }}</textarea>
                <small class="text-muted">Ejemplo: recibido, validado, rechazado, demasía.</small>
                @error('voucher_statuses')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="voucher_rules">Reglas de aprobación / rechazo</label>
                <textarea
                  name="voucher_rules"
                  id="voucher_rules"
                  rows="6"
                  class="form-control @error('voucher_rules') is-invalid @enderror"
                  placeholder="Describe lógica para aprobar/rechazar"
                  @disabled($readOnly)
                >{{ trim(old('voucher_rules', $settings->voucher_rules)) }}</textarea>
                <small class="text-muted">Describe qué validar (monto, fechas, adjuntos, excedentes).</small>
                @error('voucher_rules')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="voucher_template_help">Plantilla de voucher / guía</label>
                <textarea
                  name="voucher_template_help"
                  id="voucher_template_help"
                  rows="6"
                  class="form-control @error('voucher_template_help') is-invalid @enderror"
                  placeholder="Instrucciones para quien sube vouchers"
                  @disabled($readOnly)
                >{{ trim(old('voucher_template_help', $settings->voucher_template_help)) }}</textarea>
                <small class="text-muted">Indica columnas obligatorias, formato del archivo y tips de digitalización.</small>
                @error('voucher_template_help')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            @if (! $readOnly)
              <button type="submit" class="btn btn-brand">Guardar cambios</button>
            @endif
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
