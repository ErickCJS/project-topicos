@extends('adminlte::page')

@section('title', 'Zonas | RSU')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
@stop

@section('content')
<div class="zones-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0">Gestión de Zonas</h4>
        <button type="button" class="btn btn-success ms-auto" id="btnNuevaZona">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nueva zona
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="zonesTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Provincia</th>
                            <th>Distrito</th>
                            <th>Perímetro</th>
                            <th>Creación</th>
                            <th>Actualización</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="zoneFormModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="zoneFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content" id="zoneFormContent"></div>
        </div>
    </div>

    <div class="modal fade" id="zoneShowModal" tabindex="-1"
        aria-labelledby="zoneShowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content" id="zoneShowContent"></div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
$(function () {
    const formModalElement = document.getElementById('zoneFormModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('zoneShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);
    const provincesUrl = @json(route('zones.provinces', ['department' => '__ID__']));
    const districtsUrl = @json(route('zones.districts', ['province' => '__ID__']));
    const peruCenter = [-9.19, -75.015];

    function alertMessage(success, message) {
        return Swal.fire({
            icon: success ? 'success' : 'error',
            title: success ? 'Proceso exitoso' : 'Ocurrió un error',
            text: message,
            confirmButtonText: 'Entendido'
        });
    }

    function clearErrors(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('[data-error-for]').text('').addClass('d-none');
        form.find('.zone-general-error').addClass('d-none').text('');
    }

    function showErrors(form, errors) {
        clearErrors(form);
        Object.entries(errors || {}).forEach(function (entry) {
            const field = entry[0];
            const messages = Array.isArray(entry[1]) ? entry[1] : [entry[1]];
            const input = form.find('[name="' + field + '"]');
            input.addClass('is-invalid');
            const error = form.find('[data-error-for="' + field + '"]');
            if (error.length) error.removeClass('d-none').text(messages[0] || 'Valor no válido.');
            else form.find('.zone-general-error').removeClass('d-none').text(messages[0] || 'Valor no válido.');
        });
    }

    function requestError(xhr, fallback, form) {
        const response = xhr.responseJSON || {};
        if (xhr.status === 422 && response.errors && form) {
            showErrors(form, response.errors);
            return;
        }
        alertMessage(false, response.message || response.mensaje || fallback);
    }

    function fillSelect(select, items, placeholder, selected) {
        select.empty().append($('<option>', { value: '', text: placeholder }));
        items.forEach(function (item) {
            select.append($('<option>', {
                value: item.id,
                text: item.name + ' (' + item.code + ')'
            }));
        });
        if (selected) select.val(String(selected));
    }

    function loadProvinces(departmentId, selected) {
        const select = $('#zone_province_id');
        const districts = $('#zone_district_id');
        fillSelect(select, [], 'Cargando provincias...', '');
        fillSelect(districts, [], 'Seleccione un distrito', '');
        if (!departmentId) {
            fillSelect(select, [], 'Seleccione una provincia', '');
            return $.Deferred().resolve().promise();
        }
        return $.get(provincesUrl.replace('__ID__', departmentId)).done(function (items) {
            fillSelect(select, items, 'Seleccione una provincia', selected || '');
        });
    }

    function loadDistricts(provinceId, selected) {
        const select = $('#zone_district_id');
        fillSelect(select, [], 'Cargando distritos...', '');
        if (!provinceId) {
            fillSelect(select, [], 'Seleccione un distrito', '');
            return $.Deferred().resolve().promise();
        }
        return $.get(districtsUrl.replace('__ID__', provinceId)).done(function (items) {
            fillSelect(select, items, 'Seleccione un distrito', selected || '');
        });
    }

    function initializeZoneForm(form) {
        const department = form.find('[name="department_id"]');
        const province = form.find('[name="province_id"]');
        const district = form.find('[name="district_id"]');
        const selectedProvince = province.attr('data-selected') || province.val();
        const selectedDistrict = district.attr('data-selected') || district.val();

        department.off('change.zone').on('change.zone', function () {
            loadProvinces(this.value, '').fail(function () {
                alertMessage(false, 'No se pudieron cargar las provincias.');
            });
        });
        province.off('change.zone').on('change.zone', function () {
            loadDistricts(this.value, '').fail(function () {
                alertMessage(false, 'No se pudieron cargar los distritos.');
            });
        });

        if (department.val()) {
            loadProvinces(department.val(), selectedProvince).then(function () {
                return loadDistricts(province.val(), selectedDistrict);
            }).fail(function () {
                alertMessage(false, 'No se pudo cargar la selección geográfica.');
            });
        }
        initializeEditableMap(form.find('[data-zone-map]'), form.find('[data-zone-perimeter]'));
    }

    function parseGeoJson(value) {
        if (!value) return null;
        try { return typeof value === 'string' ? JSON.parse(value) : value; }
        catch (error) { return null; }
    }

    function initializeEditableMap(element, input) {
        if (!element.length || typeof L === 'undefined') return;
        const map = L.map(element[0]).setView(peruCenter, 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        const layers = new L.FeatureGroup().addTo(map);
        const controls = new L.Control.Draw({
            edit: { featureGroup: layers },
            draw: { polyline: false, rectangle: false, circle: false, circlemarker: false, marker: false }
        });
        map.addControl(controls);

        function sync() {
            const layer = layers.getLayers()[0];
            input.val(layer ? JSON.stringify(layer.toGeoJSON().geometry) : '');
        }

        const existing = parseGeoJson(input.val());
        if (existing && existing.type === 'Polygon') {
            L.geoJSON(existing, { style: { color: '#198754' } }).eachLayer(function (layer) {
                layers.addLayer(layer);
            });
            if (layers.getLayers().length) map.fitBounds(layers.getBounds(), { padding: [20, 20] });
        }
        map.on(L.Draw.Event.CREATED, function (event) {
            layers.clearLayers();
            layers.addLayer(event.layer);
            sync();
        });
        map.on(L.Draw.Event.EDITED, sync);
        map.on(L.Draw.Event.DELETED, sync);
        setTimeout(function () { map.invalidateSize(); }, 100);
        element.data('leaflet-map', map);
    }

    function initializeReadonlyMap(element) {
        if (!element.length || typeof L === 'undefined') return;
        const map = L.map(element[0], { zoomControl: true }).setView(peruCenter, 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        const geometry = parseGeoJson(element.attr('data-perimeter'));
        if (geometry && geometry.type === 'Polygon') {
            const layer = L.geoJSON(geometry, { style: { color: '#0d6efd', fillOpacity: .25 } }).addTo(map);
            map.fitBounds(layer.getBounds(), { padding: [20, 20] });
        }
        setTimeout(function () { map.invalidateSize(); }, 100);
        element.data('leaflet-map', map);
    }

    const table = $('#zonesTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('zones.index')),
        columns: [
            { data: 'name', name: 'name' },
            { data: 'department', orderable: false, searchable: false },
            { data: 'province', orderable: false, searchable: false },
            { data: 'district', orderable: false, searchable: false },
            { data: 'perimeter_status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros', infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '', zeroRecords: 'No se encontraron zonas', emptyTable: 'Aún no hay zonas registradas',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function openForm(url) {
        $.get(url).done(function (response) {
            $('#zoneFormContent').html(response);
            initializeZoneForm($('#zoneFormContent form'));
            formModal.show();
        }).fail(function (xhr) { requestError(xhr, 'No se pudo abrir el formulario de zonas.'); });
    }

    $('#btnNuevaZona').on('click', function () { openForm(@json(route('zones.create'))); });
    $('#zonesTable').on('click', '.btnVer', function () {
        $.get($(this).data('url')).done(function (response) {
            $('#zoneShowContent').html(response);
            initializeReadonlyMap($('#zoneShowContent [data-zone-show-map]'));
            showModal.show();
        }).fail(function (xhr) { requestError(xhr, 'No se pudo cargar el detalle de la zona.'); });
    });
    $('#zonesTable').on('click', '.btnEditar', function () { openForm($(this).data('url')); });

    $('#zoneFormModal').on('submit', 'form[data-zone-form]', function (event) {
        event.preventDefault();
        const form = $(this);
        const submit = form.find('[type="submit"]').prop('disabled', true);
        clearErrors(form);
        if (!form.find('[name="perimeter"]').val()) {
            showErrors(form, { perimeter: ['Dibuje el perímetro de la zona.'] });
            submit.prop('disabled', false);
            return;
        }
        $.ajax({
            url: form.attr('action'), type: 'POST', data: new FormData(this), processData: false,
            contentType: false, headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide(); table.ajax.reload(null, false);
                alertMessage(true, response.message || response.mensaje || 'Zona guardada correctamente.');
            },
            error: function (xhr) { requestError(xhr, 'No se pudo guardar la zona.', form); },
            complete: function () { submit.prop('disabled', false); }
        });
    });

    $('#zonesTable').on('click', '.btnEliminar', function () {
        const url = $(this).data('url');
        Swal.fire({
            title: '¿Eliminar esta zona?', text: 'Esta acción no se puede revertir.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: url, type: 'DELETE', headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' } })
                .done(function (response) {
                    table.ajax.reload(null, false);
                    alertMessage(true, response.message || response.mensaje || 'Zona eliminada correctamente.');
                }).fail(function (xhr) { requestError(xhr, 'No se pudo eliminar la zona.'); });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () { $('#zoneFormContent').empty(); });
    showModalElement.addEventListener('hidden.bs.modal', function () { $('#zoneShowContent').empty(); });
    $('#zoneFormModal, #zoneShowModal').on('shown.bs.modal', function () {
        $(this).find('[data-zone-map], [data-zone-show-map]').each(function () {
            const map = $(this).data('leaflet-map');
            if (map) map.invalidateSize();
        });
    });
});
</script>
@stop
