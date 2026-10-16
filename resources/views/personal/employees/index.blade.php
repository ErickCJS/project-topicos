@extends('adminlte::page')

@section('title', 'Personal | RSU')

@section('content')
    <div class="employees-module">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0"><i class="bi bi-people me-2" aria-hidden="true"></i>Personal</h4>
                <button type="button" class="btn btn-success ms-auto" id="btnNuevoEmpleado">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Nuevo empleado
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle w-100" id="tablaEmpleados">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>DNI</th>
                                <th>Nombre</th>
                                <th>Apellidos</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Creación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="employeeFormModal" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="employeeFormModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" id="employeeFormContent">
                    @include('personal.employees.partials.form-modal')
                </div>
            </div>
        </div>

        <div class="modal fade" id="employeeShowModal" tabindex="-1"
            aria-labelledby="employeeShowModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content" id="employeeShowContent"></div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .employees-module #tablaEmpleados {
        width: 100% !important;
    }

    .employees-module #tablaEmpleados th,
    .employees-module #tablaEmpleados td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .employees-module #tablaEmpleados td:first-child {
        width: 72px;
    }

    .employees-module #employeeFormModal .modal-content,
    .employees-module #employeeShowModal .modal-content {
        overflow: hidden;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
    }

    .employees-module #employeeFormContent > form {
        display: flex;
        flex-direction: column;
        min-height: 0;
        max-height: calc(100dvh - 3.5rem);
    }

    .employees-module #employeeFormContent > form > .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    .employees-module .employee-modal-header {
        min-height: 76px;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }

    .employees-module .employee-modal-heading {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    .employees-module .employee-modal-heading small {
        display: block;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
    }

    .employees-module .employee-modal-icon {
        display: inline-grid;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        place-items: center;
        color: var(--bs-info-text-emphasis);
        background-color: var(--bs-info-bg-subtle);
        border: 1px solid var(--bs-info-border-subtle);
        border-radius: .65rem;
        font-size: 1.15rem;
    }

    .employees-module .employee-modal-body {
        padding: 1.5rem;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
    }

    .employees-module .employee-form-intro {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .employees-module .employee-form-intro h6,
    .employees-module .form-label {
        color: var(--bs-body-color);
    }

    .employees-module .employee-form-intro p,
    .employees-module .form-text {
        color: var(--bs-secondary-color);
    }

    .employees-module .form-control,
    .employees-module .form-select {
        min-height: 42px;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }

    .employees-module .form-control::placeholder {
        color: var(--bs-secondary-color);
        opacity: .8;
    }

    .employees-module .form-control:focus,
    .employees-module .form-select:focus {
        border-color: rgba(var(--bs-info-rgb), .8);
        box-shadow: 0 0 0 .2rem rgba(var(--bs-info-rgb), .16);
    }

    .employees-module .employee-photo-panel {
        height: 100%;
        padding: 1rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .65rem;
    }

    .employees-module .employee-photo-picker {
        display: flex;
        width: 100%;
        height: 306px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: var(--bs-secondary-color);
        background-color: var(--bs-body-bg);
        border: 1px dashed var(--bs-border-color);
        border-radius: .6rem;
        cursor: pointer;
    }

    .employees-module .employee-photo-picker:focus-within,
    .employees-module .employee-photo-picker:hover {
        border-color: rgba(var(--bs-info-rgb), .8);
    }

    .employees-module .employee-photo-placeholder {
        font-size: 7rem;
        line-height: 1;
    }

    .employees-module .employee-photo-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .employees-module .employee-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
    }

    .employees-module .employee-show-profile,
    .employees-module .employee-detail-card {
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .65rem;
    }

    .employees-module .employee-detail-card {
        height: 100%;
        padding: 1rem;
    }

    .employees-module .employee-detail-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--bs-secondary-color);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .employees-module .employee-detail-value {
        color: var(--bs-body-color);
        font-weight: 600;
    }

    .employees-module .employee-show-photo {
        width: 132px;
        height: 132px;
        flex: 0 0 auto;
        object-fit: cover;
        cursor: zoom-in;
        transition: transform .2s ease;
    }

    .employees-module .employee-show-photo.is-zoomed {
        position: relative;
        z-index: 1056;
        cursor: zoom-out;
        transform: scale(1.5);
    }

    .employees-module .employee-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .employees-module .employee-action-btn {
        display: inline-flex;
        width: 36px;
        min-width: 36px;
        height: 31px;
        flex: 0 0 36px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-width: 1px;
        border-style: solid;
        border-radius: .25rem;
    }

    .employees-module .employee-action-btn i {
        line-height: 1;
    }

    .employees-module .employee-field-error {
        display: block;
    }

    html.dark .employees-module #employeeFormModal .modal-content,
    html.dark .employees-module #employeeShowModal .modal-content,
    html[data-bs-theme="dark"] .employees-module #employeeFormModal .modal-content,
    html[data-bs-theme="dark"] .employees-module #employeeShowModal .modal-content {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5);
    }

    @media (max-width: 575.98px) {
        .employees-module #tablaEmpleados {
            min-width: 900px;
        }

        .employees-module #employeeFormModal .modal-dialog {
            margin: .5rem;
        }

        .employees-module .employee-modal-body {
            padding: 1rem;
        }

        .employees-module .employee-modal-footer {
            flex-wrap: wrap;
        }
    }
</style>
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('employeeFormModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const createFormHtml = $('#employeeFormContent').html();
    const showModalElement = document.getElementById('employeeShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);
    let employeePhotoObjectUrl = null;

    function liberarVistaPrevia() {
        if (employeePhotoObjectUrl) {
            URL.revokeObjectURL(employeePhotoObjectUrl);
            employeePhotoObjectUrl = null;
        }
    }

    function mostrarMensaje(texto, error = false) {
        return Swal.fire({
            icon: error ? 'error' : 'success',
            title: error ? 'Ocurrió un error' : 'Proceso exitoso',
            text: texto,
            confirmButtonText: 'Entendido'
        });
    }

    function mensajeError(xhr, fallback) {
        const data = xhr.responseJSON || {};

        return data.mensaje || data.message || fallback;
    }

    function limpiarErrores(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.employee-field-error').remove();
        form.find('.employee-photo-picker').removeClass('border-danger');
    }

    function mostrarErroresValidacion(form, errors) {
        limpiarErrores(form);

        Object.entries(errors).forEach(function ([field, messages]) {
            const input = form.find('[name="' + field + '"]').not(':disabled').first();

            if (!input.length) return;

            input.addClass('is-invalid');
            $('<div>', {
                class: 'invalid-feedback employee-field-error',
                text: messages.join(' ')
            }).insertAfter(input);

            if (field === 'foto') {
                form.find('.employee-photo-picker').addClass('border-danger');
            }
        });
    }

    function mostrarErrorGeneral(xhr, fallback) {
        mostrarMensaje(mensajeError(xhr, fallback), true);
    }

    function actualizarCampoLicencia(form) {
        const tipo = form.find('[name="tipo_personal_id"] option:selected');
        const campo = form.find('[data-campo-licencia]');
        const input = campo.find('[name="licencia"]');
        const requiereLicencia = tipo.data('requiere-licencia') == 1;

        campo.prop('hidden', !requiereLicencia);
        input.prop('disabled', !requiereLicencia).prop('required', requiereLicencia);

        if (!requiereLicencia) input.val('');
    }

    const table = $('#tablaEmpleados').DataTable({
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('personal.employees.index')),
        order: [[7, 'desc']],
        columns: [
            { data: 'photo', name: 'employees.profile_photo_path', orderable: false, searchable: false },
            { data: 'dni', name: 'employees.dni' },
            { data: 'first_name', name: 'employees.first_name' },
            { data: 'last_name', name: 'employees.last_name' },
            { data: 'email', name: 'employees.email' },
            { data: 'type', name: 'employee_types.name' },
            { data: 'status', name: 'employees.status' },
            { data: 'created_at', name: 'employees.created_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron empleados',
            emptyTable: 'Aún no hay personal registrado',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    $(document).on('change', '#employeeFormContent [name="tipo_personal_id"]', function () {
        actualizarCampoLicencia($(this).closest('form'));
    });

    $(document).on('change', '#employeeProfilePhoto', function () {
        const archivo = this.files && this.files[0];

        if (!archivo) return;

        liberarVistaPrevia();
        employeePhotoObjectUrl = URL.createObjectURL(archivo);

        const preview = document.getElementById('employeePhotoPreview');
        preview.src = employeePhotoObjectUrl;
        preview.classList.remove('d-none');
        document.getElementById('employeePhotoPlaceholder').classList.add('d-none');
    });

    $(document).on('change', '#employeeChangePassword', function () {
        const password = document.getElementById('employeePassword');
        password.disabled = !this.checked;
        password.required = this.checked;

        if (!this.checked) password.value = '';
    });

    $(document).on('input change', '#employeeFormContent [name]', function () {
        const input = $(this);
        input.removeClass('is-invalid');
        input.next('.employee-field-error').remove();

        if (input.attr('name') === 'foto') {
            input.closest('.employee-photo-panel').find('.employee-photo-picker').removeClass('border-danger');
        }
    });

    $(document).on('submit', '#employeeFormContent form', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('[type="submit"]').prop('disabled', true);
        limpiarErrores(form);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                table.ajax.reload(null, false);
                mostrarMensaje(response.mensaje || 'Personal guardado correctamente.');
            },
            error: function (error) {
                const response = error.responseJSON || {};

                if (error.status === 422 && response.errors) {
                    mostrarErroresValidacion(form, response.errors);
                    return;
                }

                mostrarErrorGeneral(error, 'No se pudo guardar el personal. Intente nuevamente.');
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    function abrirFormularioEmpleado(url) {
        liberarVistaPrevia();

        $.get(url, function (html) {
            $('#employeeFormContent').html(html);
            actualizarCampoLicencia($('#employeeFormContent form'));
            formModal.show();
        }).fail(function (error) {
            mostrarErrorGeneral(error, 'No se pudo cargar el formulario del empleado.');
        });
    }

    $('#btnNuevoEmpleado').on('click', function () {
        liberarVistaPrevia();
        $('#employeeFormContent').html(createFormHtml);

        const form = $('#employeeFormContent form');
        actualizarCampoLicencia(form);
        formModal.show();
    });

    $(document).on('click', '.btnEditarEmpleado', function () {
        abrirFormularioEmpleado($(this).data('url'));
    });

    $(document).on('click', '.btnVerEmpleado', function () {
        $.get($(this).data('url'), function (html) {
            $('#employeeShowContent').html(html);
            showModal.show();
        }).fail(function (error) {
            mostrarErrorGeneral(error, 'No se pudieron cargar los datos del empleado.');
        });
    });

    $(document).on('click', '.employee-show-photo', function () {
        $(this).toggleClass('is-zoomed');
    });

    $(document).on('click', '.btnEliminarEmpleado', function () {
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar este empleado?',
            text: 'Si tiene contratos históricos, se conservará como inactivo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    Accept: 'application/json'
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    mostrarMensaje(response.mensaje || 'Operación completada correctamente.');
                },
                error: function (error) {
                    mostrarErrorGeneral(error, 'No se pudo eliminar el empleado.');
                }
            });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        liberarVistaPrevia();
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#employeeShowContent').empty();
    });
});
</script>
@stop
