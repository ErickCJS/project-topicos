<div class="modal fade" id="programmingFormModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="programmingFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header">
            <div class="d-flex align-items-center gap-2"><span class="programming-modal-icon"><i class="bi bi-calendar-plus" aria-hidden="true"></i></span><h5 class="modal-title" id="programmingFormTitle">Nueva programación</h5></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form id="programmingForm">
            @csrf
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4"><label class="form-label" for="programStart">Fecha de inicio *</label><input type="date" class="form-control" id="programStart" required></div>
                    <div class="col-12 col-md-4"><label class="form-label" for="programEnd">Fecha de fin *</label><input type="date" class="form-control" id="programEnd" required></div>
                    <div class="col-12 col-md-4 d-flex align-items-end"><button type="button" class="btn btn-outline-success w-100" id="validateProgramming"><i class="bi bi-check2 me-1"></i>Validar disponibilidad</button></div>
                    <div class="col-12"><label class="form-label" for="programGroup">Grupo de personal *</label><select class="form-select" id="programGroup" required><option value="">Seleccione un grupo</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }} · {{ $group->zone->name }} · {{ $group->shift->name }}</option>@endforeach</select><div class="form-text">Busque por nombre, zona o turno.</div></div>
                </div>
                <div id="programValidation" class="alert alert-info mt-3 mb-0" role="status" aria-live="polite">Seleccione un grupo y valide la disponibilidad antes de guardar.</div>
                <div id="programGroupSummary" class="programming-summary mt-3" hidden></div>
                <div class="row g-3 mt-0">
                    <div class="col-12 col-md-4"><label class="form-label" for="programDriver">Conductor *</label><select class="form-select" id="programDriver" required><option value="">Seleccione un conductor</option>@foreach($employees->where('employee_type_id', 1) as $employee)<option value="{{ $employee->id }}">{{ $employee->dni }} — {{ $employee->first_name }} {{ $employee->last_name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-8"><div id="programHelpers" class="row g-3"></div><div class="form-text">Las plazas de ayudantes dependen de la capacidad del vehículo. Puede dejar plazas opcionales sin asignar.</div></div>
                    <fieldset class="col-12"><legend class="form-label">Días de trabajo *</legend><div class="d-flex flex-wrap gap-3">
                        @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $day)
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="workdays" value="{{ $loop->iteration }}" id="programDay{{ $loop->index }}"><label class="form-check-label" for="programDay{{ $loop->index }}">{{ $day }}</label></div>
                        @endforeach
                    </div></fieldset>
                    <div class="col-12"><label class="form-label" for="programNotes">Observaciones</label><textarea class="form-control" id="programNotes" rows="3" maxlength="1000" placeholder="Observaciones adicionales..."></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success" id="saveProgramming" disabled><i class="bi bi-floppy me-1"></i>Guardar</button></div>
        </form>
    </div></div>
</div>
<template id="programHelperTemplate"><div class="col-12 col-md-6"><label class="form-label"></label><select class="form-select programming-helper-select"><option value="">Sin asignar</option>@foreach($employees->where('employee_type_id', '!=', 1) as $employee)<option value="{{ $employee->id }}">{{ $employee->dni }} — {{ $employee->first_name }} {{ $employee->last_name }}</option>@endforeach</select></div></template>
