<div class="modal fade" id="programmingModificationsModal" tabindex="-1" aria-labelledby="programmingModificationsTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="programmingModificationsTitle">Modificar programación</h5>
                    <div class="small text-body-secondary" id="programmingModificationsSubtitle">Cargue una programación para comenzar.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2" role="status">Cada cambio se valida, aplica y registra inmediatamente al pulsar «Aplicar cambio».</div>

                <section class="mb-4" aria-labelledby="programmingChangeSectionTitle">
                    <h6 class="programming-change-section-title" id="programmingChangeSectionTitle"><i class="bi bi-sliders me-2" aria-hidden="true"></i>Modificaciones de la programación</h6>
                    <div class="row g-4">
                        <div class="col-12 col-lg-4">
                            <article class="card programming-change-card h-100">
                                <div class="card-header"><i class="bi bi-clock me-2" aria-hidden="true"></i>Cambio de turno</div>
                                <div class="card-body">
                                    <label class="form-label" for="modCurrentShift">Turno actual</label>
                                    <input class="form-control mb-3" id="modCurrentShift" disabled>
                                    <label class="form-label" for="modNewShift">Nuevo turno</label>
                                    <select class="form-select mb-3" id="modNewShift"><option value="">Seleccione un turno</option></select>
                                    <label class="form-label" for="modShiftReason">Motivo *</label>
                                    <select class="form-select programming-mod-reason mb-2" id="modShiftReason" data-custom="#modShiftCustomReason" data-kind="shift"><option value="">Seleccione un motivo</option></select>
                                    <input class="form-control mb-3 d-none" id="modShiftCustomReason" maxlength="120" placeholder="Especifique el motivo">
                                    <label class="form-label" for="modShiftDescription">Descripción <span class="text-body-secondary">(opcional)</span></label>
                                    <textarea class="form-control mb-3" id="modShiftDescription" rows="2" maxlength="1000" placeholder="Explique las circunstancias"></textarea>
                                    <button type="button" class="btn btn-outline-primary w-100" data-add-change="shift"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aplicar cambio</button>
                                </div>
                            </article>
                        </div>
                        <div class="col-12 col-lg-4">
                            <article class="card programming-change-card h-100">
                                <div class="card-header"><i class="bi bi-truck me-2" aria-hidden="true"></i>Cambio de vehículo</div>
                                <div class="card-body">
                                    <label class="form-label" for="modCurrentVehicle">Vehículo actual</label>
                                    <input class="form-control mb-3" id="modCurrentVehicle" disabled>
                                    <label class="form-label" for="modNewVehicle">Nuevo vehículo</label>
                                    <select class="form-select mb-2" id="modNewVehicle"><option value="">Seleccione un vehículo</option></select>
                                    <small class="form-text d-block mb-3">Seleccione un solo vehículo. La lista muestra únicamente vehículos disponibles con la misma capacidad de ocupantes que el actual.</small>
                                    <div class="alert alert-warning py-2 d-none" id="modVehicleCapacityWarning" role="status"></div>
                                    <label class="form-label" for="modVehicleReason">Motivo *</label>
                                    <select class="form-select programming-mod-reason mb-2" id="modVehicleReason" data-custom="#modVehicleCustomReason" data-kind="vehicle"><option value="">Seleccione un motivo</option></select>
                                    <input class="form-control mb-3 d-none" id="modVehicleCustomReason" maxlength="120" placeholder="Especifique el motivo">
                                    <label class="form-label" for="modVehicleDescription">Descripción <span class="text-body-secondary">(opcional)</span></label>
                                    <textarea class="form-control mb-3" id="modVehicleDescription" rows="2" maxlength="1000" placeholder="Explique las circunstancias"></textarea>
                                    <button type="button" class="btn btn-outline-primary w-100" data-add-change="vehicle"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aplicar cambio</button>
                                </div>
                            </article>
                        </div>
                        <div class="col-12 col-lg-4">
                            <article class="card programming-change-card h-100">
                                <div class="card-header"><i class="bi bi-person-gear me-2" aria-hidden="true"></i>Cambio de personal</div>
                                <div class="card-body">
                                <label class="form-label" for="modPeopleRole">Tipo de personal</label>
                                    <select class="form-select mb-3" id="modPeopleRole">
                                        <option value="driver">Conductor</option>
                                        <option value="helper">Ayudante</option>
                                    </select>

                                    <div class="programming-people-row d-none" id="modPeopleDriverFields">
                                        <div class="small text-body-secondary mb-2">Persona actual: <strong id="modCurrentDriverName">Cargando…</strong></div>
                                        <label class="form-label" for="modNewDriver">Nuevo personal</label>
                                        <select class="form-select" id="modNewDriver"><option value="">Selecciona un conductor disponible</option></select>
                                    </div>

                                    <div class="programming-people-row d-none" id="modPeopleHelperReplaceFields">
                                        <label class="form-label" for="modCurrentHelper">Persona actual</label>
                                        <select class="form-select mb-2" id="modCurrentHelper"><option value="">Selecciona un ayudante actual</option></select>
                                        <label class="form-label" for="modNewHelper">Nuevo personal</label>
                                        <select class="form-select" id="modNewHelper"><option value="">Selecciona un ayudante disponible</option></select>
                                    </div>

                                    <div class="programming-people-reason">
                                        <label class="form-label" for="modPeopleReason">Motivo *</label>
                                        <select class="form-select programming-mod-reason mb-2" id="modPeopleReason" data-custom="#modPeopleCustomReason" data-kind="driver"><option value="">Selecciona un motivo</option></select>
                                        <input class="form-control mb-3 d-none" id="modPeopleCustomReason" maxlength="120" placeholder="Especifica el motivo">
                                        <label class="form-label" for="modPeopleDescription">Descripción <span class="text-body-secondary">(opcional)</span></label>
                                        <textarea class="form-control" id="modPeopleDescription" rows="2" maxlength="1000" placeholder="Agrega un detalle si hace falta"></textarea>
                                    </div>

                                    <button type="button" class="btn btn-outline-primary w-100 mt-3" id="modPeopleSubmitButton" data-add-change="driver">
                                        <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Aplicar cambio
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>

                <section aria-labelledby="programmingHistoryTitle">
                    <div class="programming-change-section-heading">
                        <h6 class="programming-change-section-title mb-0" id="programmingHistoryTitle"><i class="bi bi-clock-history me-2" aria-hidden="true"></i>Historial de modificaciones</h6>
                    </div>
                    <div class="table-responsive programming-change-table-wrap">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead><tr><th>Fecha y hora</th><th>Tipo de cambio</th><th>Valor anterior</th><th>Valor nuevo</th><th>Motivo</th><th>Usuario responsable</th><th class="text-end">Acción</th></tr></thead>
                            <tbody id="programmingModHistoryBody"><tr><td colspan="7" class="text-center text-body-secondary py-3">Cargando historial…</td></tr></tbody>
                        </table>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
