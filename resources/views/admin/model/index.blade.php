@extends('adminlte::page')

@section('title', 'Modelos | RSUProject')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <h4 class="mb-0">Listado de Modelos</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo modelo
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                    <thead>
                        <tr>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Código</th>
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

    <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header model-modal-header">
                    <div class="model-modal-heading">
                        <span class="model-modal-icon" aria-hidden="true"><i class="bi bi-boxes"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="formModalLabel">Modelo</h5>
                            <small>Complete la información del modelo</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body model-modal-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modelShowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="modelShowContent"></div>
        </div>
    </div>
@stop

@section('css')
<style>
    #DataTable th,
    #DataTable td {
        vertical-align: middle;
    }

    #formModal .modal-content,
    #modelShowModal .modal-content {
        overflow: hidden;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
    }

    #formModal .model-modal-header,
    #modelShowModal .model-modal-header {
        min-height: 76px;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }

    #formModal .model-modal-heading,
    #modelShowModal .model-modal-heading {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    #formModal .model-modal-heading small,
    #modelShowModal .model-modal-heading small {
        display: block;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
    }

    #formModal .model-modal-icon,
    #modelShowModal .model-modal-icon {
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

    #formModal .model-modal-body,
    #modelShowModal .model-modal-body {
        padding: 1.5rem;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
    }

    #formModal .model-form-intro {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    #formModal .model-form-intro h6,
    #formModal .form-label {
        color: var(--bs-body-color);
    }

    #formModal .model-form-intro p,
    #formModal .form-text {
        color: var(--bs-secondary-color);
    }

    #formModal .form-label {
        margin-bottom: .45rem;
    }

    #formModal .form-control,
    #formModal .form-select {
        min-height: 42px;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }

    #formModal textarea.form-control {
        min-height: 112px;
    }

    #formModal .form-control::placeholder {
        color: var(--bs-secondary-color);
        opacity: .8;
    }

    #formModal .form-control:focus,
    #formModal .form-select:focus {
        border-color: rgba(var(--bs-info-rgb), .8);
        box-shadow: 0 0 0 .2rem rgba(var(--bs-info-rgb), .16);
    }

    #formModal .model-modal-footer,
    #modelShowModal .model-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
    }

    #formModal .model-modal-footer {
        margin: 1.5rem -1.5rem -1.5rem;
    }

    #modelShowModal .model-detail-card {
        height: 100%;
        padding: 1rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .6rem;
    }

    #modelShowModal .model-detail-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--bs-secondary-color);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    #modelShowModal .model-detail-value {
        color: var(--bs-body-color);
        font-weight: 600;
    }

    #modelShowModal .model-detail-description {
        color: var(--bs-body-color);
        white-space: pre-wrap;
    }

    html.dark #formModal .modal-content,
    html.dark #modelShowModal .modal-content,
    html[data-bs-theme="dark"] #formModal .modal-content,
    html[data-bs-theme="dark"] #modelShowModal .modal-content {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5);
    }

    .model-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .model-actions .model-action-btn {
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

    .model-actions .model-action-btn i {
        line-height: 1;
    }

    @media (max-width: 575.98px) {
        #formModal .model-modal-body,
        #modelShowModal .model-modal-body {
            padding: 1rem;
        }

        #formModal .model-modal-footer {
            margin: 1rem -1rem -1rem;
        }

        #formModal .model-modal-footer,
        #modelShowModal .model-modal-footer {
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
    const showModalElement = document.getElementById('modelShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);

    function mostrarAlerta(esExito, mensaje) {
        return Swal.fire({
            title: esExito ? 'Proceso exitoso' : 'Ocurrió un error',
            icon: esExito ? 'success' : 'error',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mensajeError(xhr, mensajePredeterminado) {
        const response = xhr.responseJSON || {};

        return response.message || mensajePredeterminado;
    }

    function limpiarErrores(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.model-field-error').remove();
    }

    function mostrarErroresValidacion(form, errors) {
        limpiarErrores(form);

        Object.entries(errors).forEach(function ([field, messages]) {
            const input = form.find('[name="' + field + '"]').first();

            if (!input.length) return;

            input.addClass('is-invalid');
            $('<div>', {
                class: 'invalid-feedback model-field-error',
                text: messages.join(' ')
            }).insertAfter(input);
        });
    }

    function abrirModal(url, titulo) {
        $.get(url, function (response) {
            $('#formModalLabel').text(titulo);
            $('#formModal .modal-body').html(response);
            formModal.show();
        }).fail(function (error) {
            mostrarAlerta(false, mensajeError(error, 'No se pudo abrir el formulario.'));
        });
    }

    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('brandsmodel.index')),
        columns: [
            { data: 'name', name: 'name' },
            { data: 'brand_name', orderable: false, searchable: false },
            { data: 'code', name: 'code', defaultContent: '' },
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
            zeroRecords: 'No se encontraron modelos',
            emptyTable: 'Aún no hay modelos registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refrescarTabla() {
        table.ajax.reload(null, false);
    }

    $('#btnNuevo').on('click', function () {
        abrirModal(@json(route('brandsmodel.create')), 'Nuevo modelo');
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#modelShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrarAlerta(false, mensajeError(error, 'No se pudo cargar el modelo.'));
        });
    });

    $(document).on('click', '.btnEditar', function () {
        abrirModal($(this).data('url'), 'Editar modelo');
    });

    $(document).on('input change', '#formModal [name]', function () {
        const input = $(this);
        input.removeClass('is-invalid');
        input.next('.model-field-error').remove();
    });

    $(document).on('submit', '#formModal form', function (event) {
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
                mostrarAlerta(true, response.message || 'Modelo guardado correctamente.');
            },
            error: function (error) {
                const response = error.responseJSON || {};

                if (error.status === 422 && response.errors) {
                    mostrarErroresValidacion(form, response.errors);
                    return;
                }

                mostrarAlerta(false, mensajeError(error, 'No se pudo guardar el modelo.'));
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar este modelo?',
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
                    mostrarAlerta(true, response.message || 'Modelo eliminado correctamente.');
                },
                error: function (error) {
                    mostrarAlerta(false, mensajeError(error, 'No se pudo eliminar el modelo.'));
                }
            });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        $('#formModal .modal-body').empty();
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#modelShowContent').empty();
    });
});
</script>
@stop
