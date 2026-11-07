@extends('adminlte::page')

@section('title', 'Cambios | RSU')
@section('plugins.DatatablesButtons', true)

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/programming.css') }}">
@include('admin.programming.partials.bulk-modifications-modal', ['embedded' => true])
@stop

@section('content')
<div class="change-management-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0"><i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i>Cambios</h4>
        <button type="button" class="btn btn-success" id="bulkModification"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Nuevo cambio masivo</button>
    </div>
    <div class="card mb-3"><div class="card-body">
        <form id="changeFilters" class="row g-3 align-items-end" novalidate>
            <div class="col-12 col-md-4 col-xl-2"><label for="changeFilterStart" class="form-label">Fecha de inicio</label><input type="date" class="form-control" id="changeFilterStart"></div>
            <div class="col-12 col-md-4 col-xl-2"><label for="changeFilterEnd" class="form-label">Fecha de fin</label><input type="date" class="form-control" id="changeFilterEnd"></div>
            <div class="col-12 col-md-4 col-xl-3"><label for="changeFilterType" class="form-label">Tipo de cambio</label><select class="form-select" id="changeFilterType"><option value="">Todos</option><option value="bulk">Cambio masivo</option><option value="shift">Turno</option><option value="driver">Conductor</option><option value="helper">Ayudante</option><option value="vehicle">Vehículo</option><option value="rescheduling">Reprogramación</option></select></div>
            <div class="col-12 col-xl-2"><button type="reset" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg me-1"></i>Limpiar</button></div>
            <div class="col-12 small text-danger d-none" id="changeFilterError" role="alert"></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="changesTable" class="table table-striped table-hover align-middle w-100">
            <thead><tr><th>Identificador</th><th>Tipo de cambio</th><th>Fecha del cambio</th><th>Valores anteriores</th><th>Valores nuevos</th><th>Usuario</th><th>Programación</th><th>Motivo</th><th class="text-center">Acciones</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div></div>
</div>

    <div class="modal fade" id="changeDetailModal" tabindex="-1"
        aria-labelledby="changeDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content" id="changeDetailContent">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0">Cargando detalle...</p>
                </div>
            </div>
        </div>
    </div>@stop

@section('js')
<script>
$(function () {
    const form = $('#changeFilters');
    const errorBox = $('#changeFilterError');
    const validRange = () => {
        const start = $('#changeFilterStart').val();
        const end = $('#changeFilterEnd').val();
        if (start && end && end < start) {
            errorBox.text('La fecha final no puede ser anterior a la fecha inicial.').removeClass('d-none');
            return false;
        }
        errorBox.addClass('d-none').text('');
        return true;
    };
    const table = $('#changesTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: {
            url: "{{ route('cambios.index', [], false) }}",
            data: data => { data.filter_start = $('#changeFilterStart').val(); data.filter_end = $('#changeFilterEnd').val(); data.change_type = $('#changeFilterType').val(); },
            error: xhr => {
                const message = xhr.responseJSON?.errors?.filter_end?.[0] || xhr.responseJSON?.message || 'No se pudo consultar el historial.';
                errorBox.text(message).removeClass('d-none');
            }
        },
        order: [[2, 'desc']],
        columns: [
            { data: 'id', name: 'programming_changes.id' }, { data: 'change_type', name: 'action' }, { data: 'performed_at', name: 'created_at' },
            { data: 'before_summary', name: 'before', orderable: false, searchable: false }, { data: 'after_summary', name: 'after', orderable: false, searchable: false },
            { data: 'performed_by', name: 'performed_by', orderable: false, searchable: false }, { data: 'programming_label', name: 'programming_label', orderable: false, searchable: false },
            { data: 'reason_label', name: 'reason' }, { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: { search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros', info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Mostrando 0 a 0 de 0 registros', infoFiltered: '(filtrado de _MAX_ registros)', zeroRecords: 'No se encontraron cambios', emptyTable: 'Aún no hay cambios registrados', paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' } }
    });
    form.on('change', 'input, select', () => { if (validRange()) table.ajax.reload(); });
    form.on('reset', () => setTimeout(() => { validRange(); table.ajax.reload(); }, 0));
});

    // Detalle del cambio mediante modal AJAX
    const detailModalElement = document.getElementById('changeDetailModal');
    const detailModal = bootstrap.Modal.getOrCreateInstance(detailModalElement);
    const detailContent = $('#changeDetailContent');

    $('#changesTable').on('click', '.btn-ver-cambio', function () {
        const url = $(this).data('url');

        detailContent.html(`
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Cargando detalle...</p>
            </div>
        `);

        detailModal.show();

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            success: function (html) {
                detailContent.html(html);
            },
            error: function () {
                detailContent.html(`
                    <div class="modal-header">
                        <h5 class="modal-title">Detalle del cambio</h5>
                        <button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            No se pudo cargar el detalle del cambio.
                        </div>
                    </div>
                `);
            }
        });
    });
</script>
<script>
document.getElementById('bulkModificationModal')?.setAttribute(
    'data-endpoint-base',
    @json(url('/admin/cambios/masivos'))
);
document.getElementById('bulkModificationModal')?.setAttribute(
    'data-reload-url',
    @json(route('cambios.index'))
);
window.programmingBulkReasons = @json($reasons);
</script>
<script src="{{ asset('js/bulk-programming-modifications.js') }}"></script>
@stop
