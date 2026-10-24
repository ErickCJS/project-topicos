@extends('adminlte::page')

@section('title', 'Turnos | RSU')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('content')
<div class="shifts-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0">Listado de Turnos</h4>
        <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo turno
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="shiftsTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Inicio</th>
                            <th>Fin</th>
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

    <div class="modal fade" id="shiftFormModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="shiftFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shiftFormModalLabel">Turno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="shiftFormContent"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shiftShowModal" tabindex="-1"
        aria-labelledby="shiftShowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="shiftShowContent"></div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('shiftFormModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('shiftShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);

    function alertMessage(success, message) {
        if (!success) return window.RSUValidation.error(message);
        return Swal.fire({
            title: 'Proceso exitoso',
            icon: 'success',
            text: message,
            confirmButtonText: 'Entendido'
        });
    }

    function requestError(xhr, fallback) {
        const response = xhr.responseJSON || {};
        if (xhr.status === 422 && response.errors) {
            window.RSUValidation.show(response.errors);
            return;
        }
        if (xhr.status === 404) {
            alertMessage(false, 'No se encontró el turno solicitado.');
            return;
        }
        alertMessage(false, response.message || response.mensaje || fallback);
    }

    function openForm(url, title) {
        $.get(url).done(function (response) {
            $('#shiftFormModalLabel').text(title);
            $('#shiftFormContent').html(response);
            formModal.show();
        }).fail(function (xhr) {
            requestError(xhr, 'No se pudo abrir el formulario del turno.');
        });
    }

    const table = $('#shiftsTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('shifts.index')),
        columns: [
            { data: 'name', name: 'name' },
            { data: 'start_time', name: 'start_time' },
            { data: 'end_time', name: 'end_time' },
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
            zeroRecords: 'No se encontraron turnos',
            emptyTable: 'Aún no hay turnos registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refreshTable() {
        table.ajax.reload(null, false);
    }

    $('#btnNuevo').on('click', function () {
        openForm(@json(route('shifts.create')), 'Nuevo turno');
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url')).done(function (response) {
            $('#shiftShowContent').html(response);
            showModal.show();
        }).fail(function (xhr) {
            requestError(xhr, 'No se pudo cargar el detalle del turno.');
        });
    });

    $(document).on('click', '.btnEditar', function () {
        openForm($(this).data('url'), 'Editar turno');
    });

    $(document).on('submit', '#shiftFormContent form', function (event) {
        event.preventDefault();
        const form = $(this);
        const button = form.find('[type="submit"]').prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                refreshTable();
                alertMessage(true, response.message || 'Turno guardado correctamente.');
            },
            error: function (xhr) {
                requestError(xhr, 'No se pudo guardar el turno.');
            },
            complete: function () {
                button.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar este turno?',
            text: 'Esta acción no se puede revertir.',
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
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
                success: function (response) {
                    refreshTable();
                    alertMessage(true, response.message || 'Turno eliminado correctamente.');
                },
                error: function (xhr) {
                    requestError(xhr, 'No se pudo eliminar el turno.');
                }
            });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        $('#shiftFormContent').empty();
    });
    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#shiftShowContent').empty();
    });
});
</script>
@stop
