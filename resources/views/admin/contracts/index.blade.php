@extends('adminlte::page')

@section('title', 'Contratos | RSU')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('content')
    <div class="contracts-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0">Listado de Contratos</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Contrato
            </button>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Trabajador</th>
                                <th>Tipo</th>
                                <th>Inicio</th>
                                <th>Término</th>
                                <th>Fin de espera para recontratación</th>
                                <th>Sueldo (S/)</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="contractFormModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content" id="contractFormContent"></div>
            </div>
        </div>

        <div class="modal fade" id="contractShowModal" tabindex="-1"
            aria-labelledby="contractShowModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content" id="contractShowContent"></div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('contractShowModal');
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

    function mostrarErrorGeneral(xhr, fallback) {
        const response = xhr.responseJSON || {};
        if (xhr.status === 422 && response.errors) {
            window.RSUValidation.show(response.errors);
            return;
        }
        mostrarAlerta(false, response.mensaje || response.message || fallback);
    }

    function mostrarErroresValidacion(errors) {
        window.RSUValidation.show(errors);
    }

    function sumarMeses(fecha, meses) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return '';

        const partes = fecha.split('-').map(Number);
        const destino = new Date(Date.UTC(partes[0], partes[1] - 1 + meses, 1));
        const ultimoDia = new Date(Date.UTC(destino.getUTCFullYear(), destino.getUTCMonth() + 1, 0)).getUTCDate();
        destino.setUTCDate(Math.min(partes[2], ultimoDia));

        return destino.toISOString().slice(0, 10);
    }

    function actualizarFechas() {
        const temporal = $('#contract_type').val() === 'Temporal';
        const startDate = $('#formModal [name="start_date"]').val() || '';
        const endDate = $('#contract_end_date');

        $('#contract_end_group').toggle(temporal);
        endDate.prop('required', temporal).prop('disabled', !temporal);

        if (!temporal) endDate.val('');

        endDate.attr('min', startDate);
        endDate.attr('max', temporal ? sumarMeses(startDate, 3) : '');
        $('#contract_next_hiring_group').toggle(temporal);
        $('#contract_next_hiring').val(temporal ? sumarMeses(endDate.val(), 2) : '');
    }

    function destruirSelect2() {
        $('#formModal select.select2-hidden-accessible').each(function () {
            $(this).select2('destroy');
        });
    }

    function inicializarSelect2() {
        $('#formModal select[name="employee_id"], #formModal select[name="employee_display"]').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#formModal'),
            placeholder: 'Seleccione un trabajador',
            language: {
                noResults: function () { return 'No se encontraron trabajadores'; },
                searching: function () { return 'Buscando...'; }
            }
        });
    }

    function abrirFormulario(url) {
        destruirSelect2();

        $.get(url, function (response) {
            $('#contractFormContent').html(response);
            actualizarFechas();
            formModal.show();
            inicializarSelect2();
        }).fail(function (error) {
            mostrarErrorGeneral(error, 'No se pudo abrir el formulario del contrato.');
        });
    }

    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: "{{ route('contracts.index') }}",
        columns: [
            { data: 'dni', name: 'employees.dni' },
            { data: 'employee', name: 'employees.last_name' },
            { data: 'type', name: 'contracts.type' },
            { data: 'start_date', name: 'contracts.start_date' },
            { data: 'end_date', name: 'contracts.end_date' },
            { data: 'next_hiring_date', orderable: false, searchable: false },
            { data: 'salary', name: 'contracts.salary' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'edit', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron contratos',
            emptyTable: 'Aún no hay contratos registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    $('#btnNuevo').on('click', function () {
        abrirFormulario(@json(route('contracts.create')));
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'))
            .done(function (response) {
                $('#contractShowContent').html(response);
                showModal.show();
            })
            .fail(function (error) {
                mostrarErrorGeneral(error, 'No se pudo abrir el detalle del contrato.');
            });
    });

    $(document).on('click', '.btnEditar', function () {
        abrirFormulario($(this).data('url'));
    });

    $(document).on('click', '.btnAnular', function () {
        const url = $(this).data('url');

        Swal.fire({
            title: '\u00BFAnular contrato?',
            text: 'El contrato dejara de estar vigente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, anular',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    mostrarAlerta(true, response.mensaje || 'Contrato anulado correctamente.');
                },
                error: function (error) {
                    mostrarErrorGeneral(error, 'No se pudo anular el contrato.');
                }
            });
        });
    });

    $('#formModal').on('change input', '#contract_type, [name="start_date"], #contract_end_date', actualizarFechas);

    $('#formModal').on('submit', 'form', function (event) {
        event.preventDefault();

        const form = $(this);
        const buttons = form.find('button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                table.ajax.reload(null, false);
                mostrarAlerta(true, response.mensaje);
            },
            error: function (error) {
                const response = error.responseJSON || {};

                if (error.status === 422 && response.errors) {
                    mostrarErroresValidacion(response.errors);
                    return;
                }

                mostrarErrorGeneral(error, 'No se pudo guardar el contrato.');
            },
            complete: function () {
                buttons.prop('disabled', false);
            }
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        destruirSelect2();
        $('#contractFormContent').empty();
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#contractShowContent').empty();
    });
});
</script>
@stop
