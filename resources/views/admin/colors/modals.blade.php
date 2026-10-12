{{-- Modal crear color --}}
<div class="modal fade color-modal" id="modalCrearColor" tabindex="-1" aria-labelledby="modalCrearColorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formCrearColor" class="modal-content" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearColorLabel">Nuevo Color</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="crearName" class="form-label">Nombre del Color</label>
                    <input class="form-control" id="crearName" name="name" required maxlength="100" autocomplete="off" placeholder="Ej. Azul">
                </div>
                <div class="mb-3">
                    <label for="crearCode" class="form-label">Código del Color (Hexadecimal)</label>
                    <div class="input-group">
                        <input class="form-control color-code-input" id="crearCode" name="code" required maxlength="7" autocomplete="off" placeholder="#104CAD" value="#000000">
                        <input class="form-control form-control-color color-picker" id="crearPicker" type="color" value="#000000" title="Seleccionar color" aria-label="Seleccionar color">
                    </div>
                    <div class="form-text">Ingresa un código #RGB o #RRGGBB, o selecciónalo en la paleta.</div>
                </div>
                <div class="mb-3">
                    <label for="crearDescription" class="form-label">Descripción</label>
                    <textarea class="form-control" id="crearDescription" name="description" rows="3" maxlength="100" placeholder="Agrega una descripción del color"></textarea>
                    <div class="d-flex justify-content-end"><small class="text-muted"><span data-counter-for="crearDescription">0</span>/100</small></div>
                </div>
                <label class="form-label mb-2">Vista Previa del Color</label>
                <div class="color-preview" id="crearPreview">#000000</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times-circle"></i> Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal editar color --}}
<div class="modal fade color-modal" id="modalEditarColor" tabindex="-1" aria-labelledby="modalEditarColorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditarColor" class="modal-content" novalidate>
            <div class="modal-header">
            <h5 class="modal-title" id="modalEditarColorLabel">Editar Color</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="editarId">
                <div class="mb-3">
                    <label for="editarName" class="form-label">Nombre del Color</label>
                    <input class="form-control" id="editarName" name="name" required maxlength="100" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label for="editarCode" class="form-label">Código del Color (Hexadecimal)</label>
                    <div class="input-group">
                        <input class="form-control color-code-input" id="editarCode" name="code" required maxlength="7" autocomplete="off" placeholder="#104CAD">
                        <input class="form-control form-control-color color-picker" id="editarPicker" type="color" value="#000000" title="Seleccionar color" aria-label="Seleccionar color">
                    </div>
                    <div class="form-text">Si falta el # inicial, se agrega al guardar. Formatos aceptados: #RGB o #RRGGBB.</div>
                </div>
                <div class="mb-3">
                    <label for="editarDescription" class="form-label">Descripción</label>
                    <textarea class="form-control" id="editarDescription" name="description" rows="3" maxlength="100" placeholder="Agrega una descripción del color"></textarea>
                    <div class="d-flex justify-content-end"><small class="text-muted"><span data-counter-for="editarDescription">0</span>/100</small></div>
                </div>
                <label class="form-label mb-2">Vista Previa del Color</label>
                <div class="color-preview" id="editarPreview">#000000</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times-circle"></i> Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
