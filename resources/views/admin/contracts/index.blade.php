@extends('adminlte::page')

@section('title', 'RSUProject')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link href="{{ asset('css/vehicle-select2.css') }}" rel="stylesheet">
@stop

@section('content')
    <div class="card">
        <div class="card-header d-flex">
            <div class="w-50"><h4>Listado de Contratos</h4></div>
            <div class="w-50 d-flex justify-content-end">
                <button type="button" class="btn btn-success" id="btnNuevo"><i class="fas fa-plus-circle"></i> Nuevo Contrato</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="DataTable">
                    <thead><tr>
                        <th>DNI</th><th>Trabajador</th><th>Tipo</th><th>Inicio</th><th>Término</th><th>Fin de espera para recontratación</th><th>Sueldo (S/)</th><th>Estado</th><th>Acciones</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalLabel">Contrato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        var table;
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('formModal'));

        function mostrar_alerta(tipo, mensaje) {
            Swal.fire({ title: tipo === 0 ? 'Ocurrió un error!' : 'Proceso exitoso!', icon: tipo === 0 ? 'error' : 'success', text: mensaje });
        }

        function mostrar_error(err) {
            var response = err.responseJSON || {};
            var mensaje = 'No se pudo completar la operación. Inténtelo nuevamente.';
            if (err.status === 422 && response.errors) {
                mensaje = Object.values(response.errors).flat().join('\n');
            } else if (response.mensaje) {
                mensaje = response.mensaje;
            } else if (err.status === 404) {
                mensaje = 'El contrato o trabajador ya no existe.';
            } else if (err.status === 419) {
                mensaje = 'La sesión ha expirado. Recargue la página.';
            }
            mostrar_alerta(0, mensaje);
        }

        function sumar_meses(fecha, meses) {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return '';
            var partes = fecha.split('-').map(Number);
            var destino = new Date(Date.UTC(partes[0], partes[1] - 1 + meses, 1));
            var ultimoDia = new Date(Date.UTC(destino.getUTCFullYear(), destino.getUTCMonth() + 1, 0)).getUTCDate();
            destino.setUTCDate(Math.min(partes[2], ultimoDia));
            return destino.toISOString().slice(0, 10);
        }

        function actualizar_fechas() {
            var temporal = $('#contract_type').val() === 'Temporal';
            $('#contract_end_group').toggle(temporal);
            $('#contract_end_date').prop('required', temporal).prop('disabled', !temporal);
            if (!temporal) $('#contract_end_date').val('');
            $('#contract_end_date').attr('min', $('#formModal [name="start_date"]').val() || '');
            $('#contract_end_date').attr('max', temporal ? sumar_meses($('#formModal [name="start_date"]').val(), 3) : '');
            $('#contract_next_hiring_group').toggle(temporal);
            $('#contract_next_hiring').val(temporal ? sumar_meses($('#contract_end_date').val(), 2) : '');
        }

        function mostrar_modal(titulo, response) {
            $('#formModal select.select2-hidden-accessible').select2('destroy');
            $('#formModalLabel').text(titulo);
            $('#formModal .modal-body').html(response);
            actualizar_fechas();
            modal.show();
            $('#formModal select[name="employee_id"], #formModal select[name="employee_display"]').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#formModal'),
                placeholder: 'Seleccione un trabajador',
                language: {
                    noResults: function () { return 'No se encontraron trabajadores'; },
                    searching: function () { return 'Buscando...'; }
                }
            });
        }

        $('#formModal').on('change input', '#contract_type, [name="start_date"], #contract_end_date', actualizar_fechas);
        $('#btnNuevo').on('click', function () {
            $.ajax({ url: "{{ route('contracts.create') }}", type: 'GET',
                success: function (response) { mostrar_modal('Nuevo Contrato', response); }, error: mostrar_error });
        });
        $('#DataTable').on('click', '.btnEditar', function () {
            $.ajax({ url: "{{ route('contracts.edit', ':id') }}".replace(':id', $(this).data('id')), type: 'GET',
                success: function (response) { mostrar_modal('Editar Contrato', response); }, error: mostrar_error });
        });
        $('#DataTable').on('click', '.btnAnular', function () {
            var button = $(this);
            Swal.fire({
                title: '¿Está seguro de anular el contrato?',
                text: 'Se cerrará con la fecha de hoy. Se conservará el historial y no podrá editarse ni reactivarse.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', confirmButtonText: 'Sí, anular', cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                button.prop('disabled', true);
                $.ajax({
                    url: "{{ route('contracts.cancel', ':id') }}".replace(':id', button.data('id')),
                    type: 'PATCH',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function (response) { table.ajax.reload(null, false); mostrar_alerta(1, response.mensaje); },
                    error: mostrar_error,
                    complete: function () { button.prop('disabled', false); }
                });
            });
        });
        $('#formModal').on('submit', 'form', function (e) {
            e.preventDefault();
            var form = $(this);
            var buttons = form.find('button[type="submit"]').prop('disabled', true);
            $.ajax({
                url: form.attr('action'), type: form.attr('method'), data: new FormData(this),
                processData: false, contentType: false, headers: { 'Accept': 'application/json' },
                success: function (response) { modal.hide(); table.ajax.reload(null, false); mostrar_alerta(1, response.mensaje); },
                error: mostrar_error, complete: function () { buttons.prop('disabled', false); }
            });
        });

        table = $('#DataTable').DataTable({
            processing: true, serverSide: true, ajax: "{{ route('contracts.index') }}",
            columns: [
                { data: 'dni', name: 'employees.dni' },
                { data: 'employee', name: 'employees.last_name' },
                { data: 'type', name: 'contracts.type' },
                { data: 'start_date', name: 'contracts.start_date' },
                { data: 'end_date', name: 'contracts.end_date' },
                { data: 'next_hiring_date', orderable: false, searchable: false },
                { data: 'salary', name: 'contracts.salary' },
                { data: 'status', orderable: false, searchable: false },
                { data: 'edit', orderable: false, searchable: false }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json' }
        });
    });
</script>
@stop
