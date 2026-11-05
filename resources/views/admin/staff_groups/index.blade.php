
@extends('adminlte::page')

@section('title', 'Grupos de Personal | RSUProject')

@section('content')
<div class="staff-groups-module">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0">Listado de Grupos de Personal</h4>

        <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
            Nuevo Grupo
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="staff-groups-table-wrap">
                <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Turno</th>
                            <th>Zona</th>
                            <th>Vehículo</th>
                            <th>Conductor</th>
                            <th>Ayudantes</th>
                            <th>Días</th>
                            <th>Estado</th>
                            <th>Fecha creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal para crear y editar --}}
    <div class="modal fade"
         id="formModal"
         data-bs-backdrop="static"
         data-bs-keyboard="false"
         tabindex="-1"
         aria-labelledby="formModalLabel"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header staff-modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill fs-5" aria-hidden="true"></i>
                        <h5 class="modal-title mb-0" id="formModalLabel">
                            Nuevo Grupo de Personal
                        </h5>
                    </div>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar">
                    </button>
                </div>

                <div class="modal-body" id="formModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/staff_group.css') }}">

@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);


    /*
     * Mostrar alertas utilizando las funciones que ya tiene tu proyecto.
     */
    function mostrarAlerta(esExito, mensaje) {
        if (!esExito) {
            if (window.RSUValidation) {
                return window.RSUValidation.error(mensaje);
            }

            return Swal.fire({
                title: 'Error',
                icon: 'error',
                text: mensaje,
                confirmButtonText: 'Entendido'
            });
        }

        return Swal.fire({
            title: 'Proceso exitoso',
            icon: 'success',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mensajeError(xhr, mensajePredeterminado) {
        const response = xhr.responseJSON || {};
        return response.message || mensajePredeterminado;
    }

    function mostrarErroresValidacion(errors) {
        if (window.RSUValidation) {
            window.RSUValidation.show(errors);
            return;
        }

        const mensajes = Object.values(errors).flat().join('\n');
        mostrarAlerta(false, mensajes || 'Verifica los datos ingresados.');
    }

    /*
     * Abrir formulario de creación o edición.
     */
    function abrirModal(url, titulo, esEdicion = false) {
        $.get(url, function (response) {
            $('#formModalLabel').text(titulo);
            $('#formModal .modal-body').html(response);

            // Indicar si estamos creando o editando
            $('#formModal').data('es-edicion', esEdicion);

            inicializarSelect2();
            formModal.show();

            if (esEdicion) {
                $('#formModal').find('#vehicle_id').trigger('change');
            }
        }).fail(function (error) {
            mostrarAlerta(
                false,
                mensajeError(error, 'No se pudo abrir el formulario.')
            );
        });
    }
       /*
     * Inicializar DataTables.
     *
     * Las claves de columns deben coincidir con las que devuelva
     * el controlador en cada objeto de data.
     */
    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        responsive: false,
        autoWidth: false,
        ajax: {
            url: "{{ route('staffgroups.index') }}",
        },
        columns: [
            { data: 'name' },
            { data: 'shift_name' },
            { data: 'zone_name' },
            { data: 'vehicle_name' },
            { data: 'driver' },
            { data: 'helpers' },

            { data: 'days'},
            { data: 'state' },
            { data: 'created_at' },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],

        order: [[0, 'asc']],

        language: {
            processing: 'Procesando...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: 'Cargando...',
            zeroRecords: 'No se encontraron grupos de personal',
            emptyTable: 'Aún no hay grupos de personal registrados',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            }
        }
    });

    /*
     * Actualizar la tabla después de crear, editar o eliminar.
     */
    function refrescarTabla() {
        table.ajax.reload(null, false);
    }

    /*
     * Botón Nuevo Grupo.
     */
    $('#btnNuevo').on('click', function () {
        abrirModal("{{route('staffgroups.create')}}", 'Nuevo Grupo de Personal');
    });

    /*
     * Botón Editar.
     * El backend debe generar un botón .btnEditar con data-url.
     */
    $(document).on('click', '.btnEditar', function () {
        const id = $(this).data('id');
        const url = "{{ route('staffgroups.edit', ':id') }}".replace(':id', id);

        abrirModal(url, 'Editar Grupo de Personal', 1);
    });

    /*
     * Envío AJAX del formulario cargado dentro del modal.
     */
    $(document).on('submit', '#formModal form', function (event) {
        event.preventDefault();
        if (!this.checkValidity()) {
            this.reportValidity();
            return;
        }
        const form = $(this);
        const submitButton = form.find('[type="submit"]');

        if (submitButton.prop('disabled')) return;

        const originalText = submitButton.html();

        submitButton.prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: {
                Accept: 'application/json'
            },

            success: function (response) {
                formModal.hide();
                refrescarTabla();
                mostrarAlerta(
                    true,
                    response.message || 'Grupo de personal guardado correctamente.'
                );
            },

            error: function (xhr) {
                const response = xhr.responseJSON || {};

                if (xhr.status === 422 && response.errors) {
                    mostrarErroresValidacion(response.errors);
                    return;
                }

                mostrarAlerta(
                    false,
                    mensajeError(xhr, 'No se pudo guardar el grupo de personal.')
                );
            },

            complete: function () {
                submitButton.prop('disabled', false);
                submitButton.html(originalText);
            }
        });
    });

    /*
     * Eliminar grupo mediante AJAX.
     * El botón debe incluir class="btnEliminar" y data-url.
     */
    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const id = $(this).data('id');

        const url = "{{route('staffgroups.destroy', ':id')}}".replace(':id', id);

        if (!url) {
            mostrarAlerta(false, 'No se especificó la URL de eliminación.');
            return;
        }

        Swal.fire({
            title: '¿Eliminar este grupo de personal?',
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
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    Accept: 'application/json'
                },

                success: function (response) {
                    refrescarTabla();

                    mostrarAlerta(
                        true,
                        response.message || 'Grupo de personal eliminado correctamente.'
                    );
                },

                error: function (xhr) {
                    mostrarAlerta(
                        false,
                        mensajeError(xhr, 'No se pudo eliminar el grupo de personal.')
                    );
                }
            });
        });
    });


    $(document).on('click', '.btnVer', function () {
        const id = $(this).data('id');
        const url = "{{ route('staffgroups.show', ':id') }}".replace(':id', id);

        abrirModal(url, 'Detalle del grupo de personal');
    });



    /*
     * Limpiar el formulario cuando se cierra el modal.
     */
    formModalElement.addEventListener('hidden.bs.modal', function () {
        $('#formModalBody').empty();
    });


    

    function inicializarSelect2() {
        $('#formModal select').each(function () {
            var selector = $(this);
            if (selector.hasClass('select2-hidden-accessible')) selector.select2('destroy');
            var opciones = { theme: 'bootstrap-5', width: '100%', dropdownParent: $('#formModal') };

            if (selector.attr('id') === 'color_id') {
                var renderizarColor = function (opcion) {
                    if (!opcion.id) return opcion.text;

                    var codigoColor = $(opcion.element).data('color');
                    var nombreColor = $('<span>').text(opcion.text);

                    if (!codigoColor) return nombreColor;

                    return $('<span class="vehicle-color-option">')
                        .append($('<span class="vehicle-color-swatch" aria-hidden="true">').css('background-color', codigoColor))
                        .append(nombreColor);
                };

                opciones.templateResult = renderizarColor;
                opciones.templateSelection = renderizarColor;
            }

            selector.select2(opciones);
        });
    }

    //cuando seleccionamos un vehiculo se crea un formulario automaticamente con la capacidad
    $(document).on('change', '#vehicle_id', function(){
        const capacidad = $(this).find(':selected').data('capacity');
        const cantidadAyudantes = Math.max(0, capacidad - 1);
        generarCamposPersonal(cantidadAyudantes);
    })

    function generarCamposPersonal(cantidadAyudantes) {
        const $contenedor = $('#camposPersonal');

        $contenedor.empty();

        // Campo del conductor
        $contenedor.append(`
        <hr>
            <div class="">
                Seleccione un conductor y al menos un ayudante, sin superar la capacidad del vehículo.
            </div>
            
            <div class="mb-3 ">
                <label for="driver_id" class="form-label">Conductor *</label>
                <select name="driver_id" id="driver_id" class="form-select" required>
                    <option value="">Seleccione un conductor</option>
                </select>
            </div>
        `);

        // Campos de ayudantes
        for (let i = 1; i <= cantidadAyudantes; i++) {
            $contenedor.append(`
                <div class="mb-3">
                    <label for="helpers_${i}" class="form-label">Ayudante ${i}${i === 1 ? ' *' : ''}</label>
                    <select name="helpers[]" id="helpers_${i}" class="form-select" ${i === 1 ? 'required' : ''}>
                        <option value="">Seleccione un ayudante</option>
                    </select>
                </div>
            `);
        }

        inicializarSelect2($contenedor);
    }

    $(document).on('change', '#shift_id, #zone_id, #vehicle_id, [name="days[]"]', function () {
        const shift = $('#shift_id').val();
        const zone = $('#zone_id').val();
        const vehicle = $('#vehicle_id').val();

        const days = $('[name="days[]"]:checked').map(function () {
            return this.value;
        }).get();

        if (shift && zone && vehicle && days.length > 0) {
            $('#camposPersonal').removeClass('d-none');
            
            const $form = $('#formModal').find('form');

            const data = {
                turnos: shift,
                zona: zone,
                vehiculo: vehicle,
                dias: days,
                id_staff: $form.attr('data-id') || null
            };

            $.get({
                url: "{{ route('staffgroups.helpers') }}",
                data: data,

                success: function (response) {


                    const conductorActual = String(
                        $form.attr('data-current-driver') || ''
                    );

                    const ayudantesActuales = JSON.parse(
                        $form.attr('data-current-helpers') || '[]'
                    ).map(String);

                    // Cargar conductores
                    const $driver = $('#driver_id');

                    $driver.empty().append(
                        new Option('Seleccione un conductor', '')
                    );

                    $.each(response.conductores, function (id, nombre) {
                        $driver.append(new Option(nombre, id));
                    });

                    // Seleccionar conductor actual
                    if (conductorActual) {
                        $driver.val(conductorActual);
                    }

                    $driver.trigger('change.select2');

                    // Cargar ayudantes
                    $('[name="helpers[]"]').each(function (index) {
                        const $select = $(this);
                        const ayudanteActual = ayudantesActuales[index] || '';

                        $select.empty().append(
                            new Option('Seleccione un ayudante', '')
                        );

                        $.each(response.ayudantes, function (id, nombre) {
                            $select.append(new Option(nombre, id));
                        });

                        if (ayudanteActual) {
                            $select.val(ayudanteActual);
                        }

                        $select.trigger('change.select2');
                    });

                    $('[name="helpers[]"]').first().trigger('change');

                },

                error: function (xhr) {
                    console.error(xhr.responseJSON ?? xhr.responseText);
                }
            });

            return;
        }

        $('#camposPersonal').addClass('d-none');
    });


    $(document).on('change', '[name="helpers[]"]', function () {
        const seleccionados = $('[name="helpers[]"]').map(function () {
            return $(this).val();
        }).get().filter(Boolean);

        $('[name="helpers[]"]').each(function () {
            const selectActual = this;
            const valorActual = $(selectActual).val();

            $(selectActual).find('option').each(function () {
                const valor = this.value;

                if (!valor) return;

                // Ocultar si está seleccionado en otro select
                const ocupadoEnOtro = seleccionados.includes(valor) && valor !== valorActual;

                $(this).prop('disabled', ocupadoEnOtro);
            });
        });
    });
});
</script>

@stop
