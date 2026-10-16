@extends('adminlte::page')

@section('title', 'Contratos | RSU')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
@vite('resources/css/admin-indexes.css')
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
        return Swal.fire({
            title: esExito ? 'Proceso exitoso' : 'Ocurrió un error',
            icon: esExito ? 'success' : 'error',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mensajesError(xhr, fallback) {
        const response = xhr.responseJSON || {};
        const errors = response.errors ? Object.values(response.errors).flat() : [];

        if (errors.length) return errors;
        if (response.mensaje || response.message) return [response.mensaje || response.message];
        if (xhr.status === 404) return ['El contrato o trabajador ya no existe.'];
        if (xhr.status === 419) return ['La sesión ha expirado. Recargue la página.'];

        return [fallback];
    }

    function mostrarErrorGeneral(xhr, fallback) {
        mostrarAlerta(false, mensajesError(xhr, fallback).join(' '));
    }

    function limpiarErrores(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.select2-selection.is-invalid').removeClass('is-invalid');
        form.find('.contract-field-error, .contract-validation-errors').remove();
    }

    function campoParaError(form, field) {
        if (field === 'employee_id' && form.find('[name="employee_display"]').length) {
            return form.find('[name="employee_display"]').first();
        }

        return form.find('[name="' + field + '"]').not('[type="hidden"]').first();
    }

    function mostrarErroresValidacion(form, errors) {
        limpiarErrores(form);
        const generales = [];

        Object.entries(errors).forEach(function ([field, messages]) {
            const input = campoParaError(form, field);

            if (!input.length) {
                generales.push(...messages);
                return;
            }

            input.addClass('is-invalid');
            let target = input;

            if (input.hasClass('select2-hidden-accessible')) {
                target = input.next('.select2-container');
                target.find('.select2-selection').addClass('is-invalid');
            }

            $('<div>', {
                class: 'invalid-feedback contract-field-error',
                text: messages.join(' ')
            }).insertAfter(target);
        });

        if (generales.length) {
            const items = generales.map(function (message) {
                return $('<li>').text(message).prop('outerHTML');
            }).join('');

            form.find('.contract-modal-body').prepend(
                '<div class="alert alert-danger contract-validation-errors" role="alert">' +
                    '<div class="fw-semibold mb-1">No se pudo completar la operación:</div>' +
                    '<ul class="mb-0 ps-3">' + items + '</ul>' +
                '</div>'
            );
        }
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

    $('#formModal').on('change input', '#contract_type, [name="start_date"], #contract_end_date', actualizarFechas);

    $('#formModal').on('input change', '[name]', function () {
        const input = $(this);
        input.removeClass('is-invalid');
        input.next('.contract-field-error').remove();
        input.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        input.next('.select2-container').next('.contract-field-error').remove();
    });

    $('#btnNuevo').on('click', function () {
        abrirFormulario("{{ route('contracts.create') }}");
    });

    $('#DataTable').on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#contractShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrarErrorGeneral(error, 'No se pudo cargar el contrato.');
        });
    });

    $('#DataTable').on('click', '.btnEditar', function () {
        abrirFormulario($(this).data('url'));
    });

    $('#DataTable').on('click', '.btnAnular', function () {
        const button = $(this);

        Swal.fire({
            title: '¿Está seguro de anular el contrato?',
            text: 'Se cerrará con la fecha de hoy. Se conservará el historial y no podrá editarse ni reactivarse.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b54747',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            button.prop('disabled', true);

            $.ajax({
                url: button.data('url'),
                type: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    mostrarAlerta(true, response.mensaje);
                },
                error: function (error) {
                    mostrarErrorGeneral(error, 'No se pudo anular el contrato.');
                },
                complete: function () {
                    button.prop('disabled', false);
                }
            });
        });
    });

    $('#formModal').on('submit', 'form', function (event) {
        event.preventDefault();

        const form = $(this);
        const buttons = form.find('button[type="submit"]').prop('disabled', true);
        limpiarErrores(form);

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
                    mostrarErroresValidacion(form, response.errors);
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
