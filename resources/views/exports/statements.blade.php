@php($title = 'Reporte de extractos')

@extends('exports.layout')

@section('content')
  @include('exports.tables.statements', ['lines' => $lines])
@endsection
