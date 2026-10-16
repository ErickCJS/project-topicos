<div class="modal fade color-modal" id="modalCrearColor" data-bs-backdrop="static" data-bs-keyboard="false"
    tabindex="-1" aria-labelledby="modalCrearColorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form id="formCrearColor" class="modal-content" novalidate>
            @csrf
            <div class="modal-header color-modal-header">
                <div class="color-modal-heading">
                    <span class="color-modal-icon" aria-hidden="true"><i class="bi bi-palette"></i></span>
                    <div>
                        <h5 class="modal-title mb-0" id="modalCrearColorLabel">Nuevo Color</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body color-modal-body">
                <div class="color-form-intro">
                    <p class="small mb-0">Puede escribir el código hexadecimal o elegirlo desde la paleta.</p>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="crearName" class="form-label fw-semibold">Nombre del color</label>
                        <input class="form-control" id="crearName" name="name" required maxlength="100"
                            autocomplete="off" placeholder="Ej. Azul">
                        <div class="invalid-feedback color-field-error" data-error-for="name"></div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="crearCode" class="form-label fw-semibold">Código hexadecimal</label>
                        <div class="input-group">
                            <input class="form-control color-code-input" id="crearCode" name="code" required
                                maxlength="7" autocomplete="off" placeholder="#104CAD" value="#000000">
                            <input class="form-control form-control-color color-picker" id="crearPicker" type="color"
                                value="#000000" title="Seleccionar color" aria-label="Seleccionar color">
                        </div>
                        <div class="invalid-feedback color-field-error" data-error-for="code"></div>
                        <div class="form-text">Formatos aceptados: #RGB y #RRGGBB. El prefijo # se agrega automáticamente.</div>
                    </div>

                    <div class="col-12">
                        <label for="crearDescription" class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" id="crearDescription" name="description" rows="3" maxlength="100"
                            placeholder="Agregue una descripción del color"></textarea>
                        <div class="invalid-feedback color-field-error" data-error-for="description"></div>
                        <div class="d-flex justify-content-end color-counter">
                            <small><span data-counter-for="crearDescription">0</span>/100</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold mb-2">Vista previa del color</label>
                        <div class="color-preview" id="crearPreview" role="img" aria-label="Vista previa #000000">#000000</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer color-modal-footer">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-floppy me-1" aria-hidden="true"></i>Guardar
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade color-modal" id="modalEditarColor" data-bs-backdrop="static" data-bs-keyboard="false"
    tabindex="-1" aria-labelledby="modalEditarColorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form id="formEditarColor" class="modal-content" novalidate>
            @csrf
            <div class="modal-header color-modal-header">
                <div class="color-modal-heading">
                    <span class="color-modal-icon" aria-hidden="true"><i class="bi bi-palette"></i></span>
                    <div>
                        <h5 class="modal-title mb-0" id="modalEditarColorLabel">Editar Color</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body color-modal-body">
                <input type="hidden" name="id" id="editarId">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="editarName" class="form-label fw-semibold">Nombre del color</label>
                        <input class="form-control" id="editarName" name="name" required maxlength="100" autocomplete="off">
                        <div class="invalid-feedback color-field-error" data-error-for="name"></div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="editarCode" class="form-label fw-semibold">Código hexadecimal</label>
                        <div class="input-group">
                            <input class="form-control color-code-input" id="editarCode" name="code" required maxlength="7"
                                autocomplete="off" placeholder="#104CAD">
                            <input class="form-control form-control-color color-picker" id="editarPicker" type="color"
                                value="#000000" title="Seleccionar color" aria-label="Seleccionar color">
                        </div>
                        <div class="invalid-feedback color-field-error" data-error-for="code"></div>
                        <div class="form-text">Formatos aceptados: #RGB y #RRGGBB. El prefijo # se agrega automáticamente.</div>
                    </div>

                    <div class="col-12">
                        <label for="editarDescription" class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" id="editarDescription" name="description" rows="3" maxlength="100"
                            placeholder="Agregue una descripción del color"></textarea>
                        <div class="invalid-feedback color-field-error" data-error-for="description"></div>
                        <div class="d-flex justify-content-end color-counter">
                            <small><span data-counter-for="editarDescription">0</span>/100</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold mb-2">Vista previa del color</label>
                        <div class="color-preview" id="editarPreview" role="img" aria-label="Vista previa del color">#000000</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer color-modal-footer">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-floppy me-1" aria-hidden="true"></i>Guardar cambios
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade color-modal" id="modalVerColor" tabindex="-1" aria-labelledby="modalVerColorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header color-modal-header">
                <div class="color-modal-heading">
                    <span class="color-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
                    <div>
                        <h5 class="modal-title mb-0" id="modalVerColorLabel">Detalle de color</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body color-modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="color-detail-card">
                            <small class="color-detail-label">Nombre</small>
                            <span class="color-detail-value text-break" id="verColorName"></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="color-detail-card">
                            <small class="color-detail-label">Código hexadecimal</small>
                            <span class="color-detail-value" id="verColorCode"></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="color-detail-card">
                            <small class="color-detail-label">Descripción</small>
                            <div class="color-detail-description text-break" id="verColorDescription"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="color-detail-card">
                            <small class="color-detail-label">Vista previa</small>
                            <div class="color-detail-preview" id="verColorPreview" role="img" aria-label="Vista previa del color"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="color-detail-card">
                            <small class="color-detail-label">Creación</small>
                            <span class="color-detail-value" id="verColorCreatedAt"></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="color-detail-card">
                            <small class="color-detail-label">Última actualización</small>
                            <span class="color-detail-value" id="verColorUpdatedAt"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer color-modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
