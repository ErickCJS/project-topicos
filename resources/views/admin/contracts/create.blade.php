{!! Form::open(['route' => 'contracts.store', 'class' => 'contract-form']) !!}
<div class="modal-header contract-modal-header">
    <div class="contract-modal-heading">
        <span class="contract-modal-icon" aria-hidden="true"><i class="bi bi-file-earmark-plus"></i></span>
        <div>
            <h5 class="modal-title mb-0" id="contractFormModalLabel">Nuevo Contrato</h5>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body contract-modal-body">
    @include('admin.contracts.template.form')
</div>

<div class="modal-footer contract-modal-footer">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-floppy me-1" aria-hidden="true"></i>Guardar
    </button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancelar
    </button>
</div>
{!! Form::close() !!}
