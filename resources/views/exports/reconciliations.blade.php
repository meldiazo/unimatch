@php($title = 'Reporte de conciliaciones')

@extends('exports.layout')

@section('content')
  @include('exports.tables.reconciliations', ['transactions' => $transactions])
@endsection
