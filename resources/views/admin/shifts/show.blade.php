<div class="modal-header">
    <div>
        <h5 class="modal-title mb-0">Detalle del turno</h5>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body">
    <div class="row g-3">
        <div class="col-12">
            <div class="card card-body">
                <small class="text-muted">Nombre</small>
                <span class="fw-semibold text-break">{{ $shift->name }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card card-body">
                <small class="text-muted">Hora de inicio</small>
                <span class="fw-semibold">{{ substr((string) $shift->start_time, 0, 5) }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card card-body">
                <small class="text-muted">Hora de fin</small>
                <span class="fw-semibold">{{ substr((string) $shift->end_time, 0, 5) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
