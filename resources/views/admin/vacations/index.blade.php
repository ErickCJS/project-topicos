@extends('adminlte::page')

@section('title', 'Vacaciones | RSU')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('content')
<div class="vacations-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0">Gestión de Vacaciones</h4>
        <button type="button" class="btn btn-success ms-auto" id="btnNuevaVacacion">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nueva vacación
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="vacationsTable">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>DNI</th>
                            <th>Contrato</th>
                            <th>Año</th>
                            <th>Fechas</th>
                            <th>Días programados</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vacationFormModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="vacationFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="vacationFormContent"></div>
        </div>
    </div>

    <div class="modal fade" id="vacationShowModal" tabindex="-1"
        aria-labelledby="vacationShowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="vacationShowContent"></div>
        </div>
    </div>

    <div class="modal fade" id="vacationRejectModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="vacationRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="vacationRejectForm" method="POST">
                    @csrf
                    <div class="modal-header vacation-modal-header">
                        <div class="vacation-modal-heading">
                            <span class="vacation-modal-icon" aria-hidden="true"><i class="bi bi-x-circle"></i></span>
                            <div><h5 class="modal-title mb-0" id="vacationRejectModalLabel">Rechazar solicitud</h5></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body vacation-modal-body">
                        <div class="alert alert-danger vacation-reject-error d-none" role="alert"></div>
                        <label for="vacation_rejection_reason" class="form-label">Motivo del rechazo *</label>
                        <textarea id="vacation_rejection_reason" name="rejection_reason" class="form-control" rows="4" maxlength="1000" required></textarea>
                        <div class="invalid-feedback d-none" data-error-for="rejection_reason"></div>
                    </div>
                    <div class="modal-footer vacation-modal-footer">
                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i>Rechazar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('vacationFormModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('vacationShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);
    const rejectModalElement = document.getElementById('vacationRejectModal');
    const rejectModal = bootstrap.Modal.getOrCreateInstance(rejectModalElement);
    let balanceRequest = null;

    function alertMessage(success, message) {
        return Swal.fire({
            icon: success ? 'success' : 'error',
            title: success ? 'Proceso exitoso' : 'Ocurrió un error',
            text: message,
            confirmButtonText: 'Entendido'
        });
    }

    function clearErrors(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('[data-error-for]').text('').addClass('d-none');
        form.find('.vacation-general-error').addClass('d-none').text('');
    }

    function showErrors(form, errors) {
        clearErrors(form);
        Object.entries(errors || {}).forEach(function (entry) {
            const field = entry[0];
            const messages = Array.isArray(entry[1]) ? entry[1] : [entry[1]];
            const input = form.find('[name="' + field + '"]');
            input.addClass('is-invalid');
            form.find('[data-error-for="' + field + '"]').removeClass('d-none').text(messages[0] || 'Valor no válido.');
        });
    }

    function showRequestError(xhr, fallback, form) {
        const response = xhr.responseJSON || {};
        if (xhr.status === 422 && response.errors && form) {
            showErrors(form, response.errors);
            const unmapped = Object.keys(response.errors).filter(function (field) {
                return !form.find('[data-error-for="' + field + '"]').length;
            });
            if (unmapped.length) {
                form.find('.vacation-general-error').removeClass('d-none')
                    .text(response.errors[unmapped[0]][0]);
            }
            return;
        }
        alertMessage(false, response.message || response.mensaje || fallback);
    }

    function inclusiveDays(start, end) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(start) || !/^\d{4}-\d{2}-\d{2}$/.test(end)) return 0;
        const from = new Date(start + 'T00:00:00Z');
        const to = new Date(end + 'T00:00:00Z');
        if (to < from) return 0;
        return Math.round((to - from) / 86400000) + 1;
    }

    function refreshCalculation() {
        const form = $('#vacationFormContent form');
        if (!form.length) return;

        const startInput = form.find('[name="start_date"]');
        const endInput = form.find('[name="end_date"]');
        const isHistorical = startInput.attr('data-historical') === '1';
        const isEditing = Boolean(form.data('vacation-id'));
        const originalStart = startInput.attr('data-original-start') || '';
        const now = new Date();
        const today = [
            now.getFullYear(),
            String(now.getMonth() + 1).padStart(2, '0'),
            String(now.getDate()).padStart(2, '0')
        ].join('-');

        // Crear: no permitir fechas anteriores a hoy.
        // Editar: conservar la fecha original si el período ya comenzó.
        // Regularizar históricos: permitir fechas anteriores.
        const minimumStart = isHistorical ? '' :
            (isEditing && originalStart && originalStart < today ? originalStart : today);

        startInput.attr('min', minimumStart);

        const start = startInput.val() || '';

        // La fecha de término nunca puede ser anterior al inicio.
        endInput.attr('min', start || minimumStart);

        if (endInput.val() && start && endInput.val() < start) {
            endInput.val('');
        }

        const end = endInput.val() || '';
        const days = inclusiveDays(start, end);
        const employeeId = form.find('[name="employee_id"]').val();
        const yearInput = form.find('[name="year"]');
        if (/^\d{4}-/.test(start) && !yearInput.prop('readonly')) yearInput.val(start.slice(0, 4));
        const year = yearInput.val();

        form.find('[data-vacation-days]').text(days || '—');
        form.find('[name="scheduled_days_preview"]').val(days || '');
        form.find('[data-vacation-balance]').text('—');

        if (!employeeId || !year || !days) return;
        if (balanceRequest) balanceRequest.abort();

        balanceRequest = $.get(@json(route('vacations.balance')), {
            employee_id: employeeId,
            year: year,
            excluding_id: form.data('vacation-id') || ''
        }).done(function (response) {
            form.find('[data-vacation-balance]').text(Math.max(0, response.available_days - days));
        });
    }

    function openForm(url) {
        $.get(url).done(function (response) {
            $('#vacationFormContent').html(response);
            formModal.show();
            refreshCalculation();
        }).fail(function (xhr) {
            showRequestError(xhr, 'No se pudo abrir el formulario de vacaciones.');
        });
    }

    const table = $('#vacationsTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('vacations.index')),
        order: [[3, 'desc'], [4, 'asc']],
        columns: [
            { data: 'employee_name', name: 'employee_name' },
            { data: 'dni', name: 'employees.dni' },
            { data: 'contract', orderable: false, searchable: false },
            { data: 'year', name: 'vacations.year' },
            { data: 'period', name: 'vacations.start_date', searchable: false },
            { data: 'scheduled_days', name: 'vacations.scheduled_days' },
            { data: 'balance', orderable: false, searchable: false },
            { data: 'status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron vacaciones',
            emptyTable: 'Aún no hay vacaciones registradas',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    $('#btnNuevaVacacion').on('click', function () {
        openForm(@json(route('vacations.create')));
    });

    $(document).on('click', '.vacations-module .btnVer', function () {
        $.get($(this).data('url')).done(function (response) {
            $('#vacationShowContent').html(response);
            showModal.show();
        }).fail(function (xhr) {
            showRequestError(xhr, 'No se pudo abrir el detalle de la vacación.');
        });
    });

    $(document).on('click', '.vacations-module .btnEditar', function () {
        openForm($(this).data('url'));
    });

    $(document).on('click', '.vacations-module .btnAprobar', function () {
        const url = $(this).data('url');
        Swal.fire({
            title: '¿Aprobar solicitud?',
            text: 'Se descontarán sus días del saldo anual y bloqueará la asignación del trabajador en esas fechas.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#198754'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    alertMessage(true, response.mensaje || 'Solicitud aprobada correctamente.');
                },
                error: function (xhr) {
                    showRequestError(xhr, 'No se pudo aprobar la solicitud.');
                }
            });
        });
    });

    $(document).on('click', '.vacations-module .btnRechazar', function () {
        $('#vacationRejectForm').attr('action', $(this).data('url'));
        $('#vacation_rejection_reason').val('').removeClass('is-invalid');
        $('[data-error-for="rejection_reason"]').text('').addClass('d-none');
        $('.vacation-reject-error').addClass('d-none').text('');
        rejectModal.show();
    });

    $('#vacationFormModal').on('input change', '[name="employee_id"], [name="year"], [name="start_date"], [name="end_date"]', refreshCalculation);

    $('#vacationFormModal').on('submit', 'form', function (event) {
        event.preventDefault();
        const form = $(this);
        const submit = form.find('[type="submit"]').prop('disabled', true);
        clearErrors(form);

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
                alertMessage(true, response.mensaje || 'Vacación guardada correctamente.');
            },
            error: function (xhr) {
                showRequestError(xhr, 'No se pudo guardar la vacación.', form);
            },
            complete: function () {
                submit.prop('disabled', false);
            }
        });
    });

    $('#vacationRejectForm').on('submit', function (event) {
        event.preventDefault();
        const form = $(this);
        const submit = form.find('[type="submit"]').prop('disabled', true);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('[data-error-for]').text('').addClass('d-none');
        form.find('.vacation-reject-error').addClass('d-none').text('');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: { Accept: 'application/json' },
            success: function (response) {
                rejectModal.hide();
                table.ajax.reload(null, false);
                alertMessage(true, response.mensaje || 'Solicitud rechazada correctamente.');
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                if (xhr.status === 422 && response.errors) {
                    const messages = response.errors.rejection_reason || response.errors.status;
                    if (messages) {
                        form.find('[name="rejection_reason"]').addClass('is-invalid');
                        form.find('[data-error-for="rejection_reason"]').removeClass('d-none').text(messages[0]);
                    }
                    return;
                }
                showRequestError(xhr, 'No se pudo rechazar la solicitud.');
            },
            complete: function () {
                submit.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.vacations-module .btnEliminar', function () {
        const url = $(this).data('url');
        Swal.fire({
            title: '¿Eliminar esta vacación?',
            text: 'Se liberarán sus días del saldo anual. Esta acción no se puede revertir.',
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
                data: { confirmed: 1 },
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    alertMessage(true, response.mensaje || 'Vacación eliminada correctamente.');
                },
                error: function (xhr) {
                    showRequestError(xhr, 'No se pudo eliminar la vacación.');
                }
            });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        if (balanceRequest) balanceRequest.abort();
        $('#vacationFormContent').empty();
    });
    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#vacationShowContent').empty();
    });
    rejectModalElement.addEventListener('hidden.bs.modal', function () {
        $('#vacation_rejection_reason').val('').removeClass('is-invalid');
    });
});
</script>
@stop
