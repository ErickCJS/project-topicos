@extends('adminlte::page')
@section('title', 'Permisos | RSU')
@section('css')<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}"><link rel="stylesheet" href="{{ asset('css/users-module.css') }}">@stop
@section('content')@include('admin.user-types.partials.permissions-matrix')@stop
@section('js')@include('admin.user-types.partials.permissions-script')@stop
