
<div class="modal-header users-modal-header">
    <div class="users-modal-heading">
        <span class="users-modal-icon">
            <i class="bi bi-tags" aria-hidden="true"></i>
        </span>

        <div>
            <h5 class="modal-title mb-0">Tipos de usuario</h5>
            <small class="text-muted">
Consulta, edita las clasificaciones y configura sus permisos predeterminados.
            </small>
        </div>
    </div>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="modal"
        aria-label="Cerrar">
    </button>
</div>

<div class="modal-body">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h6 class="mb-1">Listado de tipos</h6>
            <small class="text-muted">
                Los permisos de cada tipo se aplican como plantilla al crear o reasignar usuarios.
            </small>
        </div>
    </div>

    <div class="table-responsive">
        <table
            class="table table-striped table-hover align-middle w-100"
            id="userTypesTable">

            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Permisos</th>
                    <th>Fecha de creación</th>
                    <th>Última actualización</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal-footer users-modal-footer">
    <button
        type="button"
        class="btn btn-light border"
        data-bs-dismiss="modal">
        Cerrar
    </button>
</div>
