@extends('adminlte::page')

@section('title', 'Editar usuario | RSU')

@section('content')
<div class="users-module">

    <div class="card">

        <div class="card-header users-modal-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

                <div class="users-modal-heading">
                    <span class="users-modal-icon">
                        <i class="bi bi-person-gear" aria-hidden="true"></i>
                    </span>

                    <div>
                        <h5 class="mb-0">Editar usuario</h5>
                        <small class="text-muted">
                            Actualiza la información y configuración de la cuenta.
                        </small>
                    </div>
                </div>

                <a href="{{ route('users.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver
                </a>

            </div>
        </div>

        <div class="card-body p-0">

            <form
                method="POST"
                action="{{ route('users.update', $user) }}"
                enctype="multipart/form-data"
                class="users-page-form">

                @include('admin.user.template.form')

            </form>

        </div>

    </div>

</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/users-module.css') }}">
@stop

@section('js')
<script>
document.addEventListener('click', function (event) {
    const cancelButton = event.target.closest(
        '.users-page-form [data-bs-dismiss="modal"]'
    );

    if (!cancelButton) {
        return;
    }

    event.preventDefault();

    window.location.href = @json(route('users.index'));
});
</script>
@stop
