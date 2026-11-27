<div class="modal-header">
    <div class="shift-modal-heading">
        <span class="shift-modal-icon"><i class="bi bi-clock" aria-hidden="true"></i></span>
        <h5 class="modal-title mb-0" id="shiftShowModalLabel">Detalle del turno</h5>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body">
    <div class="row g-3">
        <div class="col-12">
            <div>
                <span class="shift-detail-label">Nombre</span>
                <span class="shift-detail-value">{{ $shift->name }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div>
                <span class="shift-detail-label">Hora de inicio</span>
                <span class="shift-time text-success-emphasis bg-success-subtle">{{ substr((string) $shift->start_time, 0, 5) }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div>
                <span class="shift-detail-label">Hora de término</span>
                <span class="shift-time text-secondary-emphasis bg-secondary-subtle">{{ substr((string) $shift->end_time, 0, 5) }}</span>
                @if(substr((string) $shift->end_time, 0, 5) < substr((string) $shift->start_time, 0, 5))
                    <small class="shift-next-day">Finaliza al día siguiente</small>
                @endif
            </div>
        </div>
        <div class="col-12"><span class="shift-detail-label">Descripción</span><div class="shift-detail-value">{{ $shift->description ?: 'Sin descripción' }}</div></div>
        <div class="col-12 col-md-6"><span class="shift-detail-label">Fecha de creación</span><span class="shift-detail-value">{{ $shift->created_at?->format('d/m/Y H:i') }}</span></div>
        <div class="col-12 col-md-6"><span class="shift-detail-label">Última actualización</span><span class="shift-detail-value">{{ $shift->updated_at?->format('d/m/Y H:i') }}</span></div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
