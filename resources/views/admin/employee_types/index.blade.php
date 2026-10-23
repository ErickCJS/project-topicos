@extends('adminlte::page')

@section('title', 'Tipos de personal | RSU')

@section('content')
    <div class="employee-types-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0">Listado de Tipos de Personal</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Tipo
            </button>
        </div>
        <div class="card">
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

        <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="formModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header employee-type-modal-header">
                        <div class="employee-type-modal-heading">
                            <span class="employee-type-modal-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                            <div>
                                <h5 class="modal-title mb-0" id="formModalLabel">Tipo de personal</h5>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body employee-type-modal-body"></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="employeeTypeShowModal" tabindex="-1"
            aria-labelledby="employeeTypeShowModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content" id="employeeTypeShowContent"></div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('employeeTypeShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);

    function mostrarAlerta(esExito, mensaje) {
        if (!esExito) return window.RSUValidation.error(mensaje);
        return Swal.fire({
            title: 'Proceso exitoso',
            icon: 'success',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mensajeError(xhr, mensajePredeterminado) {
        const response = xhr.responseJSON || {};
        if (xhr.status === 404) return 'No se encontró el tipo de personal solicitado.';
        if (xhr.status === 419) return 'La sesión ha expirado. Recargue la página.';

        return xhr.status === 409 && response.message ? response.message : mensajePredeterminado;
    }

    function mostrarErroresValidacion(errors) {
        window.RSUValidation.show(errors);
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
        ajax: "{{ route('employee-types.index', [], false) }}",
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
            zeroRecords: 'No se encontraron tipos de personal',
            emptyTable: 'Aún no hay tipos de personal registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refrescarTabla() {
        table.ajax.reload(null, false);
    }

    $('#btnNuevo').on('click', function () {
        abrirModal("{{ route('employee-types.create') }}", 'Nuevo Tipo de Personal');
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#employeeTypeShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrarAlerta(false, mensajeError(error, 'No se pudo cargar el tipo de personal.'));
        });
    });

    $(document).on('click', '.btnEditar', function () {
        abrirModal($(this).data('url'), 'Editar Tipo de Personal');
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
                refrescarTabla();
                mostrarAlerta(true, response.message || 'Tipo de personal guardado correctamente.');
            },
            error: function (error) {
                const response = error.responseJSON || {};

                if (error.status === 422 && response.errors) {
                    mostrarErroresValidacion(response.errors);
                    return;
                }

                mostrarAlerta(false, mensajeError(error, 'No se pudo guardar el tipo de personal.'));
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
            title: '¿Está seguro de eliminar este tipo de personal?',
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
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    Accept: 'application/json'
                },
                success: function (response) {
                    refrescarTabla();
                    mostrarAlerta(true, response.message || 'Tipo de personal eliminado correctamente.');
                },
                error: function (error) {
                    mostrarAlerta(false, mensajeError(error, 'No se pudo eliminar el tipo de personal.'));
                }
            });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        $('#formModal .modal-body').empty();
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#employeeTypeShowContent').empty();
    });
});
</script>
@stop
