@extends('adminlte::page')

@section('title', 'Asistencias | RSUProject')

@section('content')
    <div class="attendances-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0">Listado de Asistencias</h4>
            <a href="{{ route('employee.login') }}" target="_blank" rel="noopener" class="btn btn-outline-primary ms-auto">
                <i class="bi bi-box-arrow-up-right me-1"></i>Marcación de asistencia
            </a>
            <button type="button" class="btn btn-success" id="btnNuevo">
                <i class="bi bi-plus-circle me-1"></i>Agregar nueva asistencia
            </button>
        </div>
        <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Empleado</th>
                            <th>Fecha y hora</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Turno</th>
                            <th>Notas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header attendance-modal-header">
                    <div class="attendance-modal-heading">
                        <span class="attendance-modal-icon" aria-hidden="true">
                            <i class="bi bi-calendar2-check"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0" id="formModalLabel">Asistencia</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body attendance-modal-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="attendanceShowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" id="attendanceShowContent"></div>
        </div>
    </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('attendanceShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);

    function mostrar_modal(titulo, contenido) {
        $('#formModalLabel').text(titulo);
        $('#formModal .modal-body').html(contenido);
        formModal.show();
    }

    function mostrar_alerta(esExito, mensaje) {
        if (!esExito) return window.RSUValidation.error(mensaje);
        return Swal.fire({
            title: 'Proceso exitoso',
            icon: 'success',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mostrar_error(xhr, mensajePredeterminado) {
        const response = xhr.responseJSON || {};
        if (xhr.status === 422 && response.errors) {
            window.RSUValidation.show(response.errors);
            return;
        }
        mostrar_alerta(false, response.mensaje || response.message || mensajePredeterminado);
    }

    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: "{{ route('attendances.index', [], false) }}",
        order: [[2, 'desc']],
        columns: [
            { data: 'dni', name: 'employees.dni' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'date_time', name: 'attendances.date', searchable: false },
            { data: 'type', name: 'attendances.type' },
            { data: 'status', name: 'attendances.status' },
            { data: 'shift_name', name: 'shifts.name' },
            { data: 'notes', name: 'attendances.notes', defaultContent: '' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron asistencias',
            emptyTable: 'Aún no hay asistencias registradas',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refresTable() {
        table.ajax.reload(null, false);
    }

    function cargarHistorial() {
        const employeeId = $('#formModal #attendance_employee_id').val();
        const date = $('#formModal #attendance_date').val();
        const history = document.querySelector('#formModal #attendance-day-history');

        if (!history) return;
        if (!employeeId || !date) {
            history.textContent = 'Seleccione un empleado y una fecha para consultar sus marcaciones.';
            return;
        }

        history.textContent = 'Cargando historial...';
        $.get("{{ route('attendances.history') }}", { employee_id: employeeId, date: date })
            .done(function (rows) {
                history.textContent = '';
                if (!rows.length) {
                    history.textContent = 'Sin marcaciones en esta fecha.';
                    return;
                }
                rows.forEach(function (row) {
                    const line = document.createElement('div');
                    line.textContent = [row.time, row.type, row.status, row.shift || 'Sin turno'].join(' · ');
                    history.appendChild(line);
                });
            })
            .fail(function () {
                history.textContent = 'No se pudo cargar el historial.';
            });
    }

    function inicializarEmpleado() {
        const employeeSelect = $('#formModal select[name="employee_id"]');
        if (!employeeSelect.length || employeeSelect.hasClass('select2-hidden-accessible')) return;
        employeeSelect.select2({
            width: '100%',
            dropdownParent: $('#formModal'),
            placeholder: 'Buscar por DNI, nombres o apellidos',
            language: {
                noResults: function () { return 'No se encontraron empleados'; },
                searching: function () { return 'Buscando...'; }
            }
        });
    }

    $('#btnNuevo').on('click', function () {
        $.get("{{ route('attendances.create') }}", function (response) {
            mostrar_modal('Nueva Asistencia', response);
            inicializarEmpleado();
            cargarHistorial();
        }).fail(function (error) {
            mostrar_error(error, 'No se pudo cargar el formulario de asistencia.');
        });
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#attendanceShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrar_error(error, 'No se pudo cargar la asistencia.');
        });
    });

    $(document).on('click', '.btnEditar', function () {
        $.get($(this).data('url'), function (response) {
            mostrar_modal('Editar Asistencia', response);
            inicializarEmpleado();
            cargarHistorial();
        }).fail(function (error) {
            mostrar_error(error, 'No se pudo cargar el formulario de asistencia.');
        });
    });

    $('#formModal').on('change', '#attendance_employee_id, #attendance_date', cargarHistorial);

    $(document).on('submit', '#formModal form', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('[type="submit"]').prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                refresTable();
                mostrar_alerta(true, response.mensaje || 'Asistencia guardada correctamente.');
            },
            error: function (error) {
                mostrar_error(error, 'No se pudo guardar la asistencia. Verifique los datos.');
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar esta asistencia?',
            text: 'Esta acción no se puede revertir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#b54747'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    Accept: 'application/json'
                },
                success: function (response) {
                    refresTable();
                    mostrar_alerta(true, response.mensaje || 'Asistencia eliminada correctamente.');
                },
                error: function (error) {
                    mostrar_error(error, 'No se pudo eliminar la asistencia.');
                }
            });
        });
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#attendanceShowContent').empty();
    });
});
</script>
@stop
