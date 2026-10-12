@extends('adminlte::page')

@section('title', 'RSUProject')

@section('content')
    <div class="card">
        <div class="card-header d-flex">
            <div class="w-50">
                <h4>Listado de Tipos de Vehículo</h4>
            </div>
            <div class="w-50 d-flex justify-content-end">
                <button type="button" class="btn btn-success" id="btnNuevo"><i class="fas fa-plus-circle"></i> Nuevo Tipo</button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="DataTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Creación</th>
                        <th>Actualización</th>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalLabel">Tipo de Vehículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    $(function () {
        var table;

        function mostrar_modal(titulo, response) {
            $('#formModalLabel').text(titulo);
            $('#formModal .modal-body').html(response);
            $('#formModal').modal('show');
        }

        function mostrar_alerta(tipo, mensaje) {
            Swal.fire({
                title: tipo === 0 ? 'Ocurrió un error!' : 'Proceso exitoso!',
                icon: tipo === 0 ? 'error' : 'success',
                text: mensaje
            });
        }

        function mostrar_error(err) {
            var response = err.responseJSON || {};
            var mensaje = 'No se pudo completar la operación. Inténtelo nuevamente.';

            if (err.status === 422 && response.errors) {
                mensaje = Object.values(response.errors).flat().join('\n');
            } else if (response.mensaje) {
                mensaje = response.mensaje;
            } else if (err.status === 404) {
                mensaje = 'El tipo de vehículo ya no existe.';
            } else if (err.status === 419) {
                mensaje = 'La sesión ha expirado. Recargue la página.';
            }

            mostrar_alerta(0, mensaje);
        }

        $('#btnNuevo').on('click', function () {
            $.ajax({
                url: "{{ route('vehiclestype.create') }}",
                type: 'GET',
                success: function (response) {
                    mostrar_modal('Nuevo Tipo de Vehículo', response);
                },
                error: mostrar_error
            });
        });

        $('#formModal').on('submit', 'form', function (e) {
            e.preventDefault();
            var form = $(this);
            var buttons = form.find('button[type="submit"]');
            buttons.prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: new FormData(this),
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (response) {
                    $('#formModal').modal('hide');
                    table.ajax.reload(null, false);
                    mostrar_alerta(1, response.mensaje);
                },
                error: mostrar_error,
                complete: function () {
                    buttons.prop('disabled', false);
                }
            });
        });

        $('#DataTable').on('click', '.btnEditar', function () {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('vehiclestype.edit', ':id') }}".replace(':id', id),
                type: 'GET',
                success: function (response) {
                    mostrar_modal('Editar Tipo de Vehículo', response);
                },
                error: mostrar_error
            });
        });

        $('#DataTable').on('click', '.btnEliminar', function () {
            var id = $(this).data('id');
            Swal.fire({
                title: '¿Está seguro de eliminar?',
                text: 'Esta acción no se puede revertir.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar!',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('vehiclestype.destroy', ':id') }}".replace(':id', id),
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        success: function (response) {
                            table.ajax.reload(null, false);
                            mostrar_alerta(1, response.mensaje);
                        },
                        error: mostrar_error
                    });
                }
            });
        });

        table = $('#DataTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('vehiclestype.index') }}",
            columns: [
                { data: 'name' },
                { data: 'description', defaultContent: '' },
                { data: 'created_at' },
                { data: 'updated_at' },
                { data: 'edit', orderable: false, searchable: false },
                { data: 'delete', orderable: false, searchable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json'
            }
        });
    });
</script>
@stop
