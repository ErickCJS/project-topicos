@extends('adminlte::page')
@section('title', 'Editar usuario | RSU')
@section('content')
<div class="users-module"><div class="card"><div class="modal-header users-modal-header"><div class="users-modal-heading"><span class="users-modal-icon"><i class="bi bi-person-gear"></i></span><h5 class="mb-0">Editar usuario</h5></div></div><div class="card-body"><form method="post" action="{{ route('users.update', $user) }}" enctype="multipart/form-data">@include('admin.user.template.form')</form></div></div></div>
@stop
@section('css')<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">@stop
