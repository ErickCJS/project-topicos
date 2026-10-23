@extends('adminlte::page')

@section('title', 'Tipos de Vehículo | RSUProject')

@section('content')
    <div class="vehicle-types-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0">Listado de Tipos de Vehículo</h4>
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

        <div class="modal fade vehicle-type-modal" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header vehicle-type-modal-header">
                        <div class="vehicle-type-modal-heading">
                            <span class="vehicle-type-modal-icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                            <div>
                                <h5 class="modal-title mb-0" id="formModalLabel">Tipo de Vehículo</h5>
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
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('vehicleTypeShowModal');
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

    function mostrarError(error) {
        const response = error.responseJSON || {};
        if (error.status === 422 && response.errors) {
            window.RSUValidation.show(response.errors);
            return;
        }

        let mensaje = response.mensaje || 'No se pudo completar la operación. Inténtelo nuevamente.';
        if (error.status === 404) mensaje = 'El tipo de vehículo ya no existe.';
        if (error.status === 419) mensaje = 'La sesión ha expirado. Recargue la página.';
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
        ajax: "{{ route('vehiclestype.index', [], false) }}",
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
        abrirFormulario("{{ route('vehiclestype.create') }}", 'Nuevo Tipo de Vehículo');
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

    $('#formModal').on('submit', 'form', function (event) {
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
                mostrarAlerta(true, response.mensaje || 'Tipo de vehículo guardado correctamente.');
            },
            error: function (error) {
                mostrarError(error);
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
