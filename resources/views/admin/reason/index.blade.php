@extends('adminlte::page')

@section('title', 'Motivos | RSU')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h4 class="mb-0"><i class="bi bi-list-check me-2" aria-hidden="true"></i>Listado de Motivos</h4>
        <button type="button" class="btn btn-success" id="btnNuevoMotivo">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo motivo
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="reasonTable">
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
</div>

<div class="modal fade" id="motivoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" id="motivoModalContent"></div>
    </div>
</div>
@stop
@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop
@section('js')
<script>
$(function () {
    const modal = new bootstrap.Modal(document.getElementById('motivoModal'));

    const table = $('#reasonTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: @json(route('reason.index')),
        columns: [
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description' },
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
            zeroRecords: 'No se encontraron motivos',
            emptyTable: 'Aún no hay motivos registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refreshTable() {
        table.ajax.reload(null, false);
    }

    function showModal(url) {
        $.get(url).done(function (response) {
            $('#motivoModalContent').html(response);
            modal.show();
        }).fail(function (xhr) {
            const message = xhr.responseJSON?.message || 'No se pudo abrir el formulario.';
            window.RSUValidation?.error(message);
        });
    }

    $('#btnNuevoMotivo').on('click', function () {
        showModal(@json(route('reason.create')));
    });

    $(document).on('click', '.btnEditar', function () {
        showModal($(this).data('url'));
    });

    $(document).on('submit', '#motivoForm', function (event) {
        event.preventDefault();
        const form = $(this);
        const method = form.find('input[name="_method"]').val() || 'POST';
        const url = form.attr('action');

        $.ajax({
            url: url,
            method: method,
            data: form.serialize(),
            success: function (response) {
                modal.hide();
                refreshTable();
                Swal.fire({
                    title: 'Proceso exitoso',
                    icon: 'success',
                    text: response.message || 'Operación realizada correctamente.',
                    confirmButtonText: 'Entendido'
                });
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                if (xhr.status === 422 && response.errors) {
                    form.find('.is-invalid').removeClass('is-invalid');
                    form.find('.invalid-feedback').addClass('d-none').text('');
                    Object.keys(response.errors).forEach(function (key) {
                        const input = form.find('[name="' + key + '"]');
                        const errorBox = form.find('[data-error-for="' + key + '"]');
                        input.addClass('is-invalid');
                        errorBox.removeClass('d-none').text(response.errors[key][0]);
                    });
                    return;
                }
                const message = response.message || 'No se pudo guardar el motivo.';
                window.RSUValidation?.error(message);
            }
        });
    });

    $(document).on('click', '.btnEliminar', function () {
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Eliminar motivo?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                method: 'DELETE',
                data: { _token: @json(csrf_token()) },
                success: function (response) {
                    refreshTable();
                    Swal.fire({
                        title: 'Motivo eliminado',
                        icon: 'success',
                        text: response.message || 'El motivo se eliminó correctamente.',
                        confirmButtonText: 'Entendido'
                    });
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};
                    window.RSUValidation?.error(response.message || 'No se pudo eliminar el motivo.');
                }
            });
        });
    });

});
</script>
@stop
