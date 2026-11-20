
@csrf

@if(isset($user))
    @method('PUT')
@endif

@php
    $selectedUserType = old('user_type_id', $user->user_type_id ?? '');
@endphp

<div class="modal-body users-form-body">
    <div class="row g-3">

        <div class="col-12">
            <h6 class="users-form-section-title">
                <i class="bi bi-person-vcard me-2"></i>
                Información personal
            </h6>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="user_name">Nombres</label>
            <input
                type="text"
                class="form-control @error('name') is-invalid @enderror"
                id="user_name"
                name="name"
                value="{{ old('name', $user->name ?? '') }}"
                maxlength="100"
                required
            >
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="user_last_name">Apellidos</label>
            <input
                type="text"
                class="form-control @error('last_name') is-invalid @enderror"
                id="user_last_name"
                name="last_name"
                value="{{ old('last_name', $user->last_name ?? '') }}"
                maxlength="150"
                required
            >
            @error('last_name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="user_dni">DNI</label>
            <input
                type="text"
                class="form-control @error('dni') is-invalid @enderror"
                id="user_dni"
                name="dni"
                value="{{ old('dni', $user->dni ?? '') }}"
                inputmode="numeric"
                pattern="[0-9]{8}"
                minlength="8"
                maxlength="8"
                required
            >
            @error('dni')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="user_birthdate">Fecha de nacimiento</label>
            <input
                type="date"
                class="form-control @error('birthdate') is-invalid @enderror"
                id="user_birthdate"
                name="birthdate"
                max="{{ now()->format('Y-m-d') }}"
                value="{{ old('birthdate', isset($user) ? $user->birthdate?->format('Y-m-d') : '') }}"
            >
            @error('birthdate')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <h6 class="users-form-section-title mt-2">
                <i class="bi bi-telephone me-2"></i>
                Información de contacto
            </h6>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="user_email">Correo electrónico</label>
            <input
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                id="user_email"
                name="email"
                value="{{ old('email', $user->email ?? '') }}"
                maxlength="255"
                required
            >
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="user_phone">Teléfono</label>
            <input
                type="text"
                class="form-control @error('phone') is-invalid @enderror"
                id="user_phone"
                name="phone"
                value="{{ old('phone', $user->phone ?? '') }}"
                maxlength="30"
            >
            @error('phone')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="user_license">Licencia</label>
            <input
                type="text"
                class="form-control @error('license') is-invalid @enderror"
                id="user_license"
                name="license"
                value="{{ old('license', $user->license ?? '') }}"
                maxlength="50"
            >
            @error('license')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="user_address">Dirección</label>
            <textarea
                class="form-control @error('address') is-invalid @enderror"
                id="user_address"
                name="address"
                rows="2"
                maxlength="1000"
            >{{ old('address', $user->address ?? '') }}</textarea>
            @error('address')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <h6 class="users-form-section-title mt-2">
                <i class="bi bi-shield-lock me-2"></i>
                Configuración de acceso
            </h6>
        </div>

        <div class="col-12">
            <label class="form-label" for="user_type_id">Tipo de usuario</label>

            <select
                class="form-select @error('user_type_id') is-invalid @enderror"
                id="user_type_id"
                name="user_type_id"
            >
                <option value="">Sin tipo asignado</option>

                @foreach($types as $type)
                    <option
                        value="{{ $type->id }}"
                        @selected((string) $selectedUserType === (string) $type->id)
                    >
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>

            @error('user_type_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror

            <div class="form-text">
                El tipo identifica la clasificación del usuario.
                No concede permisos automáticamente.
            </div>
        </div>

        @if(auth()->user()->is_admin)
            <div class="col-12">
                <input type="hidden" name="is_admin" value="0">

                <div class="form-check users-access-option">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="is_admin"
                        id="user_is_admin"
                        value="1"
                        @checked(old('is_admin', $user->is_admin ?? false))
                    >

                    <label class="form-check-label" for="user_is_admin">
                        <strong>Acceso administrativo total</strong>
                    </label>

                    <div class="form-text">
                        Habilita el acceso administrativo completo al sistema,
                        independientemente del tipo de usuario seleccionado.
                    </div>
                </div>

                @error('is_admin')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
        @endif

        @if(isset($user))
            <div class="col-12">
                <div class="form-check users-access-option">
                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="is_active"
                        id="user_is_active"
                        value="1"
                        @checked((bool) old('is_active', $user->is_active))
                    >

                    <label class="form-check-label" for="user_is_active">
                        Usuario activo
                    </label>

                    @error('is_active')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        @endif

        <div class="col-12">
            <h6 class="users-form-section-title mt-2">
                <i class="bi bi-lock me-2"></i>
                Seguridad de la cuenta
            </h6>
        </div>

        <div class="col-12">
            @if(isset($user))
                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="user_change_password"
                        name="change_password"
                        value="1"
                        @checked(old('change_password'))
                    >

                    <label class="form-check-label" for="user_change_password">
                        Cambiar contraseña
                    </label>
                </div>
            @endif

            <label class="form-label" for="user_password">
                {{ isset($user) ? 'Nueva contraseña' : 'Contraseña' }}
            </label>

            <input
                type="password"
                class="form-control @error('password') is-invalid @enderror"
                id="user_password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                @required(!isset($user))
                @disabled(isset($user) && !old('change_password'))
            >

            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror

            <div class="form-text">
                La contraseña debe contener al menos 8 caracteres.
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="user_profile_photo">
                Fotografía de perfil
            </label>

            <input
                type="file"
                class="form-control @error('profile_photo') is-invalid @enderror"
                id="user_profile_photo"
                name="profile_photo"
                accept="image/*"
            >

            @error('profile_photo')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

    </div>
</div>

<div class="modal-footer users-modal-footer">
    <button
        type="button"
        class="btn btn-light border"
        data-bs-dismiss="modal"
    >
        Cancelar
    </button>

    <button type="submit" class="btn btn-success">
        <i class="bi bi-floppy me-1"></i>
        {{ isset($user) ? 'Guardar cambios' : 'Registrar usuario' }}
    </button>
</div>
