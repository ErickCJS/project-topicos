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
        <div class="card shadow-sm mb-3 attendance-filter-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h6 class="mb-1">Filtrar asistencias</h6>
                        <small class="text-muted">Selecciona un periodo y refina los resultados por turno, estado u origen.</small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Periodos rápidos" id="attendance-quick-filters">
                        <button type="button" class="btn btn-outline-primary active" data-period="today" aria-pressed="true">Hoy</button>
                        <button type="button" class="btn btn-outline-primary" data-period="last7" aria-pressed="false">Últimos 7 días</button>
                        <button type="button" class="btn btn-outline-primary" data-period="month" aria-pressed="false">Este mes</button>
                    </div>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-6 col-lg-2">
                        <label for="attendance-date-from" class="form-label small fw-semibold mb-1">Desde</label>
                        <input type="date" id="attendance-date-from" class="form-control form-control-sm"
                            value="{{ today()->toDateString() }}">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="attendance-date-to" class="form-label small fw-semibold mb-1">Hasta</label>
                        <input type="date" id="attendance-date-to" class="form-control form-control-sm"
                            value="{{ today()->toDateString() }}">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="attendance-filter-shift" class="form-label small fw-semibold mb-1">Turno</label>
                        <select id="attendance-filter-shift" class="form-select form-select-sm">
                            <option value="">Todos los turnos</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="attendance-filter-status" class="form-label small fw-semibold mb-1">Estado</label>
                        <select id="attendance-filter-status" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            @foreach(\App\Models\admin\Attendance::STATUSES as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="attendance-filter-origin" class="form-label small fw-semibold mb-1">Origen</label>
                        <select id="attendance-filter-origin" class="form-select form-select-sm">
                            <option value="">Todos los orígenes</option>
                            @foreach(\App\Models\admin\Attendance::ORIGINS as $origin)
                                <option value="{{ $origin }}">{{ $origin }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="attendance-apply-filters">
                            <i class="bi bi-funnel me-1"></i>Aplicar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="attendance-clear-filters"
                            title="Restablecer filtros" aria-label="Restablecer filtros">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                    <thead class="table-light">
                        <tr>
                            <th>DNI</th>
                            <th>Trabajador</th>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                            <th>Origen</th>
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
        ajax: {
            url: "{{ route('attendances.index', [], false) }}",
            data: function (data) {
                data.date_from = $('#attendance-date-from').val();
                data.date_to = $('#attendance-date-to').val();
                data.shift_id = $('#attendance-filter-shift').val();
                data.status = $('#attendance-filter-status').val();
                data.origin = $('#attendance-filter-origin').val();
            }
        },
        order: [[2, 'desc'], [4, 'desc']],
        columns: [
            { data: 'dni', name: 'dni' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'date_display', name: 'date_display', searchable: false },
            { data: 'shift_display', name: 'shift_display', searchable: false },
            { data: 'entry_display', name: 'entry_time', orderable: false, searchable: false },
            { data: 'exit_display', name: 'exit_time', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', searchable: false },
            { data: 'origin_badge', name: 'origin', searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar trabajador:',
            searchPlaceholder: 'Nombre o DNI...',
            lengthMenu: 'Mostrar _MENU_ jornadas',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ jornadas',
            infoEmpty: 'Mostrando 0 a 0 de 0 jornadas',
            infoFiltered: '(filtrado de _MAX_ jornadas)',
            loadingRecords: 'Cargando asistencias...',
            processing: 'Procesando...',
            zeroRecords: 'No se encontraron asistencias para los filtros seleccionados',
            emptyTable: 'No hay asistencias en el periodo seleccionado',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        },
        initComplete: function () {
            $('#DataTable_filter input')
                .attr('placeholder', 'Nombre o DNI...')
                .addClass('form-control form-control-sm');
        }
    });

    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function applyDateRange(from, to, activeButton) {
        $('#attendance-date-from').val(formatDateForInput(from));
        $('#attendance-date-to').val(formatDateForInput(to));
        $('#attendance-quick-filters [data-period]')
            .removeClass('active')
            .attr('aria-pressed', 'false');

        if (activeButton) {
            $(activeButton).addClass('active').attr('aria-pressed', 'true');
        }

        table.ajax.reload();
    }

    $('#attendance-quick-filters').on('click', '[data-period]', function () {
        const today = new Date();
        const period = $(this).data('period');
        const from = new Date(today.getFullYear(), today.getMonth(), today.getDate());

        if (period === 'last7') {
            from.setDate(from.getDate() - 6);
        } else if (period === 'month') {
            from.setDate(1);
        }

        applyDateRange(from, today, this);
    });

    $('#attendance-apply-filters').on('click', function () {
        const from = $('#attendance-date-from').val();
        const to = $('#attendance-date-to').val();

        if (from && to && from > to) {
            window.RSUValidation.error('La fecha inicial no puede ser posterior a la fecha final.');
            return;
        }

        $('#attendance-quick-filters [data-period]')
            .removeClass('active')
            .attr('aria-pressed', 'false');
        table.ajax.reload();
    });

    $('#attendance-clear-filters').on('click', function () {
        const today = new Date();
        $('#attendance-filter-shift, #attendance-filter-status, #attendance-filter-origin').val('');
        table.search('');
        $('#DataTable_filter input').val('');
        applyDateRange(today, today, $('#attendance-quick-filters [data-period="today"]'));
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
                    line.textContent = [row.time, row.type, row.status, row.shift || 'Sin turno', row.origin || 'No programado'].join(' · ');
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

    $('#formModal').on('change', '#attendance_date', function () {
        const employeeSelect = $('#formModal #attendance_employee_id');
        const selectedEmployee = employeeSelect.val();
        const date = $(this).val();
        $.get("{{ route('attendances.create') }}", { date: date }, function (response) {
            const options = $(response).find('#attendance_employee_id option');
            const validSelection = options.filter(function () { return this.value === selectedEmployee; }).length > 0;
            employeeSelect.empty().append(options.clone());
            employeeSelect.val(validSelection ? selectedEmployee : '').trigger('change.select2');
            cargarHistorial();
        }).fail(function (error) {
            mostrar_error(error, 'No se pudo actualizar la lista de empleados.');
        });
    });

    $('#formModal').on('change', '#attendance_employee_id', cargarHistorial);

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
