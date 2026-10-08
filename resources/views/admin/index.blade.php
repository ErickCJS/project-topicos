{{-- resources/views/dashboard.blade.php --}}

@extends('adminlte::page')

@section('title', 'RSUProject')

@section('content_header')
    <h1>Projecto RSU</h1>
@stop

@section('content')
    <p>Bienvenido al proyecto RSU de la USAT.</p>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop