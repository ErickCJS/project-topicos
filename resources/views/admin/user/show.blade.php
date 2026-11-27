@extends('adminlte::page')

@section('title', 'Detalle de usuario | RSU')

@section('content')
<div class="users-module">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <div class="users-modal-heading">
            <span class="users-modal-icon">
                <i class="bi bi-person-vcard"></i>
            </span>

            <div>
                <h4 class="mb-0">Detalle de usuario</h4>
                <small class="text-muted">
                    Consulta la información y configuración de la cuenta.
                </small>
            </div>
        </div>

        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Volver
        </a>
    </div>

    <div class="card user-profile-summary mb-4">
        <div class="card-body d-flex flex-column flex-sm-row align-items-center gap-3 text-center text-sm-start">

            <img
                class="user-avatar user-avatar-large"
                src="{{ $user->profile_photo_url }}"
                alt="Fotografía de {{ $user->name }}"
            >

            <div class="min-w-0">
                <h3 class="h5 mb-2">
                    {{ trim($user->name . ' ' . $user->last_name) }}
                </h3>

                <div class="d-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-2">

                    <span class="badge bg-body-tertiary text-body border">
                        {{ $user->user_type_label ?: 'Sin tipo asignado' }}
                    </span>

                    @if($user->is_admin)
                        <span class="badge text-bg-dark">
                            <i class="bi bi-shield-check me-1"></i>
                            Acceso administrativo total
                        </span>
                    @endif

                    <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                    </span>

                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-card-list me-2"></i>
                Información de la cuenta
            </h5>
        </div>

        <div class="card-body">
            <div class="row g-3">

                @foreach([
                    'DNI' => $user->dni,
                    'Correo electrónico' => $user->email,
                    'Teléfono' => $user->phone,
                    'Fecha de nacimiento' => $user->birthdate?->format('d/m/Y'),
                    'Licencia' => $user->license,
                    'Dirección' => $user->address,
                    'Tipo de usuario' => $user->user_type_label,
                    'Acceso administrativo total' => $user->is_admin ? 'Sí' : 'No',
                    'Estado de la cuenta' => $user->is_active ? 'Activo' : 'Inactivo',
                    'Fecha de creación' => $user->created_at?->format('d/m/Y H:i:s'),
                    'Última actualización' => $user->updated_at?->format('d/m/Y H:i:s'),
                ] as $label => $value)

                    <div class="col-12 col-md-6">
                        <div class="user-detail-card h-100">
                            <small>{{ $label }}</small>
                            <strong>
                                {{ filled($value) ? $value : 'No registrado' }}
                            </strong>
                        </div>
                    </div>

                @endforeach

            </div>
        </div>
    </div>

</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/users-module.css') }}">
@stop

