@extends('adminlte::page')

@section('title', 'Feriados | RSUProject')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop
@section('content')
    <div class="holidays-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <div>
                <h4 class="mb-1">Listado de Feriados</h4>
                <p class="text-muted small mb-0">Consulta y organiza los días no laborables.</p>
            </div>
            <button type="button" class="btn btn-success" id="btnNuevo">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Feriado
            </button>
        </div>

        {{-- Valores visuales temporales: aún no están vinculados a la base de datos. --}}
        <section class="holiday-summary" aria-label="Resumen de feriados">
            <article class="holiday-summary-card holiday-summary-total">
                <i class="bi bi-calendar-check holiday-summary-icon" aria-hidden="true"></i>
                <strong id="totalFeriados">{{ $feriados['totalFeriados'] }}</strong>
                <span>Total Feriados</span>
            </article>
            <article class="holiday-summary-card holiday-summary-active">
                <i class="bi bi-check-circle holiday-summary-icon" aria-hidden="true"></i>
                <strong id="feriadosActivos">{{ $feriados['feriadosActivos'] }}</strong>
                <span>Feriados Activos</span>
            </article>
            <article class="holiday-summary-card holiday-summary-upcoming">
                <i class="bi bi-hourglass-split holiday-summary-icon" aria-hidden="true"></i>
                <strong id="proximosFeriados">{{ $feriados['proximosFeriados'] }}</strong>                
                <span>Próximos Feriados</span>
            </article>
            <article class="holiday-summary-card holiday-summary-year">
                <i class="bi bi-calendar3 holiday-summary-icon" aria-hidden="true"></i>
                <strong>{{ $year }}</strong>
                <span>Año Actual</span>
            </article>
        </section>

        <section class="holiday-filters card" aria-label="Filtros de feriados">
            <div class="card-body">
                <form class="holiday-filter-form" onsubmit="return false;">
                    <div class="holiday-filter-field">
                        <label for="holidayStartDate">Fecha inicio</label>
                        <input id="holidayStartDate" class="form-control" type="date">
                    </div>
                    <div class="holiday-filter-field">
                        <label for="holidayEndDate">Fecha fin</label>
                        <input id="holidayEndDate" class="form-control" type="date">
                    </div>
                    <div class="holiday-filter-field">
                        <label for="holidayStatus">Estado</label>
                        <select id="holidayStatus" class="form-select">
                            <option>Todos</option>
                        </select>
                    </div>
                    <div class="holiday-filter-actions">
                        <button class="btn btn-primary" type="button"><i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar</button>
                        <button class="btn btn-outline-secondary" type="button"><i class="bi bi-x-lg me-1" aria-hidden="true"></i>Limpiar</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle w-100 mb-0" id="DataTable">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Día</th>
                                <th>Creación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- La tabla se conectará a DataTables cuando enlaces los datos. --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
    <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header brand-modal-header">
                    <div class="brand-modal-heading">
                        <div>
                            <h5 class="modal-title mb-0" id="formModalLabel">Feriados</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body brand-modal-body"></div>
            </div>
        </div>
    </div>
@stop


@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);

    function mostrar_modal(titulo, contenido) {
        $('#formModal #formModalLabel').html(titulo);
        $('#formModal .modal-body').html(contenido);
        formModal.show();
    }

    function cerrar_modal() {
        formModal.hide();
    }

    function mostrar_alerta(tipo, mensaje) {
        const esError = tipo === 0;
        if (esError) return window.RSUValidation.error(mensaje);
        return Swal.fire({
            title: 'Proceso exitoso!',
            icon: 'success',
            text: mensaje,
            confirmButtonText: 'Entendido',
            draggable: true
        });
    }

    function mensajeError(error, mensajePredeterminado) {
        const response = error.responseJSON || {};
        return response.mensaje || response.message || mensajePredeterminado;
    }


    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: "{{ route('holidays.index') }}",
        columns: [
            { data: 'date'},
            { data: 'name'},
            { data: 'description', name: 'description', defaultContent: '' },
            { data: 'state', name: 'state' },
            { data: 'day', name: 'day' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron marcas',
            emptyTable: 'Aún no hay feriados registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });




    function refresTable() {
        refrescarDashboard();
        table.ajax.reload(null, false);
    }
   function refrescarDashboard() {
    $.ajax({
        url: "{{ route('holidays.resumen') }}",
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            $('#totalFeriados').text(data.totalFeriados);
            $('#feriadosActivos').text(data.feriadosActivos);
            $('#proximosFeriados').text(data.proximosFeriados);
        },
        error: function (xhr, textStatus, errorThrown) {
            console.error('Estado:', xhr.status);
            console.error('Tipo de error:', textStatus);
            console.error('Respuesta:', xhr.responseText);
        }
    });
}

    $('#btnNuevo').on('click', function () {
        $.ajax({
            url: "{{ route('holidays.create') }}",
            type: 'GET',
            success: function (response) {
                mostrar_modal('<i class="bi bi-calendar-event me-2"></i>Nuevo Día Feriado', response);
            },
            error: function (error) {
                mostrar_alerta(0, mensajeError(error, 'No se pudo cargar el formulario de feriado.'));
            }
        });
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#brandShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrar_alerta(0, mensajeError(error, 'No se pudo cargar el feriado.'));
        });
    });

    $(document).on('click', '.btnEditar', function () {
        const holiday_id = $(this).data('id');

        $.ajax({
            url: "{{ route('holidays.edit', ':id') }}".replace(':id', holiday_id),
            type: 'GET',
            success: function (response) {
                mostrar_modal('Editar Feriado', response);
            },
            error: function (error) {
                mostrar_alerta(0, mensajeError(error, 'No se pudo cargar el formulario de este formulario.'));
            }
        });
    });

    $(document).on('submit', '#formModal form', function () {
        event.preventDefault();

        if (!this.checkValidity()) {
            this.reportValidity();
            return;
        }

        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                cerrar_modal();
                refresTable();
                mostrar_alerta(1, response.mensaje);
            },
            error: function (error) {
                if (error.status === 422 && error.responseJSON && error.responseJSON.errors) {
                    window.RSUValidation.show(error.responseJSON.errors);
                    return;
                }

                mostrar_alerta(0, mensajeError(error, 'No se pudo guardar este feriado'));
            }
        });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const holiday_id = $(this).data('id');

        Swal.fire({
            title: '¿Está seguro de eliminar?',
            text: 'Esto no se puede revertir!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b54747',
            cancelButtonColor: '#f8faf8',
            confirmButtonText: 'Sí, eliminar!',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('holidays.destroy', ':id') }}".replace(':id', holiday_id),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    refresTable();
                    mostrar_alerta(1, response.mensaje);
                },
                error: function (error) {
                    mostrar_alerta(0, mensajeError(error, 'No se pudo eliminar este feriado.'));
                }
            });
        });
    });
});
</script>

@stop
