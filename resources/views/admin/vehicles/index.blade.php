@extends('adminlte::page')

@section('title', 'Vehículos | RSUProject')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link href="{{ asset('css/vehicle-select2.css') }}" rel="stylesheet">
<style>
    .vehicles-module #DataTable th,
    .vehicles-module #DataTable td {
        vertical-align: middle;
    }

    .vehicles-module .vehicle-modal .modal-content {
        overflow: hidden;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
    }

    .vehicles-module .vehicle-modal-header {
        min-height: 76px;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }

    .vehicles-module .vehicle-modal-heading {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    .vehicles-module .vehicle-modal-heading small {
        display: block;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
    }

    .vehicles-module .vehicle-modal-icon {
        display: inline-grid;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        place-items: center;
        color: var(--bs-info-text-emphasis);
        background-color: var(--bs-info-bg-subtle);
        border: 1px solid var(--bs-info-border-subtle);
        border-radius: .65rem;
        font-size: 1.15rem;
    }

    .vehicles-module .vehicle-modal-body {
        padding: 1.5rem;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
    }

    .vehicles-module .vehicle-form-intro {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .vehicles-module .vehicle-form-intro h6,
    .vehicles-module .vehicle-modal .form-label {
        color: var(--bs-body-color);
    }

    .vehicles-module .vehicle-form-intro p,
    .vehicles-module .vehicle-modal .form-text {
        color: var(--bs-secondary-color);
    }

    .vehicles-module .vehicle-modal .form-label {
        margin-bottom: .45rem;
    }

    .vehicles-module .vehicle-modal .form-control,
    .vehicles-module .vehicle-modal .form-select,
    .vehicles-module .select2-container--bootstrap-5 .select2-selection {
        min-height: 42px;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }

    .vehicles-module .vehicle-modal textarea.form-control {
        min-height: 112px;
    }

    .vehicles-module .vehicle-modal .form-control::placeholder {
        color: var(--bs-secondary-color);
        opacity: .8;
    }

    .vehicles-module .vehicle-modal .form-control:focus,
    .vehicles-module .vehicle-modal .form-select:focus,
    .vehicles-module .select2-container--bootstrap-5.select2-container--focus .select2-selection {
        border-color: rgba(var(--bs-info-rgb), .8);
        box-shadow: 0 0 0 .2rem rgba(var(--bs-info-rgb), .16);
    }

    .vehicles-module .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color);
    }

    .vehicles-module .select2-container .select2-selection.is-invalid {
        border-color: var(--bs-form-invalid-border-color);
    }

    .vehicles-module .vehicle-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
    }

    .vehicles-module #formModal .vehicle-modal-footer {
        margin: 1.5rem -1.5rem -1.5rem;
    }

    .vehicles-module .vehicle-detail-card {
        height: 100%;
        padding: 1rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .6rem;
    }

    .vehicles-module .vehicle-detail-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--bs-secondary-color);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .vehicles-module .vehicle-detail-value {
        color: var(--bs-body-color);
        font-weight: 600;
    }

    .vehicles-module .vehicle-detail-description {
        color: var(--bs-body-color);
        white-space: pre-wrap;
    }

    .vehicles-module .vehicle-logo-preview,
    .vehicles-module .vehicle-photo-preview,
    .vehicles-module .vehicle-show-image-preview {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        color: inherit;
        background: transparent;
        border: 0;
        border-radius: .35rem;
        cursor: zoom-in;
    }

    .vehicles-module .vehicle-logo-preview img {
        width: 70px;
        height: 50px;
        object-fit: contain;
    }

    .vehicles-module .vehicle-photo-preview img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border: 1px solid var(--bs-border-color);
        border-radius: .35rem;
    }

    .vehicles-module .vehicle-show-image-preview,
    .vehicles-module .vehicle-show-image-preview img {
        width: 100%;
    }

    .vehicles-module .vehicle-show-image-preview img {
        max-height: 280px;
        object-fit: contain;
    }

    .vehicles-module .vehicle-logo-preview:focus-visible,
    .vehicles-module .vehicle-photo-preview:focus-visible,
    .vehicles-module .vehicle-show-image-preview:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }

    .vehicles-module .vehicle-color-swatch {
        display: inline-block;
        width: 30px;
        height: 30px;
        border: 1px solid var(--bs-border-color);
        border-radius: .25rem;
        vertical-align: middle;
    }

    .vehicles-module .vehicle-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .vehicles-module .vehicle-actions .vehicle-action-btn {
        display: inline-flex;
        width: 36px;
        min-width: 36px;
        height: 31px;
        flex: 0 0 36px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-width: 1px;
        border-style: solid;
        border-radius: .25rem;
    }

    .vehicles-module .vehicle-actions .vehicle-action-btn i {
        line-height: 1;
    }

    .vehicles-module #vehicleLogoModal .vehicle-logo-modal-body {
        display: grid;
        min-height: 320px;
        place-items: center;
    }

    .vehicles-module #vehicleLogoModalImage {
        width: 100%;
        max-height: 70vh;
        object-fit: contain;
    }

    html.dark .vehicles-module .vehicle-modal .modal-content,
    html[data-bs-theme="dark"] .vehicles-module .vehicle-modal .modal-content {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5);
    }

    @media (max-width: 575.98px) {
        .vehicles-module .vehicle-modal-body {
            padding: 1rem;
        }

        .vehicles-module #formModal .vehicle-modal-footer {
            margin: 1rem -1rem -1rem;
        }

        .vehicles-module .vehicle-modal-footer {
            flex-wrap: wrap;
        }
    }
</style>
@stop

@section('content')
    <div class="vehicles-module">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">Listado de Vehículos</h4>
                <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Vehículo
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
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
                                <small>Complete la información del vehículo</small>
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
                                <small>Vista ampliada de la fotografía</small>
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
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('vehicleShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);
    const logoModalElement = document.getElementById('vehicleLogoModal');
    const logoModal = bootstrap.Modal.getOrCreateInstance(logoModalElement);
    let modalToRestoreAfterLogo = null;

    function mostrarAlerta(esExito, mensaje) {
        return Swal.fire({
            title: esExito ? 'Proceso exitoso' : 'Ocurrió un error',
            icon: esExito ? 'success' : 'error',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }
    window.mostrar_alerta = function (tipo, mensaje) {
        return mostrarAlerta(tipo !== 0, mensaje);
    };

    function mensajeError(error, predeterminado) {
        const response = error.responseJSON || {};
        if (error.status === 404) return 'El recurso solicitado ya no existe.';
        if (error.status === 419) return 'La sesión ha expirado. Recargue la página.';
        return response.mensaje || response.message || predeterminado;
    }

    function limpiarErrores(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.select2-selection.is-invalid').removeClass('is-invalid');
        form.find('.vehicle-field-error').remove();
    }

    function mostrarErroresValidacion(form, errors) {
        limpiarErrores(form);
        Object.entries(errors).forEach(function ([field, messages]) {
            const input = form.find('[name="' + field + '"]').first();
            if (!input.length) return;
            input.addClass('is-invalid');
            const target = input.hasClass('select2-hidden-accessible') ? input.next('.select2') : input;
            if (input.hasClass('select2-hidden-accessible')) target.find('.select2-selection').addClass('is-invalid');
            $('<div>', { class: 'invalid-feedback d-block vehicle-field-error', text: messages.join(' ') }).insertAfter(target);
        });
        form.find('.is-invalid').first().trigger('focus');
    }

    function mostrarErrorFormulario(error, form) {
        const response = error.responseJSON || {};
        if (error.status === 422 && response.errors) {
            mostrarErroresValidacion(form, response.errors);
            return;
        }
        mostrarAlerta(false, mensajeError(error, 'No se pudo guardar el vehículo.'));
    }

    function inicializarSelect2() {
        $('#formModal select').each(function () {
            const select = $(this);
            if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
            select.select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#formModal') });
        });
    }

    function mostrarFormulario(titulo, contenido) {
        $('#formModalLabel').text(titulo);
        $('#formModal .modal-body').html(contenido);
        inicializarSelect2();
        formModal.show();
    }

    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('vehicles.index')),
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
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
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
    window.refresTable = function () { table.ajax.reload(null, false); };

    $('#btnNuevo').on('click', function () {
        $.get(@json(route('vehicles.create')), function (response) {
            mostrarFormulario('Nuevo Vehículo', response);
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo abrir el formulario.')); });
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#vehicleShowContent').html(response);
            showModal.show();
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo cargar el vehículo.')); });
    });

    $(document).on('click', '.btnEditar', function () {
        $.get($(this).data('url'), function (response) {
            mostrarFormulario('Editar Vehículo', response);
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo abrir el formulario.')); });
    });

    $(document).on('click', '.btnImagen', function () {
        $.get($(this).data('url'), function (response) {
            mostrarFormulario('Imágenes del Vehículo', response);
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudieron cargar las imágenes.')); });
    });

    $(document).on('input change', '#formModal form [name]', function () {
        const input = $(this);
        input.removeClass('is-invalid');
        const target = input.hasClass('select2-hidden-accessible') ? input.next('.select2') : input;
        target.find('.select2-selection').removeClass('is-invalid');
        target.next('.vehicle-field-error').remove();
    });

    $(document).on('submit', '#formModal form', function (event) {
        event.preventDefault();
        const form = $(this);
        const submit = form.find('[type="submit"]').prop('disabled', true);
        limpiarErrores(form);
        $.ajax({
            url: form.attr('action'), type: form.attr('method') || 'POST', data: new FormData(this),
            processData: false, contentType: false, headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide(); window.refresTable(); mostrarAlerta(true, response.mensaje);
            },
            error: function (error) { mostrarErrorFormulario(error, form); },
            complete: function () { submit.prop('disabled', false); }
        });
    });

    $(document).on('change', '#brand_id', function () {
        const brandId = $(this).val();
        const model = $('#model_id');
        model.empty().append(new Option('Seleccione un modelo', ''));
        if (!brandId) { model.trigger('change'); return; }
        const url = @json(route('vehicles.retonar_models', ':id')).replace(':id', brandId);
        $.getJSON(url, function (models) {
            $.each(models, function (id, name) { model.append(new Option(name, id)); });
            model.trigger('change');
        }).fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudieron cargar los modelos.')); });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const url = $(this).data('url');
        Swal.fire({
            title: '¿Está seguro de eliminar este vehículo?', text: 'Esta acción también eliminará sus fotografías.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar', confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: url, type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
                success: function (response) { window.refresTable(); mostrarAlerta(true, response.mensaje); },
                error: function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudo eliminar el vehículo.')); }
            });
        });
    });

    function abrirImagen(element) {
        const trigger = $(element);
        const vehicleName = trigger.data('vehicle');
        const imageTitle = vehicleName ? 'Imagen de ' + vehicleName : 'Imagen del vehículo';
        $('#vehicleLogoModalLabel').text(imageTitle);
        $('#vehicleLogoModalImage').attr('src', trigger.data('image')).attr('alt', imageTitle);

        let parent = null;
        if (formModalElement.classList.contains('show')) parent = { element: formModalElement, instance: formModal };
        if (showModalElement.classList.contains('show')) parent = { element: showModalElement, instance: showModal };
        if (!parent) { logoModal.show(); return; }

        modalToRestoreAfterLogo = parent;
        parent.element.addEventListener('hidden.bs.modal', function () { logoModal.show(); }, { once: true });
        parent.instance.hide();
    }

    $(document).on('click', '.vehicle-logo-preview, .ampliar_foto, .vehicle-show-image-preview', function () {
        abrirImagen(this);
    });

    logoModalElement.addEventListener('hidden.bs.modal', function () {
        $('#vehicleLogoModalImage').attr('src', '').attr('alt', '');
        if (modalToRestoreAfterLogo) {
            const parent = modalToRestoreAfterLogo;
            modalToRestoreAfterLogo = null;
            parent.instance.show();
        }
    });

    function vehicleImagesId() { return $('#vehicleImagesManager').data('vehicle-id'); }
    function photoUrl(routeTemplate, id) { return routeTemplate.replace(':id', id); }

    function renderPhotos(photos) {
        const body = $('#preview-photos').empty();
        if (!photos.length) {
            body.append($('<tr>').append($('<td>', { colspan: 3, class: 'text-center text-body-secondary', text: 'No hay imágenes registradas.' })));
            return;
        }
        photos.forEach(function (photo) {
            const imageUrl = '/storage/' + photo.imagen;
            const preview = $('<button>', {
                type: 'button', class: 'vehicle-photo-preview ampliar_foto', title: 'Ampliar fotografía',
                'aria-label': 'Ampliar fotografía del vehículo', 'data-image': imageUrl
            }).append($('<img>', { src: imageUrl, alt: 'Foto del vehículo', width: 80, height: 80 }));
            const radio = $('<input>', {
                type: 'radio', name: 'foto_principal', class: 'form-check-input radio_principal',
                value: photo.id, checked: Boolean(photo.profile), 'aria-label': 'Seleccionar como fotografía principal'
            });
            const remove = $('<button>', {
                type: 'button', class: 'btn btn-danger btn-sm btn_eliminar_foto',
                'data-id': photo.id, title: 'Eliminar fotografía', 'aria-label': 'Eliminar fotografía'
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
            body.append($('<tr>')
                .append($('<td>').append(preview))
                .append($('<td>', { class: 'text-center' }).append(radio))
                .append($('<td>', { class: 'text-center' }).append(remove)));
        });
    }

    function cargarFotos() {
        const id = vehicleImagesId();
        if (!id) return;
        $.get(photoUrl(@json(route('vehicles.photo.retonar_rutas_fotos', ':id')), id), renderPhotos)
            .fail(function (error) { mostrarAlerta(false, mensajeError(error, 'No se pudieron cargar las imágenes.')); });
    }

    $(document).on('click', '.btn_subir', function () {
        const input = document.getElementById('photos');
        const errorBox = $('#photos-errors').addClass('d-none').text('');
        if (!input || !input.files.length) {
            errorBox.removeClass('d-none').text('Seleccione al menos una imagen para subir.');
            return;
        }
        const data = new FormData();
        Array.from(input.files).forEach(function (photo) { data.append('photos[]', photo); });
        data.append('_token', @json(csrf_token()));
        const button = $(this).prop('disabled', true);
        $.ajax({
            url: photoUrl(@json(route('vehicles.photo.subirimagenes', ':id')), vehicleImagesId()),
            type: 'POST', data: data, processData: false, contentType: false, headers: { Accept: 'application/json' },
            success: function (response) {
                input.value = ''; cargarFotos(); window.refresTable(); mostrarAlerta(true, response.mensaje);
            },
            error: function (error) {
                const response = error.responseJSON || {};
                if (error.status === 422 && response.errors) {
                    errorBox.removeClass('d-none').text(Object.values(response.errors).flat().join(' '));
                    return;
                }
                mostrarAlerta(false, mensajeError(error, 'No se pudieron subir las imágenes.'));
            },
            complete: function () { button.prop('disabled', false); }
        });
    });

    $(document).on('change', '#photos', function () { $('#photos-errors').addClass('d-none').text(''); });

    $(document).on('click', '.btn_eliminar_foto', function () {
        const button = $(this).prop('disabled', true);
        $.ajax({
            url: photoUrl(@json(route('vehicles.photo.eliminar_imagen', ':id')), button.data('id')),
            type: 'DELETE', headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
            success: function (response) { cargarFotos(); window.refresTable(); mostrarAlerta(true, response.mensaje); },
            error: function (error) { button.prop('disabled', false); mostrarAlerta(false, mensajeError(error, 'No se pudo eliminar la imagen.')); }
        });
    });

    $(document).on('change', '.radio_principal', function () {
        const radios = $('.radio_principal').prop('disabled', true);
        $.ajax({
            url: photoUrl(@json(route('vehicles.photo.seleccionar', ':id')), $(this).val()),
            type: 'PATCH', headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
            success: function (response) { cargarFotos(); window.refresTable(); mostrarAlerta(true, response.mensaje); },
            error: function (error) { cargarFotos(); mostrarAlerta(false, mensajeError(error, 'No se pudo seleccionar la imagen principal.')); },
            complete: function () { radios.prop('disabled', false); }
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        if (modalToRestoreAfterLogo && modalToRestoreAfterLogo.element === formModalElement) return;
        $('#formModal select.select2-hidden-accessible').select2('destroy');
        $('#formModal .modal-body').empty();
    });
    showModalElement.addEventListener('hidden.bs.modal', function () {
        if (modalToRestoreAfterLogo && modalToRestoreAfterLogo.element === showModalElement) return;
        $('#vehicleShowContent').empty();
    });
});
</script>
@stop
