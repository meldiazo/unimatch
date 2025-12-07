@php($title = 'Extractos cargados')

@extends('exports.layout')

@section('content')
  @include('exports.tables.statements', ['lines' => $lines])
@endsection
