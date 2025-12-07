@php($title = 'Reporte diario de ingresos')

@extends('exports.layout')

@section('content')
  @include('exports.tables.sales-report', ['entries' => $entries])
@endsection
