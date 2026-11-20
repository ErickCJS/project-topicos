@extends('adminlte::page')

@section('title', 'Gestión de usuarios | RSU')

@section('content')
<div class="users-module">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0">
            <i class="bi bi-people me-2" aria-hidden="true"></i>
            Gestión de usuarios
        </h4>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" id="btnNuevoTipoUsuario">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                Nuevo tipo de usuario
            </button>

            <button type="button" class="btn btn-outline-secondary" id="btnGestionarTiposUsuario">
                <i class="bi bi-tags me-1" aria-hidden="true"></i>
                Gestionar tipos
            </button>

            <button type="button" class="btn btn-success" id="btnNuevoUsuario">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
                Nuevo usuario
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="usersTable">
                    <thead>
                        <tr>
                            <th>Fotografía</th>
                            <th>Nombres y apellidos</th>
                            <th>DNI</th>
                            <th>Correo electrónico</th>
                            <th>Teléfono</th>
                            <th>Tipo de usuario</th>
                            <th>Estado</th>
                            <th>Última actualización</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.user.partials.modals')

</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/users-module.css') }}">
@stop

@section('js')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const usersModalElement = document.getElementById('usersModal');
    const userTypesModalElement = document.getElementById('userTypesModal');

    const usersModal = usersModalElement
        ? bootstrap.Modal.getOrCreateInstance(usersModalElement)
        : null;

    const userTypesModal = userTypesModalElement
        ? bootstrap.Modal.getOrCreateInstance(userTypesModalElement)
        : null;

    let editingTypeFromList = false;
    let userTypesTable = null;

    function mostrarExito(mensaje) {
        return Swal.fire({
            title: 'Proceso exitoso',
            icon: 'success',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mostrarError(mensaje) {
        return window.RSUValidation.error(mensaje);
    }

    function mostrarErroresValidacion(errors) {
        return window.RSUValidation.show(errors);
    }

    function mensajeError(xhr, predeterminado) {
        const response = xhr.responseJSON || {};

        if (xhr.status === 404) {
            return response.message || 'No se encontró el registro solicitado.';
        }

        if (xhr.status === 419) {
            return 'La sesión ha expirado. Recargue la página.';
        }

        if (xhr.status === 403) {
            return 'No tiene autorización para realizar esta operación.';
        }

        if (xhr.status === 401) {
            return 'Su sesión no está activa. Inicie sesión nuevamente.';
        }

        return response.message || response.mensaje || predeterminado;
    }

    function refrescarUsuarios() {
        usersTable.ajax.reload(null, false);
    }

    const usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: "{{ route('users.index', [], false) }}",
        order: [[7, 'desc']],
        columns: [
            {
                data: 'photo',
                orderable: false,
                searchable: false
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'dni',
                name: 'dni'
            },
            {
                data: 'email',
                name: 'email'
            },
            {
                data: 'phone',
                name: 'phone'
            },
            {
                data: 'type',
                name: 'user_type_label'
            },
            {
                data: 'status',
                name: 'is_active'
            },
            {
                data: 'updated_at',
                name: 'updated_at'
            },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron usuarios',
            emptyTable: 'Aún no hay usuarios registrados',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            }
        }
    });

    function actualizarMatrizPermisos(form) {
        if (!form) return;

        const options = [...form.querySelectorAll('.permission-option')];
        const selectors = [...form.querySelectorAll('.permission-select-all')];

        const contar = items => items.filter(item => item.checked).length;

        selectors.forEach(control => {
            const scope = control.dataset.scope;

            const scoped = scope === 'all'
                ? options
                : [...form.querySelectorAll('.' + CSS.escape(scope))];

            const selected = contar(scoped);

            control.checked = scoped.length > 0 && selected === scoped.length;
            control.indeterminate = selected > 0 && selected < scoped.length;
        });

        form.querySelectorAll('[data-module-card]').forEach(card => {
            const moduleOptions = [...card.querySelectorAll('.permission-option')];
            const selected = contar(moduleOptions);
            const counter = card.querySelector('[data-module-selected]');

            if (counter) {
                counter.textContent = selected;
            }

            card.dataset.configured = selected > 0 ? '1' : '0';
        });

        form.querySelectorAll('[data-submodule-card]').forEach(card => {
            const counter = card.querySelector('[data-submodule-selected]');

            if (counter) {
                counter.textContent = contar(
                    [...card.querySelectorAll('.permission-option')]
                );
            }
        });

        const selected = contar(options);

        const percentage = options.length
            ? Math.round(selected * 100 / options.length)
            : 0;

        const selectedCounter = form.querySelector('[data-permission-selected]');
        const stickyCounter = form.querySelector('[data-sticky-count]');
        const moduleCounter = form.querySelector('[data-module-count]');
        const percentageCounter = form.querySelector('[data-permission-percent]');
        const progress = form.querySelector('[data-permission-progress]');

        if (selectedCounter) {
            selectedCounter.textContent = selected;
        }

        if (stickyCounter) {
            stickyCounter.textContent = selected;
        }

        if (moduleCounter) {
            moduleCounter.textContent = form.querySelectorAll(
                '[data-module-card][data-configured="1"]'
            ).length;
        }

        if (percentageCounter) {
            percentageCounter.textContent = percentage + '%';
        }

        if (progress) {
            progress.style.width = percentage + '%';
            progress.setAttribute('aria-valuenow', percentage);
        }
    }

    function abrirModalUsuario(url) {
        if (!usersModalElement || !usersModal) {
            mostrarError('No se pudo inicializar el formulario de usuario.');
            return;
        }

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Modal-Request': '1',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            success: function (response) {
                usersModalElement.querySelector('.modal-content').innerHTML = response;

                const permissionForm = usersModalElement.querySelector(
                    '#permissionMatrixForm'
                );

                if (permissionForm) {
                    actualizarMatrizPermisos(permissionForm);
                }

                usersModal.show();
            },
            error: function (xhr) {
                mostrarError(
                    mensajeError(xhr, 'No se pudo cargar el formulario de usuario.')
                );
            }
        });
    }

    $('#btnNuevoUsuario').on('click', function () {
        abrirModalUsuario("{{ route('users.create', [], false) }}");
    });

    $(document).on('click', '[data-user-modal]', function () {
        abrirModalUsuario($(this).data('user-modal'));
    });

    usersModalElement?.addEventListener('hidden.bs.modal', function () {
        usersModalElement.querySelector('.modal-content').innerHTML = '';
    });

    $(document).on('submit', '#usersModal .js-modal-form', function (event) {
        event.preventDefault();

        const form = this;
        const submitButton = $(form).find('[type="submit"]');

        submitButton.prop('disabled', true);

        $.ajax({
            url: form.action,
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                usersModal.hide();
                refrescarUsuarios();

                mostrarExito(
                    response.message || 'Los datos del usuario se guardaron correctamente.'
                );
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};

                if (xhr.status === 422 && response.errors) {
                    mostrarErroresValidacion(response.errors);
                    return;
                }

                mostrarError(
                    mensajeError(xhr, 'No se pudo guardar el usuario.')
                );
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    $(document).on('change', '#usersModal #user_change_password', function () {
        const password = document.getElementById('user_password');

        if (!password) return;

        password.disabled = !this.checked;

        if (!this.checked) {
            password.value = '';
        }
    });

    $(document).on('change', '#permissionMatrixForm .permission-select-all', function () {
        const form = this.closest('form');

        if (!form) return;

        const scope = this.dataset.scope;

        const scoped = scope === 'all'
            ? form.querySelectorAll('.permission-option')
            : form.querySelectorAll('.' + CSS.escape(scope));

        scoped.forEach(option => {
            option.checked = this.checked;
        });

        actualizarMatrizPermisos(form);
    });

    $(document).on('change', '#permissionMatrixForm .permission-option', function () {
        actualizarMatrizPermisos(this.closest('form'));
    });

    $(document).on('submit', '#permissionMatrixForm', function (event) {
        event.preventDefault();

        const form = this;
        const submitButton = $(form).find('[type="submit"]');

        submitButton.prop('disabled', true);

        $.ajax({
            url: form.action,
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                usersModal.hide();
                refrescarUsuarios();

                mostrarExito(
                    response.message || 'Los permisos se actualizaron correctamente.'
                );
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};

                if (xhr.status === 422 && response.errors) {
                    mostrarErroresValidacion(response.errors);
                    return;
                }

                mostrarError(
                    mensajeError(xhr, 'No se pudieron guardar los permisos.')
                );
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '[data-user-delete]', function () {
        const url = $(this).data('user-delete');

        Swal.fire({
            title: '¿Está seguro de eliminar?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#b54747'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    refrescarUsuarios();

                    mostrarExito(
                        response.message || 'El usuario se eliminó correctamente.'
                    );
                },
                error: function (xhr) {
                    mostrarError(
                        mensajeError(xhr, 'No se pudo eliminar el usuario.')
                    );
                }
            });
        });
    });

    function destruirTablaTipos() {
        if ($.fn.DataTable.isDataTable('#userTypesTable')) {
            $('#userTypesTable').DataTable().destroy();
        }

        userTypesTable = null;
    }

    function inicializarTablaTipos() {
        const tableElement = document.getElementById('userTypesTable');

        if (!tableElement) return;

        destruirTablaTipos();

        userTypesTable = $('#userTypesTable').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 350,
            ajax: "{{ route('user-types.index', [], false) }}",
            columns: [
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'description',
                    name: 'description',
                    defaultContent: ''
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'updated_at',
                    name: 'updated_at'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                loadingRecords: '',
                zeroRecords: 'No se encontraron tipos de usuario',
                emptyTable: 'Aún no hay tipos de usuario registrados',
                paginate: {
                    first: 'Primero',
                    previous: 'Anterior',
                    next: 'Siguiente',
                    last: 'Último'
                }
            }
        });
    }

    function abrirListaTipos() {
        if (!userTypesModalElement || !userTypesModal) {
            mostrarError('No se pudo inicializar la gestión de tipos de usuario.');
            return;
        }

        $.ajax({
            url: "{{ route('user-types.index', [], false) }}",
            type: 'GET',
            headers: {
                'X-Modal-Request': '1',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            success: function (response) {
                destruirTablaTipos();

                userTypesModalElement.querySelector('.modal-content').innerHTML = response;

                editingTypeFromList = false;

                if (userTypesModalElement.classList.contains('show')) {
                    inicializarTablaTipos();
                } else {
                    userTypesModal.show();

                    $(userTypesModalElement).one('shown.bs.modal', function () {
                        inicializarTablaTipos();
                    });
                }
            },
            error: function (xhr) {
                mostrarError(
                    mensajeError(xhr, 'No se pudo cargar la lista de tipos de usuario.')
                );
            }
        });
    }

    function abrirFormularioTipo(url, desdeListado = false) {
        if (!userTypesModalElement || !userTypesModal) {
            mostrarError('No se pudo inicializar el formulario de tipos.');
            return;
        }

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Modal-Request': '1',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            success: function (response) {
                destruirTablaTipos();

                userTypesModalElement.querySelector('.modal-content').innerHTML = response;

                editingTypeFromList = desdeListado;

                if (!userTypesModalElement.classList.contains('show')) {
                    userTypesModal.show();
                }
            },
            error: function (xhr) {
                mostrarError(
                    mensajeError(xhr, 'No se pudo cargar el formulario del tipo de usuario.')
                );
            }
        });
    }

    $('#btnNuevoTipoUsuario').on('click', function () {
        abrirFormularioTipo(
            "{{ route('user-types.create', [], false) }}",
            false
        );
    });

    $('#btnGestionarTiposUsuario').on('click', function () {
        abrirListaTipos();
    });

    $(document).on('click', '#userTypesModal .btnEditarTipo', function () {
        abrirFormularioTipo(
            $(this).data('url'),
            true
        );
    });

    $(document).on('click', '#userTypesModal [data-user-type-back]', function () {
        abrirListaTipos();
    });

    $(document).on('submit', '#userTypesModal #userTypeForm', function (event) {
        event.preventDefault();

        const form = this;
        const submitButton = $(form).find('[type="submit"]');

        submitButton.prop('disabled', true);

        $.ajax({
            url: form.action,
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                if (editingTypeFromList) {
                    refrescarUsuarios();

                    mostrarExito(
                        response.message || 'Tipo de usuario actualizado correctamente.'
                    ).then(function () {
                        abrirListaTipos();
                    });
                } else {
                    userTypesModal.hide();

                    mostrarExito(
                        response.message || 'Tipo de usuario registrado correctamente.'
                    );
                }
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};

                if (xhr.status === 422 && response.errors) {
                    mostrarErroresValidacion(response.errors);
                    return;
                }

                mostrarError(
                    mensajeError(xhr, 'No se pudo guardar el tipo de usuario.')
                );
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    userTypesModalElement?.addEventListener('hidden.bs.modal', function () {
        destruirTablaTipos();

        userTypesModalElement.querySelector('.modal-content').innerHTML = '';

        editingTypeFromList = false;
    });

    @if(session('success'))
        mostrarExito(@json(session('success')));
    @endif

});
</script>
@stop
