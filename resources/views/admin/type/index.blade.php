@extends('adminlte::page')

@section('title', 'Tipos de Vehículo | RSUProject')

@section('content')
    <div class="vehicle-types-module">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">Listado de Tipos de Vehículo</h4>
                <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Tipo
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Creación</th>
                                <th>Actualización</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade vehicle-type-modal" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header vehicle-type-modal-header">
                        <div class="vehicle-type-modal-heading">
                            <span class="vehicle-type-modal-icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                            <div>
                                <h5 class="modal-title mb-0" id="formModalLabel">Tipo de Vehículo</h5>
                                <small>Complete la información del tipo de vehículo</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body vehicle-type-modal-body"></div>
                </div>
            </div>
        </div>

        <div class="modal fade vehicle-type-modal" id="vehicleTypeShowModal" tabindex="-1"
            aria-labelledby="vehicleTypeShowModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header vehicle-type-modal-header">
                        <div class="vehicle-type-modal-heading">
                            <span class="vehicle-type-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
                            <div>
                                <h5 class="modal-title mb-0" id="vehicleTypeShowModalLabel">Detalle de tipo de vehículo</h5>
                                <small>Información de solo lectura</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body vehicle-type-modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="vehicle-type-detail-card">
                                    <small class="vehicle-type-detail-label">Nombre</small>
                                    <span class="vehicle-type-detail-value text-break" id="showVehicleTypeName"></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="vehicle-type-detail-card">
                                    <small class="vehicle-type-detail-label">Descripción</small>
                                    <div class="vehicle-type-detail-description text-break" id="showVehicleTypeDescription"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="vehicle-type-detail-card">
                                    <small class="vehicle-type-detail-label">Creación</small>
                                    <span class="vehicle-type-detail-value" id="showVehicleTypeCreatedAt"></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="vehicle-type-detail-card">
                                    <small class="vehicle-type-detail-label">Última actualización</small>
                                    <span class="vehicle-type-detail-value" id="showVehicleTypeUpdatedAt"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer vehicle-type-modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .vehicle-types-module #DataTable th,
    .vehicle-types-module #DataTable td {
        vertical-align: middle;
    }

    .vehicle-types-module .vehicle-type-modal .modal-content {
        overflow: hidden;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
    }

    .vehicle-types-module .vehicle-type-modal-header {
        min-height: 76px;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }

    .vehicle-types-module .vehicle-type-modal-heading {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    .vehicle-types-module .vehicle-type-modal-heading small {
        display: block;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
    }

    .vehicle-types-module .vehicle-type-modal-icon {
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

    .vehicle-types-module .vehicle-type-modal-body {
        padding: 1.5rem;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
    }

    .vehicle-types-module .vehicle-type-form-intro {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .vehicle-types-module .vehicle-type-form-intro h6,
    .vehicle-types-module .vehicle-type-modal .form-label {
        color: var(--bs-body-color);
    }

    .vehicle-types-module .vehicle-type-form-intro p,
    .vehicle-types-module .vehicle-type-modal .form-text {
        color: var(--bs-secondary-color);
    }

    .vehicle-types-module .vehicle-type-modal .form-label {
        margin-bottom: .45rem;
    }

    .vehicle-types-module .vehicle-type-modal .form-control {
        min-height: 42px;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }

    .vehicle-types-module .vehicle-type-modal textarea.form-control {
        min-height: 112px;
    }

    .vehicle-types-module .vehicle-type-modal .form-control::placeholder {
        color: var(--bs-secondary-color);
        opacity: .8;
    }

    .vehicle-types-module .vehicle-type-modal .form-control:focus {
        border-color: rgba(var(--bs-info-rgb), .8);
        box-shadow: 0 0 0 .2rem rgba(var(--bs-info-rgb), .16);
    }

    .vehicle-types-module .vehicle-type-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
    }

    .vehicle-types-module #formModal .vehicle-type-modal-footer {
        margin: 1.5rem -1.5rem -1.5rem;
    }

    .vehicle-types-module .vehicle-type-detail-card {
        height: 100%;
        padding: 1rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .6rem;
    }

    .vehicle-types-module .vehicle-type-detail-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--bs-secondary-color);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .vehicle-types-module .vehicle-type-detail-value {
        color: var(--bs-body-color);
        font-weight: 600;
    }

    .vehicle-types-module .vehicle-type-detail-description {
        color: var(--bs-body-color);
        white-space: pre-wrap;
    }

    .vehicle-types-module .vehicle-type-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .vehicle-types-module .vehicle-type-actions .vehicle-type-action-btn {
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

    .vehicle-types-module .vehicle-type-actions .vehicle-type-action-btn i {
        line-height: 1;
    }

    html.dark .vehicle-types-module .vehicle-type-modal .modal-content,
    html[data-bs-theme="dark"] .vehicle-types-module .vehicle-type-modal .modal-content {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5);
    }

    @media (max-width: 575.98px) {
        .vehicle-types-module .vehicle-type-modal-body {
            padding: 1rem;
        }

        .vehicle-types-module #formModal .vehicle-type-modal-footer {
            margin: 1rem -1rem -1rem;
        }

        .vehicle-types-module .vehicle-type-modal-footer {
            flex-wrap: wrap;
        }
    }
</style>
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('vehicleTypeShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);

    function mostrarAlerta(esExito, mensaje) {
        return Swal.fire({
            title: esExito ? 'Proceso exitoso' : 'Ocurrió un error',
            icon: esExito ? 'success' : 'error',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function limpiarErrores(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.vehicle-type-field-error').remove();
    }

    function mostrarErroresValidacion(form, errors) {
        limpiarErrores(form);

        Object.entries(errors).forEach(function ([field, messages]) {
            const input = form.find('[name="' + field + '"]').first();
            if (!input.length) return;

            input.addClass('is-invalid');
            $('<div>', {
                class: 'invalid-feedback vehicle-type-field-error',
                text: messages.join(' ')
            }).insertAfter(input);
        });

        form.find('.is-invalid').first().trigger('focus');
    }

    function mostrarError(error, form = null) {
        const response = error.responseJSON || {};

        if (error.status === 422 && response.errors && form) {
            mostrarErroresValidacion(form, response.errors);
            return;
        }

        let mensaje = response.mensaje || response.message || 'No se pudo completar la operación. Inténtelo nuevamente.';
        if (error.status === 404) mensaje = 'El tipo de vehículo ya no existe.';
        if (error.status === 419) mensaje = 'La sesión ha expirado. Recargue la página.';
        if (error.status === 422 && response.errors) mensaje = Object.values(response.errors).flat().join(' ');

        mostrarAlerta(false, mensaje);
    }

    function abrirFormulario(url, titulo) {
        $.get(url, function (response) {
            $('#formModalLabel').text(titulo);
            $('#formModal .modal-body').html(response);
            formModal.show();
        }).fail(function (error) {
            mostrarError(error);
        });
    }

    function fechaLatinoamericana(valor) {
        if (!valor) return 'Sin fecha';
        const fecha = new Date(String(valor).replace(' ', 'T'));
        if (Number.isNaN(fecha.getTime())) return valor;

        return new Intl.DateTimeFormat('es-419', {
            dateStyle: 'short',
            timeStyle: 'medium'
        }).format(fecha);
    }

    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('vehiclestype.index')),
        columns: [
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description', defaultContent: '' },
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron tipos de vehículo',
            emptyTable: 'Aún no hay tipos de vehículo registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refrescarTabla() {
        table.ajax.reload(null, false);
    }

    $('#btnNuevo').on('click', function () {
        abrirFormulario(@json(route('vehiclestype.create')), 'Nuevo Tipo de Vehículo');
    });

    $('#DataTable').on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (type) {
            $('#showVehicleTypeName').text(type.name);
            $('#showVehicleTypeDescription').text(type.description || 'Sin descripción');
            $('#showVehicleTypeCreatedAt').text(fechaLatinoamericana(type.created_at));
            $('#showVehicleTypeUpdatedAt').text(fechaLatinoamericana(type.updated_at));
            showModal.show();
        }).fail(function (error) {
            mostrarError(error);
        });
    });

    $('#DataTable').on('click', '.btnEditar', function () {
        abrirFormulario($(this).data('url'), 'Editar Tipo de Vehículo');
    });

    $(document).on('input change', '#formModal [name]', function () {
        const input = $(this);
        input.removeClass('is-invalid');
        input.next('.vehicle-type-field-error').remove();
    });

    $('#formModal').on('submit', 'form', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('[type="submit"]').prop('disabled', true);
        limpiarErrores(form);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                refrescarTabla();
                mostrarAlerta(true, response.mensaje || 'Tipo de vehículo guardado correctamente.');
            },
            error: function (error) {
                mostrarError(error, form);
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    $('#DataTable').on('click', '.btnEliminar', function () {
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar este tipo?',
            text: 'Esta acción no se puede revertir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    Accept: 'application/json'
                },
                success: function (response) {
                    refrescarTabla();
                    mostrarAlerta(true, response.mensaje || 'Tipo de vehículo eliminado correctamente.');
                },
                error: function (error) {
                    mostrarError(error);
                }
            });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        $('#formModal .modal-body').empty();
    });
});
</script>
@stop
