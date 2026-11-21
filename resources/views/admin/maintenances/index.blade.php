@extends('adminlte::page')

@section('title', 'Mantenimientos | RSU')
@section('plugins.Select2', true)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h4 class="mb-0"><i class="fas fa-screwdriver-wrench me-2" aria-hidden="true"></i>Listado de mantenimientos</h4>
        @can('permission', 'mantenimientos.create')
            <button type="button" class="btn btn-success" id="btnNuevoMantenimiento">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo mantenimiento
            </button>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="maintenanceTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Fecha de inicio</th>
                            <th>Fecha final</th>
                            <th>Registrado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="maintenanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenanceModalTitle">Nuevo mantenimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="maintenanceForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="maintenanceName" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="maintenanceName" name="name" maxlength="150" required>
                        <div class="invalid-feedback" data-error-for="name"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="maintenanceStartDate" class="form-label">Fecha de inicio</label>
                            <input type="date" class="form-control" id="maintenanceStartDate" name="start_date" required>
                            <div class="invalid-feedback" data-error-for="start_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="maintenanceEndDate" class="form-label">Fecha final</label>
                            <input type="date" class="form-control" id="maintenanceEndDate" name="end_date" required>
                            <div class="invalid-feedback" data-error-for="end_date"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="scheduleModalTitle">Horarios</h5>
                    <small class="text-muted" id="schedulePeriod"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm" class="border rounded p-3 mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="scheduleVehicle" class="form-label">Vehículo</label>
                            <select class="form-select" id="scheduleVehicle" name="vehicle_id" required></select>
                            <div class="invalid-feedback" data-error-for="vehicle_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="scheduleResponsible" class="form-label">Responsable</label>
                            <select class="form-select" id="scheduleResponsible" name="responsible_id" required>
                                <option value="">Seleccione primero el día</option>
                            </select>
                            <div class="invalid-feedback" data-error-for="responsible_id"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="scheduleType" class="form-label">Tipo</label>
                            <select class="form-select" id="scheduleType" name="type" required>
                                <option value="">Seleccione</option>
                                <option value="Preventivo">Preventivo</option>
                                <option value="Limpieza">Limpieza</option>
                                <option value="Reparación">Reparación</option>
                            </select>
                            <div class="invalid-feedback" data-error-for="type"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="scheduleWeekday" class="form-label">Día</label>
                            <select class="form-select" id="scheduleWeekday" name="weekday" required>
                                <option value="">Seleccione</option>
                                <option value="1">Lunes</option>
                                <option value="2">Martes</option>
                                <option value="3">Miércoles</option>
                                <option value="4">Jueves</option>
                                <option value="5">Viernes</option>
                                <option value="6">Sábado</option>
                                <option value="7">Domingo</option>
                            </select>
                            <div class="invalid-feedback" data-error-for="weekday"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="scheduleStartTime" class="form-label">Hora de inicio</label>
                            <input type="time" class="form-control" id="scheduleStartTime" name="start_time" required>
                            <div class="invalid-feedback" data-error-for="start_time"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="scheduleEndTime" class="form-label">Hora final</label>
                            <input type="time" class="form-control" id="scheduleEndTime" name="end_time" required>
                            <div class="invalid-feedback" data-error-for="end_time"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary d-none" id="cancelScheduleEdit">Cancelar edición</button>
                        <button type="submit" class="btn btn-primary" id="saveScheduleButton">Agregar horario</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="scheduleTable">
                        <thead>
                            <tr>
                                <th>Vehículo</th>
                                <th>Responsable</th>
                                <th>Tipo</th>
                                <th>Día</th>
                                <th>Horario</th>
                                <th>Fechas generadas</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="detailModalTitle">Días generados</h5>
                    <small class="text-muted" id="detailScheduleInfo"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="detailTable">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Observación</th>
                                <th>Imagen</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar día de mantenimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="detailForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha programada</label>
                        <input type="text" class="form-control" id="detailDate" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="detailObservation" class="form-label">Observación</label>
                        <textarea class="form-control" id="detailObservation" name="observation" rows="3" maxlength="1000"></textarea>
                        <div class="invalid-feedback" data-error-for="observation"></div>
                    </div>
                    <div class="mb-3">
                        <label for="detailStatus" class="form-label">Estado</label>
                        <select class="form-select" id="detailStatus" name="status" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Realizado">Realizado</option>
                            <option value="No realizado">No realizado</option>
                        </select>
                        <div class="invalid-feedback" data-error-for="status"></div>
                    </div>
                    <div class="mb-3">
                        <label for="detailImage" class="form-label">Imagen (máximo 2 MB)</label>
                        <input type="file" class="form-control" id="detailImage" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        <div class="invalid-feedback" data-error-for="image"></div>
                    </div>
                    <div id="detailCurrentImageContainer" class="d-none">
                        <p class="form-label mb-1">Imagen registrada</p>
                        <img id="detailCurrentImage" src="" alt="Imagen del mantenimiento" class="img-fluid rounded border" style="max-height: 180px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
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
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('maintenanceModal'));
    const scheduleModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal'));
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('detailModal'));
    const detailEditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('detailEditModal'));
    const form = $('#maintenanceForm');
    const scheduleForm = $('#scheduleForm');
    const detailForm = $('#detailForm');
    let editingUrl = @json(route('maintenances.store'));
    let editingMethod = 'POST';
    let schedulesUrl = null;
    let scheduleEditingUrl = null;
    let scheduleEditingMethod = 'POST';
    let schedulePermissions = {};
    let detailsUrl = null;
    let detailEditingUrl = null;

    const weekdayNames = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo' };

    const table = $('#maintenanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: @json(route('maintenances.index')),
        columns: [
            { data: 'name', name: 'name' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            zeroRecords: 'No se encontraron mantenimientos',
            emptyTable: 'Aún no hay mantenimientos registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function clearForm() {
        form[0].reset();
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
    }

    function showErrors(errors, targetForm) {
        targetForm = targetForm || form;
        targetForm.find('.is-invalid').removeClass('is-invalid');
        targetForm.find('.invalid-feedback').text('');
        Object.keys(errors || {}).forEach(function (field) {
            targetForm.find('[name="' + field + '"]').addClass('is-invalid');
            targetForm.find('[data-error-for="' + field + '"]').text(errors[field][0]);
        });
    }

    function clearScheduleForm() {
        scheduleForm[0].reset();
        scheduleForm.find('.is-invalid').removeClass('is-invalid');
        scheduleForm.find('.invalid-feedback').text('');
        $('#cancelScheduleEdit').addClass('d-none');
        $('#saveScheduleButton').text('Agregar horario');
        scheduleEditingUrl = schedulesUrl;
        scheduleEditingMethod = 'POST';
    }

    function fillScheduleOptions(response) {
        const vehicleSelect = $('#scheduleVehicle').empty().append('<option value="">Seleccione un vehículo</option>');
        response.vehicles.forEach(function (vehicle) {
            vehicleSelect.append($('<option>').val(vehicle.id).text(vehicle.code + ' - ' + vehicle.name + ' (' + vehicle.plate + ')'));
        });

        const responsibleSelect = $('#scheduleResponsible').empty().append('<option value="">Seleccione primero el día</option>');
        response.responsibles.forEach(function (person) {
            responsibleSelect.append($('<option>').val(person.id).text(person.dni + ' - ' + person.first_name + ' ' + person.last_name));
        });
        initializeResponsibleSelect();
    }

    function initializeResponsibleSelect() {
        const responsibleSelect = $('#scheduleResponsible');
        if (responsibleSelect.hasClass('select2-hidden-accessible')) {
            responsibleSelect.select2('destroy');
        }
        responsibleSelect.select2({
            width: '100%',
            dropdownParent: $('#scheduleModal'),
            placeholder: 'Seleccione un responsable',
            allowClear: true
        });
    }

    function loadResponsibleOptions(weekday, selectedId) {
        const responsibleSelect = $('#scheduleResponsible').empty();
        if (!weekday) {
            responsibleSelect.append('<option value="">Seleccione primero el día</option>');
            initializeResponsibleSelect();
            return;
        }

        responsibleSelect.append('<option value="">Cargando responsables...</option>');
        $.get(schedulesUrl, { weekday: weekday }).done(function (response) {
            responsibleSelect.empty().append('<option value="">Seleccione un responsable</option>');
            response.responsibles.forEach(function (person) {
                responsibleSelect.append($('<option>').val(person.id).text(person.dni + ' - ' + person.first_name + ' ' + person.last_name));
            });
            initializeResponsibleSelect();
            if (selectedId) responsibleSelect.val(String(selectedId)).trigger('change');
        }).fail(function () {
            responsibleSelect.empty().append('<option value="">No se pudieron cargar responsables</option>');
            initializeResponsibleSelect();
        });
    }

    function renderSchedules(schedules) {
        const body = $('#scheduleTable tbody').empty();

        if (!schedules.length) {
            body.append($('<tr>').append($('<td>').attr('colspan', 7).addClass('text-center text-muted').text('No hay horarios registrados.')));
            return;
        }

        schedules.forEach(function (schedule) {
            const actions = $('<div>').addClass('d-flex justify-content-center gap-2');
            if (schedulePermissions.view) {
                $('<button>').attr({ type: 'button', title: 'Ver días generados' }).addClass('btn btn-outline-primary btn-sm btnVerDetalles')
                    .data('url', schedulesUrl + '/' + schedule.id + '/details').append('<i class="bi bi-calendar-check"></i>').appendTo(actions);
            }
            if (schedulePermissions.edit) {
                $('<button>').attr({ type: 'button', title: 'Editar horario' }).addClass('btn btn-outline-warning btn-sm btnEditarHorario')
                    .data('schedule', schedule).append('<i class="bi bi-pencil-square"></i>').appendTo(actions);
            }
            if (schedulePermissions.delete) {
                $('<button>').attr({ type: 'button', title: 'Eliminar horario' }).addClass('btn btn-outline-danger btn-sm btnEliminarHorario')
                    .data('url', schedulesUrl + '/' + schedule.id).append('<i class="bi bi-trash"></i>').appendTo(actions);
            }

            body.append($('<tr>').append(
                $('<td>').text(schedule.vehicle),
                $('<td>').text(schedule.responsible),
                $('<td>').text(schedule.type),
                $('<td>').text(weekdayNames[schedule.weekday] || schedule.weekday),
                $('<td>').text(schedule.start_time + ' - ' + schedule.end_time),
                $('<td>').text(schedule.dates.join(', ')),
                $('<td>').append(actions)
            ));
        });
    }

    function renderDetails(details) {
        const body = $('#detailTable tbody').empty();

        if (!details.length) {
            body.append($('<tr>').append($('<td>').attr('colspan', 5).addClass('text-center text-muted').text('No hay días generados.')));
            return;
        }

        details.forEach(function (detail) {
            const image = detail.image_url
                ? $('<img>').attr({ src: detail.image_url, alt: 'Imagen del mantenimiento' }).css({
                    width: '80px',
                    height: '60px',
                    objectFit: 'cover',
                    borderRadius: '6px'
                })
                : $('<span>').addClass('text-muted').text('Sin imagen');
            const actions = $('<div>').addClass('d-flex justify-content-center');
            if (schedulePermissions.edit) {
                $('<button>').attr({ type: 'button', title: 'Editar día' }).addClass('btn btn-outline-warning btn-sm btnEditarDetalle')
                    .data('detail', detail).append('<i class="bi bi-pencil-square"></i>').appendTo(actions);
            }

            body.append($('<tr>').append(
                $('<td>').text(detail.scheduled_date),
                $('<td>').text(detail.observation || '—'),
                $('<td>').append(image),
                $('<td>').text(detail.status),
                $('<td>').append(actions)
            ));
        });
    }

    function loadDetails(url) {
        detailsUrl = url;
        $.get(url).done(function (response) {
            $('#detailModalTitle').text('Días generados');
            $('#detailScheduleInfo').text(response.schedule.vehicle + ' · ' + response.schedule.type);
            renderDetails(response.details || []);
            detailModal.show();
        }).fail(function (xhr) {
            const response = xhr.responseJSON || {};
            window.RSUValidation?.error(response.message || 'No se pudieron cargar los días generados.');
        });
    }

    function loadSchedules(url) {
        schedulesUrl = url;
        $.get(url).done(function (response) {
            schedulePermissions = response.permissions || {};
            fillScheduleOptions(response);
            clearScheduleForm();
            $('#scheduleModalTitle').text('Horarios: ' + response.maintenance.name);
            $('#schedulePeriod').text('Período: ' + response.maintenance.start_date + ' al ' + response.maintenance.end_date);
            renderSchedules(response.schedules || []);
            $('#scheduleForm').toggle(!!schedulePermissions.create);
            scheduleModal.show();
        }).fail(function (xhr) {
            const response = xhr.responseJSON || {};
            window.RSUValidation?.error(response.message || 'No se pudieron cargar los horarios.');
        });
    }

    $('#btnNuevoMantenimiento').on('click', function () {
        clearForm();
        $('#maintenanceModalTitle').text('Nuevo mantenimiento');
        editingUrl = @json(route('maintenances.store'));
        editingMethod = 'POST';
        modal.show();
    });

    $(document).on('click', '.btnEditarMantenimiento', function () {
        clearForm();
        const button = $(this);
        $('#maintenanceModalTitle').text('Editar mantenimiento');
        $('#maintenanceName').val(button.data('name'));
        $('#maintenanceStartDate').val(button.data('start-date'));
        $('#maintenanceEndDate').val(button.data('end-date'));
        editingUrl = button.data('url');
        editingMethod = 'PUT';
        modal.show();
    });

    $(document).on('click', '.btnHorariosMantenimiento', function () {
        loadSchedules($(this).data('url'));
    });

    $(document).on('click', '.btnVerDetalles', function () {
        loadDetails($(this).data('url'));
    });

    $(document).on('click', '.btnEditarDetalle', function () {
        const detail = $(this).data('detail');
        detailEditingUrl = detailsUrl + '/' + detail.id;
        $('#detailDate').val(detail.scheduled_date);
        $('#detailObservation').val(detail.observation || '');
        $('#detailStatus').val(detail.status);
        $('#detailImage').val('');
        detailForm.find('.is-invalid').removeClass('is-invalid');
        detailForm.find('.invalid-feedback').text('');
        if (detail.image_url) {
            $('#detailCurrentImage').attr('src', detail.image_url);
            $('#detailCurrentImageContainer').removeClass('d-none');
        } else {
            $('#detailCurrentImageContainer').addClass('d-none');
        }
        detailEditModal.show();
    });

    detailForm.on('submit', function (event) {
        event.preventDefault();
        const data = new FormData(detailForm[0]);
        data.append('_method', 'PUT');

        $.ajax({
            url: detailEditingUrl,
            method: 'POST',
            data: data,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
            success: function (response) {
                detailEditModal.hide();
                loadDetails(detailsUrl);
                Swal.fire({ title: 'Proceso exitoso', icon: 'success', text: response.message, confirmButtonText: 'Entendido' });
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                if (xhr.status === 422 && response.errors) {
                    showErrors(response.errors, detailForm);
                    return;
                }
                window.RSUValidation?.error(response.message || 'No se pudo actualizar el día.');
            }
        });
    });

    $(document).on('click', '.btnEditarHorario', function () {
        const schedule = $(this).data('schedule');
        scheduleForm.find('[name="vehicle_id"]').val(schedule.vehicle_id);
        scheduleForm.find('[name="type"]').val(schedule.type);
        scheduleForm.find('[name="weekday"]').val(schedule.weekday);
        scheduleForm.find('[name="start_time"]').val(schedule.start_time);
        scheduleForm.find('[name="end_time"]').val(schedule.end_time);
        loadResponsibleOptions(schedule.weekday, schedule.responsible_id);
        scheduleEditingUrl = schedulesUrl + '/' + schedule.id;
        scheduleEditingMethod = 'PUT';
        $('#cancelScheduleEdit').removeClass('d-none');
        $('#saveScheduleButton').text('Actualizar horario');
    });

    $('#cancelScheduleEdit').on('click', clearScheduleForm);
    $('#scheduleWeekday').on('change', function () {
        loadResponsibleOptions($(this).val());
    });

    scheduleForm.on('submit', function (event) {
        event.preventDefault();
        $.ajax({
            url: scheduleEditingUrl,
            method: scheduleEditingMethod,
            data: scheduleForm.serialize(),
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
            success: function () {
                loadSchedules(schedulesUrl);
                Swal.fire({ title: 'Proceso exitoso', icon: 'success', text: 'Horario guardado correctamente.', confirmButtonText: 'Entendido' });
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                if (xhr.status === 422 && response.errors) {
                    showErrors(response.errors, scheduleForm);
                    return;
                }
                window.RSUValidation?.error(response.message || 'No se pudo guardar el horario.');
            }
        });
    });

    $(document).on('click', '.btnEliminarHorario', function () {
        const url = $(this).data('url');
        Swal.fire({
            title: '¿Eliminar horario?',
            text: 'También se eliminarán sus fechas generadas.',
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
                headers: { Accept: 'application/json' },
                success: function () {
                    loadSchedules(schedulesUrl);
                    Swal.fire({ title: 'Horario eliminado', icon: 'success', text: 'El horario y sus fechas fueron eliminados.', confirmButtonText: 'Entendido' });
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};
                    window.RSUValidation?.error(response.message || 'No se pudo eliminar el horario.');
                }
            });
        });
    });

    form.on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: editingUrl,
            method: editingMethod,
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
            success: function (response) {
                modal.hide();
                table.ajax.reload(null, false);
                Swal.fire({ title: 'Proceso exitoso', icon: 'success', text: response.message, confirmButtonText: 'Entendido' });
            },
            error: function (xhr) {
                const response = xhr.responseJSON || {};
                if (xhr.status === 422 && response.errors) {
                    showErrors(response.errors);
                    return;
                }
                window.RSUValidation?.error(response.message || 'No se pudo guardar el mantenimiento.');
            }
        });
    });

    $(document).on('click', '.btnEliminarMantenimiento', function () {
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Eliminar mantenimiento?',
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
                headers: { Accept: 'application/json' },
                success: function (response) {
                    table.ajax.reload(null, false);
                    Swal.fire({ title: 'Mantenimiento eliminado', icon: 'success', text: response.message, confirmButtonText: 'Entendido' });
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};
                    window.RSUValidation?.error(response.message || 'No se pudo eliminar el mantenimiento.');
                }
            });
        });
    });
});
</script>
@stop
