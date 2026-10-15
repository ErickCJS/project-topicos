@extends('adminlte::page')

@section('title', 'Tipos de personal | RSU')

@section('content')
<div class="card">
    <div class="card-header d-flex">
        <div class="w-50">
            <h4>Listado de Tipos de Personal</h4>
        </div>
        <div class="w-50 d-flex justify-content-end">
            <button class="btn btn-success" id="btnNuevo">
                <i class="fas fa-plus-circle"></i> Nuevo Tipo
            </button>
        </div>
    </div>

    <div class="card-body">
        <table class="table table-striped" id="DataTable" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Creación</th>
                    <th>Actualización</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1"
     aria-labelledby="formModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalLabel"></h5>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function () {

    var table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('employee-types.index') }}",
        columns: [
            { data: 'name' },
            { data: 'description' },
            { data: 'created_at' },
            { data: 'updated_at' },
            {
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
        }
    });

    function abrirModal(url, titulo) {
        $.get(url, function (response) {
            $('#formModalLabel').text(titulo);
            $('#formModal .modal-body').html(response);
            $('#formModal').modal('show');
        }).fail(function () {
            Swal.fire('Error', 'No se pudo abrir el formulario.', 'error');
        });
    }

    $('#btnNuevo').click(function () {
        abrirModal(
            "{{ route('employee-types.create') }}",
            'Nuevo Tipo de Personal'
        );
    });

    $(document).on('click', '.btnEditar', function () {
        var id = $(this).data('id');

        abrirModal(
            "{{ route('employee-types.edit', ':id') }}".replace(':id', id),
            'Editar Tipo de Personal'
        );
    });

    $(document).on('submit', '#formModal form', function (e) {
        e.preventDefault();

        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $('#formModal').modal('hide');
                table.ajax.reload(null, false);

                Swal.fire({
                    title: 'Proceso exitoso',
                    text: response.message,
                    icon: 'success'
                });
            },
            error: function (error) {
                var mensaje = error.responseJSON?.message
                    ?? 'No se pudo guardar el tipo de personal.';

                Swal.fire('Error', mensaje, 'error');
            }
        });
    });

    $(document).on('click', '.btnEliminar', function () {
        var id = $(this).data('id');

        Swal.fire({
            title: '¿Está seguro de eliminar?',
            text: 'Esta acción no se puede revertir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {

            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('employee-types.destroy', ':id') }}"
                    .replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    Swal.fire('Eliminado', response.message, 'success');
                },
                error: function (error) {
                    var mensaje = error.responseJSON?.message
                        ?? 'No se pudo eliminar el tipo de personal.';

                    Swal.fire('Error', mensaje, 'error');
                }
            });
        });
    });

});
</script>
@stop