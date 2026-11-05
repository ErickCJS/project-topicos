@extends('adminlte::page')

@section('title', 'Cambios de programación | RSU')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0"><i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i>Cambios de programación</h4>
        <button type="button" class="btn btn-success" id="newProgrammingChange">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo cambio masivo
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form id="changeFilters" method="GET" action="{{ route('programming-changes.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label" for="startDate">Fecha de inicio</label>
                    <input class="form-control" type="date" id="startDate" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                </div>
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label" for="endDate">Fecha de fin</label>
                    <input class="form-control @error('end_date') is-invalid @enderror" type="date" id="endDate" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label" for="changeAction">Tipo de cambio</label>
                    <select class="form-select" id="changeAction" name="action">
                        <option value="">Todos los tipos</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-xl-3 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar</button>
                    <a class="btn btn-outline-secondary" href="{{ route('programming-changes.index') }}" aria-label="Limpiar filtros" title="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="programmingChangesTable" class="table table-striped table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col">Fecha del cambio</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">N.º programación</th>
                            <th scope="col">Fecha programada</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Usuario</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="massChangeModal" tabindex="-1" aria-labelledby="massChangeTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="massChangeTitle">Nuevo cambio masivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="massChangeForm" action="{{ route('programming-changes.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="changeStart">Fecha de inicio</label>
                            <input class="form-control" type="date" id="changeStart" name="start_date" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="changeEnd">Fecha de fin</label>
                            <input class="form-control" type="date" id="changeEnd" name="end_date" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="changeZone">Zona</label>
                            <select class="form-select" id="changeZone" name="zone_id" required>
                                <option value="">Seleccione una zona</option>
                                @foreach($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="changeType">Tipo de cambio</label>
                            <select class="form-select" id="changeType" name="type" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="shift">Cambio de turno</option>
                                <option value="driver">Cambio de conductor</option>
                                <option value="occupant">Cambio de ocupante</option>
                                <option value="vehicle">Cambio de vehículo</option>
                            </select>
                        </div>
                        <div class="col-12 d-none change-value-group" id="changeValue-shift" data-type="shift">
                            <label class="form-label" for="changeShift">Nuevo turno</label>
                            <select class="form-select change-value" id="changeShift" name="value_id" disabled>
                                <option value="">Seleccione un turno</option>
                                @foreach($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->name }} · {{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12 d-none change-value-group" id="changeValue-driver" data-type="driver">
                            <label class="form-label" for="changeDriver">Nuevo conductor</label>
                            <select class="form-select change-value" id="changeDriver" name="value_id" disabled>
                                <option value="">Seleccione un conductor</option>
                                @foreach($employees->where('employee_type_id', 1) as $employee)<option value="{{ $employee->id }}">{{ $employee->dni }} · {{ $employee->first_name }} {{ $employee->last_name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12 d-none change-value-group" id="changeValue-occupant" data-type="occupant">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="currentOccupant">Ocupante actual</label>
                                    <select class="form-select" id="currentOccupant" name="occupant_id" disabled>
                                        <option value="">Seleccione el ocupante a reemplazar</option>
                                        @foreach($employees->where('employee_type_id', '!=', 1) as $employee)<option value="{{ $employee->id }}">{{ $employee->dni }} · {{ $employee->first_name }} {{ $employee->last_name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="newOccupant">Nuevo ocupante</label>
                                    <select class="form-select change-value" id="newOccupant" name="value_id" disabled>
                                        <option value="">Seleccione el nuevo ocupante</option>
                                        @foreach($employees->where('employee_type_id', '!=', 1) as $employee)<option value="{{ $employee->id }}">{{ $employee->dni }} · {{ $employee->first_name }} {{ $employee->last_name }}</option>@endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none change-value-group" id="changeValue-vehicle" data-type="vehicle">
                            <label class="form-label" for="changeVehicle">Nuevo vehículo</label>
                            <select class="form-select change-value" id="changeVehicle" name="value_id" disabled>
                                <option value="">Seleccione un vehículo</option>
                                @foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }} · {{ $vehicle->plate }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="changeReason">Motivo</label>
                            <select class="form-select" id="changeReason" name="reason_id" required>
                                <option value="">Seleccione un motivo</option>
                                @foreach($reasons as $reason)<option value="{{ $reason->id }}">{{ $reason->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="changeReasonDetail">Detalle del motivo</label>
                            <textarea class="form-control" id="changeReasonDetail" name="reason_detail" rows="3" maxlength="1000" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="saveMassChange"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Aplicar cambio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="changeDetailModal" tabindex="-1" aria-labelledby="changeDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeDetailTitle">Detalle del cambio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-3">
                    <dt class="col-sm-4">Tipo de cambio</dt><dd class="col-sm-8" id="detailType"></dd>
                    <dt class="col-sm-4">Número de programación</dt><dd class="col-sm-8" id="detailProgramming"></dd>
                    <dt class="col-sm-4">Fecha programada</dt><dd class="col-sm-8" id="detailProgrammingDate"></dd>
                    <dt class="col-sm-4">Usuario</dt><dd class="col-sm-8" id="detailUser"></dd>
                    <dt class="col-sm-4">Fecha del cambio</dt><dd class="col-sm-8" id="detailDate"></dd>
                    <dt class="col-sm-4">Motivo</dt><dd class="col-sm-8" id="detailReason"></dd>
                    <dt class="col-sm-4">Detalle del motivo</dt><dd class="col-sm-8" id="detailReasonText"></dd>
                </dl>
                <div class="row g-3">
                    <div class="col-12 col-md-6"><h6>Valor previo</h6><div class="border rounded p-3" id="detailBefore"></div></div>
                    <div class="col-12 col-md-6"><h6>Valor nuevo</h6><div class="border rounded p-3" id="detailAfter"></div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button></div>
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
    const table = $('#programmingChangesTable').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: {
            url: @json(route('programming-changes.index')),
            data: function (request) {
                request.start_date = $('#startDate').val();
                request.end_date = $('#endDate').val();
                request.action = $('#changeAction').val();
            }
        },
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action' },
            { data: 'programming_number', name: 'programming_id', searchable: false },
            { data: 'scheduled_date', searchable: false, orderable: false },
            { data: 'reason_name', searchable: false, orderable: false },
            { data: 'user_name', searchable: false, orderable: false },
            { data: 'actions', searchable: false, orderable: false, className: 'text-end text-nowrap' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            zeroRecords: 'No se encontraron cambios',
            emptyTable: 'Aún no hay cambios registrados',
            processing: 'Cargando cambios...',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    $('#changeFilters').on('submit', function (event) {
        event.preventDefault();
        table.ajax.reload();
    });

    const massChangeModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('massChangeModal'));
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('changeDetailModal'));
    const massChangeForm = $('#massChangeForm');
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));

    function updateChangeFields() {
        const type = $('#changeType').val();
        $('.change-value-group').addClass('d-none').find('select').prop('disabled', true).prop('required', false);
        const group = $('#changeValue-' + type).removeClass('d-none');
        group.find('.change-value').prop('disabled', false).prop('required', true);
        if (type === 'occupant') $('#currentOccupant').prop('disabled', false).prop('required', true);
    }

    $('#newProgrammingChange').on('click', () => massChangeModal.show());
    $('#changeType').on('change', updateChangeFields);
    $('#currentOccupant').on('change', function () {
        $('#newOccupant option').prop('disabled', false);
        if (this.value) $('#newOccupant option[value="' + this.value + '"]').prop('disabled', true);
        if ($('#newOccupant').val() === this.value) $('#newOccupant').val('');
    });

    massChangeForm.on('submit', function (event) {
        event.preventDefault();
        const submit = $('#saveMassChange').prop('disabled', true);
        $.ajax({
            url: this.action,
            method: 'POST',
            data: massChangeForm.serialize(),
            headers: { Accept: 'application/json' }
        }).done(response => {
            massChangeModal.hide();
            massChangeForm[0].reset();
            updateChangeFields();
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Cambio aplicado', text: response.message, confirmButtonText: 'Entendido' });
        }).fail(xhr => {
            const response = xhr.responseJSON || {};
            const message = response.errors ? Object.values(response.errors).flat().join('\n') : response.message || 'No se pudo aplicar el cambio.';
            Swal.fire({ icon: 'error', title: 'No se aplicó el cambio', text: message, confirmButtonText: 'Entendido' });
        }).always(() => submit.prop('disabled', false));
    });

    function valueLabel(value) {
        if (!value) return 'Sin registro';
        const label = [value.label, value.hours, value.plate].filter(Boolean).map(escapeHtml).join(' · ');
        return label || '<pre class="mb-0 text-wrap">' + escapeHtml(JSON.stringify(value, null, 2)) + '</pre>';
    }

    $('#programmingChangesTable').on('click', '[data-action="detail"]', function () {
        $.get($(this).data('url')).done(change => {
            $('#detailType').text(change.action);
            $('#detailProgramming').text(change.programming_id);
            $('#detailProgrammingDate').text(change.programming_date);
            $('#detailUser').text(change.user);
            $('#detailDate').text(change.changed_at);
            $('#detailReason').text(change.reason);
            $('#detailReasonText').text(change.reason_detail);
            $('#detailBefore').html(valueLabel(change.before));
            $('#detailAfter').html(valueLabel(change.after));
            detailModal.show();
        }).fail(() => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el detalle.' }));
    });

    $('#programmingChangesTable').on('click', '[data-action="delete"]', function () {
        const url = $(this).data('url');
        Swal.fire({
            icon: 'warning',
            title: '¿Eliminar este registro?',
            text: 'Solo se eliminará el historial; la programación no cambiará.',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url, method: 'DELETE', data: { _token: @json(csrf_token()) }, headers: { Accept: 'application/json' } })
                .done(response => {
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Registro eliminado', text: response.message });
                })
                .fail(() => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el registro.' }));
        });
    });

    $('#massChangeModal').on('hidden.bs.modal', function () {
        massChangeForm[0].reset();
        updateChangeFields();
    });
});
</script>
@stop