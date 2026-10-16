@extends('adminlte::page')

@section('title', 'Vehículos | RSUProject')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link href="{{ asset('css/vehicle-select2.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('content')
    <div class="vehicles-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0">Listado de Vehículos</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Vehículo
            </button>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="vehicles-table-wrap">
                    <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Placa</th>
                                <th>Año</th>
                                <th>Capacidad</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Tipo</th>
                                <th>Color</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade vehicle-modal" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header vehicle-modal-header">
                        <div class="vehicle-modal-heading">
                            <span class="vehicle-modal-icon" aria-hidden="true"><i class="bi bi-truck"></i></span>
                            <div>
                                <h5 class="modal-title mb-0" id="formModalLabel">Vehículo</h5>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body vehicle-modal-body"></div>
                </div>
            </div>
        </div>

        <div class="modal fade vehicle-modal" id="vehicleShowModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content" id="vehicleShowContent"></div>
            </div>
        </div>

        <div class="modal fade vehicle-modal" id="vehicleLogoModal" tabindex="-1" aria-labelledby="vehicleLogoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header vehicle-modal-header">
                        <div class="vehicle-modal-heading">
                            <span class="vehicle-modal-icon" aria-hidden="true"><i class="bi bi-image"></i></span>
                            <div>
                                <h5 class="modal-title mb-0" id="vehicleLogoModalLabel">Imagen del vehículo</h5>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body vehicle-modal-body vehicle-logo-modal-body">
                        <img id="vehicleLogoModalImage" class="img-fluid" alt="">
                    </div>
                    <div class="modal-footer vehicle-modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    var elementoModalFormulario = document.getElementById('formModal');
    var modalFormulario = bootstrap.Modal.getOrCreateInstance(elementoModalFormulario);
    var elementoModalDetalle = document.getElementById('vehicleShowModal');
    var modalDetalle = bootstrap.Modal.getOrCreateInstance(elementoModalDetalle);
    var elementoModalImagen = document.getElementById('vehicleLogoModal');
    var modalImagen = bootstrap.Modal.getOrCreateInstance(elementoModalImagen);
    var modalAReabrirAlCerrarImagen = null;

    function mostrarAlerta(esExito, mensaje) {
        if (!esExito) return window.RSUValidation.error(mensaje);
        return Swal.fire({
            title: 'Proceso exitoso',
            icon: 'success',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }
    function mensajeError(error, mensajePredeterminado) {
        var respuesta = error.responseJSON || {};
        if (error.status === 404) return 'El recurso solicitado ya no existe.';
        if (error.status === 419) return 'La sesión ha expirado. Recargue la página.';
        return respuesta.mensaje || mensajePredeterminado;
    }

    function mostrarErroresValidacion(errores) {
        window.RSUValidation.show(errores);
    }

    function mostrarErrorFormulario(error) {
        var respuesta = error.responseJSON || {};
        if (error.status === 422 && respuesta.errors) {
            mostrarErroresValidacion(respuesta.errors);
            return;
        }
        mostrarAlerta(false, mensajeError(error, 'No se pudo guardar el vehículo.'));
    }

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

    function mostrar_formulario(titulo, contenido) {
        $('#formModalLabel').text(titulo);
        $('#formModal .modal-body').html(contenido);
        inicializarSelect2();
        modalFormulario.show();
    }

    var tabla = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: false,
        autoWidth: false,
        searchDelay: 350,
        ajax: "{{ route('vehicles.index') }}",
        order: [[1, 'asc']],
        columns: [
            { data: 'logo', name: 'vehicle_images.imagen', orderable: false, searchable: false },
            { data: 'code', name: 'vehicles.code' },
            { data: 'name', name: 'vehicles.name' },
            { data: 'plate', name: 'vehicles.plate' },
            { data: 'year', name: 'vehicles.year' },
            { data: 'load_capacity', name: 'vehicles.load_capacity' },
            { data: 'brand', name: 'brands.name' },
            { data: 'model', name: 'brandmodels.name' },
            { data: 'type', name: 'vehicletypes.name' },
            { data: 'color', name: 'color.code', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center vehicle-actions-cell', width: '154px' }
        ],
        language: {
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)', loadingRecords: '',
            zeroRecords: 'No se encontraron vehículos', emptyTable: 'Aún no hay vehículos registrados',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });
    window.refresTable = function () { tabla.ajax.reload(null, false); };

    $('#btnNuevo').on('click', function () {
        $.get("{{ route('vehicles.create') }}", function (response) {
            mostrar_formulario('Nuevo Vehículo', response);
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo abrir el formulario.')); });
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#vehicleShowContent').html(response);
            modalDetalle.show();
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo cargar el vehículo.')); });
    });

    $(document).on('click', '.btnEditar', function () {
        $.get($(this).data('url'), function (response) {
            mostrar_formulario('Editar Vehículo', response);
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo abrir el formulario.')); });
    });

    $(document).on('click', '.btnImagen', function () {
        $.get($(this).data('url'), function (response) {
            mostrar_formulario('Imágenes del Vehículo', response);
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudieron cargar las imágenes.')); });
    });

    $(document).on('submit', '#formModal form', function (event) {
        event.preventDefault();
        var formulario = $(this);
        var botonEnviar = formulario.find('[type="submit"]').prop('disabled', true);
        $.ajax({
            url: formulario.attr('action'), type: formulario.attr('method') || 'POST', data: new FormData(this),
            processData: false, contentType: false, headers: { Accept: 'application/json' },
            success: function (response) {
                modalFormulario.hide(); window.refresTable(); mostrarAlerta(true, response.mensaje);
            },
            error: function (error) { mostrarErrorFormulario(error); },
            complete: function () { botonEnviar.prop('disabled', false); }
        });
    });

    $(document).on('change', '#brand_id', function () {
        var idMarca = $(this).val();
        var selectorModelo = $('#model_id');
        selectorModelo.empty().append(new Option('Seleccione un modelo', ''));
        if (!idMarca) { selectorModelo.trigger('change'); return; }
        var urlModelos = "{{ route('vehicles.retonar_models', ':id') }}".replace(':id', idMarca);
        $.getJSON(urlModelos, function (modelos) {
            $.each(modelos, function (id, nombre) { selectorModelo.append(new Option(nombre, id)); });
            selectorModelo.trigger('change');
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudieron cargar los modelos.')); });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        var url = $(this).data('url');
        Swal.fire({
            title: '¿Está seguro de eliminar este vehículo?', text: 'Esta acción también eliminará sus fotografías.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar', confirmButtonColor: '#b54747'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}", Accept: 'application/json' },
                success: function (response) { window.refresTable(); mostrarAlerta(true, response.mensaje); },
                error: function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo eliminar el vehículo.')); }
            });
        });
    });

    function abrirImagen(elemento) {
        var botonImagen = $(elemento);
        var nombreVehiculo = botonImagen.data('vehicle');
        var tituloImagen = nombreVehiculo ? 'Imagen de ' + nombreVehiculo : 'Imagen del vehículo';
        $('#vehicleLogoModalLabel').text(tituloImagen);
        $('#vehicleLogoModalImage').attr('src', botonImagen.data('image')).attr('alt', tituloImagen);

        var modalPadre = null;
        if (elementoModalFormulario.classList.contains('show')) {
            modalPadre = { elemento: elementoModalFormulario, instancia: modalFormulario };
        }
        if (elementoModalDetalle.classList.contains('show')) {
            modalPadre = { elemento: elementoModalDetalle, instancia: modalDetalle };
        }
        if (!modalPadre) { modalImagen.show(); return; }

        modalAReabrirAlCerrarImagen = modalPadre;
        modalPadre.elemento.addEventListener('hidden.bs.modal', function () { modalImagen.show(); }, { once: true });
        modalPadre.instancia.hide();
    }

    $(document).on('click', '.vehicle-logo-preview, .ampliar_foto, .vehicle-show-image-preview', function () {
        abrirImagen(this);
    });

    elementoModalImagen.addEventListener('hidden.bs.modal', function () {
        $('#vehicleLogoModalImage').attr('src', '').attr('alt', '');
        if (modalAReabrirAlCerrarImagen) {
            var modalPadre = modalAReabrirAlCerrarImagen;
            modalAReabrirAlCerrarImagen = null;
            modalPadre.instancia.show();
        }
    });

    function obtenerIdVehiculoGaleria() { return $('#vehicleImagesManager').data('vehicle-id'); }
    function construirUrlFoto(plantillaRuta, id) { return plantillaRuta.replace(':id', id); }

    function mostrarFotos(fotos) {
        var cuerpoTabla = $('#preview-photos').empty();
        if (!fotos.length) {
            cuerpoTabla.append($('<tr>').append($('<td>', { colspan: 3, class: 'text-center text-body-secondary', text: 'No hay imágenes registradas.' })));
            return;
        }
        $.each(fotos, function (indice, foto) {
            var urlImagen = '/storage/' + foto.imagen;
            var botonVistaPrevia = $('<button>', {
                type: 'button', class: 'vehicle-photo-preview ampliar_foto', title: 'Ampliar fotografía',
                'aria-label': 'Ampliar fotografía del vehículo', 'data-image': urlImagen
            }).append($('<img>', { src: urlImagen, alt: 'Foto del vehículo', width: 80, height: 80 }));
            var radioPrincipal = $('<input>', {
                type: 'radio', name: 'foto_principal', class: 'form-check-input radio_principal',
                value: foto.id, checked: Boolean(foto.profile), 'aria-label': 'Seleccionar como fotografía principal'
            });
            var botonEliminar = $('<button>', {
                type: 'button', class: 'btn btn-danger btn-sm btn_eliminar_foto',
                'data-id': foto.id, title: 'Eliminar fotografía', 'aria-label': 'Eliminar fotografía'
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
            cuerpoTabla.append($('<tr>')
                .append($('<td>').append(botonVistaPrevia))
                .append($('<td>', { class: 'text-center' }).append(radioPrincipal))
                .append($('<td>', { class: 'text-center' }).append(botonEliminar)));
        });
    }

    function cargarFotos() {
        var id = obtenerIdVehiculoGaleria();
        if (!id) return;
        $.get(construirUrlFoto("{{ route('vehicles.photo.retonar_rutas_fotos', ':id') }}", id), mostrarFotos)
            .fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudieron cargar las imágenes.')); });
    }

    $(document).on('click', '.btn_subir', function () {
        var entradaFotos = document.getElementById('photos');
        if (!entradaFotos || !entradaFotos.files.length) {
            window.RSUValidation.show({ photos: ['Seleccione al menos una imagen para subir.'] });
            return;
        }
        var datos = new FormData();
        $.each(entradaFotos.files, function (indice, foto) { datos.append('photos[]', foto); });
        datos.append('_token', "{{ csrf_token() }}");
        var botonSubir = $(this).prop('disabled', true);
        $.ajax({
            url: construirUrlFoto("{{ route('vehicles.photo.subirimagenes', ':id') }}", obtenerIdVehiculoGaleria()),
            type: 'POST', data: datos, processData: false, contentType: false, headers: { Accept: 'application/json' },
            success: function (response) {
                entradaFotos.value = ''; cargarFotos(); window.refresTable(); mostrarAlerta(true, response.mensaje);
            },
            error: function (error) {
                var respuesta = error.responseJSON || {};
                if (error.status === 422 && respuesta.errors) {
                    window.RSUValidation.show(respuesta.errors);
                    return;
                }
                mostrarAlerta(false, mensajeError(error, 'No se pudieron subir las imágenes.'));
            },
            complete: function () { botonSubir.prop('disabled', false); }
        });
    });


    $(document).on('click', '.btn_eliminar_foto', function () {
        var boton = $(this).prop('disabled', true);
        $.ajax({
            url: construirUrlFoto("{{ route('vehicles.photo.eliminar_imagen', ':id') }}", boton.data('id')),
            type: 'DELETE', headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}", Accept: 'application/json' },
            success: function (response) { cargarFotos(); window.refresTable(); mostrarAlerta(true, response.mensaje); },
            error: function (error) { boton.prop('disabled', false); mostrarAlerta(false, mensajeError(error, 'No se pudo eliminar la imagen.')); }
        });
    });

    $(document).on('change', '.radio_principal', function () {
        var radios = $('.radio_principal').prop('disabled', true);
        $.ajax({
            url: construirUrlFoto("{{ route('vehicles.photo.seleccionar', ':id') }}", $(this).val()),
            type: 'PATCH', headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}", Accept: 'application/json' },
            success: function (response) { cargarFotos(); window.refresTable(); mostrarAlerta(true, response.mensaje); },
            error: function (error) { cargarFotos(); mostrarAlerta(false, mensajeError(error, 'No se pudo seleccionar la imagen principal.')); },
            complete: function () { radios.prop('disabled', false); }
        });
    });

    elementoModalFormulario.addEventListener('hidden.bs.modal', function () {
        if (modalAReabrirAlCerrarImagen && modalAReabrirAlCerrarImagen.elemento === elementoModalFormulario) return;
        $('#formModal select.select2-hidden-accessible').select2('destroy');
        $('#formModal .modal-body').empty();
    });
    elementoModalDetalle.addEventListener('hidden.bs.modal', function () {
        if (modalAReabrirAlCerrarImagen && modalAReabrirAlCerrarImagen.elemento === elementoModalDetalle) return;
        $('#vehicleShowContent').empty();
    });
});
</script>
@stop
