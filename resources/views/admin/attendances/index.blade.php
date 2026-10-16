@extends('adminlte::page')

@section('title', 'Asistencias | RSUProject')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <h4 class="mb-0">Listado de Asistencias</h4>
            <button type="button" class="btn btn-success" id="btnNuevo">
                <i class="bi bi-plus-circle me-1"></i>Agregar nueva asistencia
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Empleado</th>
                            <th>Fecha y hora</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Turno</th>
                            <th>Notas</th>
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header attendance-modal-header">
                    <div class="attendance-modal-heading">
                        <span class="attendance-modal-icon" aria-hidden="true">
                            <i class="bi bi-calendar2-check"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0" id="formModalLabel">Asistencia</h5>
                            <small>Complete la información de la asistencia</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body attendance-modal-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="attendanceShowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" id="attendanceShowContent"></div>
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
    #attendanceShowModal .modal-content {
        overflow: hidden;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
    }

    #formModal .attendance-modal-header,
    #attendanceShowModal .attendance-modal-header {
        min-height: 76px;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }

    #formModal .attendance-modal-heading,
    #attendanceShowModal .attendance-modal-heading {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    #formModal .attendance-modal-heading small,
    #attendanceShowModal .attendance-modal-heading small {
        display: block;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
    }

    #formModal .attendance-modal-icon,
    #attendanceShowModal .attendance-modal-icon {
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

    #formModal .attendance-modal-body,
    #attendanceShowModal .attendance-modal-body {
        padding: 1.5rem;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
    }

    #formModal .attendance-form-intro {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    #formModal .attendance-form-intro h6 {
        color: var(--bs-body-color);
    }

    #formModal .attendance-form-intro p,
    #formModal .form-text {
        color: var(--bs-secondary-color);
    }

    #formModal .form-label {
        margin-bottom: .45rem;
        color: var(--bs-body-color);
    }

    #formModal .form-control,
    #formModal .form-select {
        min-height: 42px;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }

    #formModal textarea.form-control {
        min-height: 96px;
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

    #formModal .attendance-modal-footer,
    #attendanceShowModal .attendance-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
    }

    #formModal .attendance-modal-footer {
        margin: 1.5rem -1.5rem -1.5rem;
    }

    #attendanceShowModal .attendance-detail-card {
        height: 100%;
        padding: 1rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .6rem;
    }

    #attendanceShowModal .attendance-detail-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--bs-secondary-color);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    #attendanceShowModal .attendance-detail-value {
        color: var(--bs-body-color);
        font-weight: 600;
    }

    #attendanceShowModal .attendance-detail-secondary {
        margin-top: .2rem;
        color: var(--bs-secondary-color);
        font-size: .875rem;
    }

    html.dark #formModal .modal-content,
    html.dark #attendanceShowModal .modal-content,
    html[data-bs-theme="dark"] #formModal .modal-content,
    html[data-bs-theme="dark"] #attendanceShowModal .modal-content {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5);
    }

    .attendance-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .attendance-actions .attendance-action-btn {
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

    .attendance-actions .attendance-action-btn i {
        line-height: 1;
    }

    @media (max-width: 575.98px) {
        #formModal .attendance-modal-body,
        #attendanceShowModal .attendance-modal-body {
            padding: 1rem;
        }

        #formModal .attendance-modal-footer {
            margin: 1rem -1rem -1rem;
        }

        #formModal .attendance-modal-footer,
        #attendanceShowModal .attendance-modal-footer {
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
    const showModalElement = document.getElementById('attendanceShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);

    function mostrar_modal(titulo, contenido) {
        $('#formModalLabel').text(titulo);
        $('#formModal .modal-body').html(contenido);
        formModal.show();
    }

    function mostrar_alerta(esExito, mensaje) {
        return Swal.fire({
            title: esExito ? 'Proceso exitoso' : 'Ocurrió un error',
            icon: esExito ? 'success' : 'error',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mostrar_error(xhr, mensajePredeterminado) {
        const response = xhr.responseJSON || {};
        const errores = response.errors ? Object.values(response.errors).flat() : [];
        const mensaje = errores.length
            ? errores.join(' ')
            : (response.mensaje || response.message || mensajePredeterminado);

        mostrar_alerta(false, mensaje);
    }

    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('attendances.index')),
        order: [[2, 'desc']],
        columns: [
            { data: 'dni', name: 'employees.dni' },
            { data: 'employee_name', name: 'employees.first_name' },
            { data: 'date_time', name: 'attendances.date', searchable: false },
            { data: 'type', name: 'attendances.type' },
            { data: 'status', name: 'attendances.status' },
            { data: 'shift_name', name: 'shifts.name' },
            { data: 'notes', name: 'attendances.notes', defaultContent: '' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron asistencias',
            emptyTable: 'Aún no hay asistencias registradas',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refresTable() {
        table.ajax.reload(null, false);
    }

    $('#btnNuevo').on('click', function () {
        $.get(@json(route('attendances.create')), function (response) {
            mostrar_modal('Nueva Asistencia', response);
        }).fail(function (error) {
            mostrar_error(error, 'No se pudo cargar el formulario de asistencia.');
        });
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#attendanceShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrar_error(error, 'No se pudo cargar la asistencia.');
        });
    });

    $(document).on('click', '.btnEditar', function () {
        $.get($(this).data('url'), function (response) {
            mostrar_modal('Editar Asistencia', response);
        }).fail(function (error) {
            mostrar_error(error, 'No se pudo cargar el formulario de asistencia.');
        });
    });

    $(document).on('submit', '#formModal form', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('[type="submit"]').prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                refresTable();
                mostrar_alerta(true, response.mensaje || 'Asistencia guardada correctamente.');
            },
            error: function (error) {
                mostrar_error(error, 'No se pudo guardar la asistencia. Verifique los datos.');
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
            title: '¿Está seguro de eliminar esta asistencia?',
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
                    refresTable();
                    mostrar_alerta(true, response.mensaje || 'Asistencia eliminada correctamente.');
                },
                error: function (error) {
                    mostrar_error(error, 'No se pudo eliminar la asistencia.');
                }
            });
        });
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#attendanceShowContent').empty();
    });
});
</script>
@stop
