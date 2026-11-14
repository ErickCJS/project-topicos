@extends('adminlte::page')
@section('title', 'Detalle de usuario | RSU')
@section('content')
<div class="users-module"><div class="d-flex justify-content-between align-items-center mb-3"><h4><i class="bi bi-person-vcard me-2"></i>Detalle de usuario</h4><a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Volver</a></div>
<div class="card"><div class="card-body"><div class="row g-3">
    <div class="col-12 text-center"><img class="user-avatar user-avatar-large" src="{{ $user->profile_photo_url }}" alt="Fotografía de perfil"></div>
    @foreach(['Nombres y apellidos' => trim($user->name.' '.$user->last_name), 'DNI' => $user->dni, 'Correo electrónico' => $user->email, 'Teléfono' => $user->phone, 'Fecha de nacimiento' => $user->birthdate?->format('d/m/Y'), 'Licencia' => $user->license, 'Dirección' => $user->address, 'Tipo de usuario' => $user->userType?->name, 'Estado' => $user->is_active ? 'Activo' : 'Inactivo', 'Creación' => $user->created_at?->format('d/m/Y H:i:s'), 'Última actualización' => $user->updated_at?->format('d/m/Y H:i:s')] as $label => $value)
    <div class="col-12 col-md-6"><div class="user-detail-card"><small>{{ $label }}</small><strong>{{ filled($value) ? $value : 'No registrado' }}</strong></div></div>
    @endforeach
</div></div></div></div>
@stop
@section('css')<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">@stop
