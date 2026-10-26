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
    const perimetersUrl = @json(route('zones.perimeters'));
    // Nominatim (OpenStreetMap) is used without credentials. Requests are cached,
    // serialized and kept below the public service's recommended rate.
    const geocoderUrl = 'https://nominatim.openstreetmap.org/search';
    const geocodeCache = new Map();
    let geocodeSequence = 0;
    let activeGeocodeRequest = null;
    let geocodeTimer = null;
    let lastGeocodeAt = 0;

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
                text: item.name + ' (' + item.code + ')',
                'data-location-name': item.name,
                'data-location-code': item.code
            }));
        });
        if (selected) select.val(String(selected));
    }

    function loadProvinces(form, departmentId, selected) {
        const select = form.find('[name="province_id"]');
        const districts = form.find('[name="district_id"]');
        const previousRequest = select.data('geography-request');
        if (previousRequest) previousRequest.abort();
        fillSelect(select, [], 'Cargando provincias...', '');
        fillSelect(districts, [], 'Seleccione un distrito', '');
        if (!departmentId) {
            fillSelect(select, [], 'Seleccione una provincia', '');
            return $.Deferred().resolve().promise();
        }
        const request = $.get(provincesUrl.replace('__ID__', departmentId)).done(function (items) {
            fillSelect(select, items, 'Seleccione una provincia', selected || '');
        });
        select.data('geography-request', request);
        return request;
    }

    function loadDistricts(form, provinceId, selected) {
        const select = form.find('[name="district_id"]');
        const previousRequest = select.data('geography-request');
        if (previousRequest) previousRequest.abort();
        fillSelect(select, [], 'Cargando distritos...', '');
        if (!provinceId) {
            fillSelect(select, [], 'Seleccione un distrito', '');
            return $.Deferred().resolve().promise();
        }
        const request = $.get(districtsUrl.replace('__ID__', provinceId)).done(function (items) {
            fillSelect(select, items, 'Seleccione un distrito', selected || '');
        });
        select.data('geography-request', request);
        return request;
    }

    function optionName(select) {
        const option = select.find('option:selected');
        return option.data('location-name') || option.text().replace(/\s*\([^)]*\)\s*$/, '').trim();
    }

    function applyGeocodedLocation(map, result) {
        const locationLayer = map._zoneLocationLayer;
        if (locationLayer) locationLayer.clearLayers();
        if (result.geojson && ['Polygon', 'MultiPolygon', 'LineString'].includes(result.geojson.type)) {
            const boundary = L.geoJSON(result.geojson, {
                style: { color: '#0d6efd', weight: 2, dashArray: '6 5', fillOpacity: .05 }
            });
            boundary.addTo(locationLayer);
            if (boundary.getBounds().isValid()) map.fitBounds(boundary.getBounds(), { padding: [28, 28], maxZoom: 14 });
            return;
        }
        const latitude = Number(result.lat);
        const longitude = Number(result.lon);
        if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
            map.flyTo([latitude, longitude], result.type === 'city' ? 12 : 13, { duration: .8 });
        }
    }

    function geolocate(map, query) {
        if (!query || !map) return;
        const cacheKey = query.toLowerCase();
        const sequence = ++geocodeSequence;
        if (activeGeocodeRequest) activeGeocodeRequest.abort();
        if (geocodeTimer) clearTimeout(geocodeTimer);
        if (geocodeCache.has(cacheKey)) {
            const cached = geocodeCache.get(cacheKey);
            if (cached) applyGeocodedLocation(map, cached);
            return;
        }
        const wait = Math.max(0, 1100 - (Date.now() - lastGeocodeAt));
        geocodeTimer = setTimeout(function () {
            const controller = new AbortController();
            activeGeocodeRequest = controller;
            lastGeocodeAt = Date.now();
            const url = geocoderUrl + '?format=jsonv2&limit=1&polygon_geojson=1&countrycodes=pe&q=' + encodeURIComponent(query);
            fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } })
            .then(function (response) { if (!response.ok) throw new Error('Geocoder unavailable'); return response.json(); })
            .then(function (results) {
                if (sequence !== geocodeSequence) return;
                const result = results[0] || null;
                geocodeCache.set(cacheKey, result);
                if (result) applyGeocodedLocation(map, result);
            })
            .catch(function (error) {
                if (error.name !== 'AbortError') console.warn('No se pudo ubicar la selección geográfica.', error);
            });
        }, wait);
    }

    function selectedLocationQuery(form, level) {
        const department = optionName(form.find('[name="department_id"]'));
        const province = optionName(form.find('[name="province_id"]'));
        const district = optionName(form.find('[name="district_id"]'));
        if (level === 'department' && department) return department + ', Peru';
        if (level === 'province' && province && department) return province + ', ' + department + ', Peru';
        if (level === 'district' && district && province && department) return district + ', ' + province + ', ' + department + ', Peru';
        return '';
    }

    function initializeZoneForm(form) {
        if (!form.length || form.data('zone-form-initialized')) return;
        form.data('zone-form-initialized', true);
        const department = form.find('[name="department_id"]');
        const province = form.find('[name="province_id"]');
        const district = form.find('[name="district_id"]');
        const selectedProvince = province.attr('data-selected') || province.val();
        const selectedDistrict = district.attr('data-selected') || district.val();
        const mapElement = form.find('[data-zone-map]');
        const hasExistingPerimeter = Boolean(form.find('[data-zone-perimeter]').val());
        function moveTo(level) {
            const move = mapElement.data('zone-geolocate');
            if (move) move(selectedLocationQuery(form, level));
        }

        department.off('change.zone').on('change.zone', function () {
            moveTo('department');
            loadProvinces(form, this.value, '').fail(function () {
                alertMessage(false, 'No se pudieron cargar las provincias.');
            });
        });
        province.off('change.zone').on('change.zone', function () {
            moveTo('province');
            loadDistricts(form, this.value, '').fail(function () {
                alertMessage(false, 'No se pudieron cargar los distritos.');
            });
        });
        district.off('change.zone').on('change.zone', function () { moveTo('district'); });

        if (department.val()) {
            loadProvinces(form, department.val(), selectedProvince).then(function () {
                return loadDistricts(form, province.val(), selectedDistrict);
            }).fail(function () {
                alertMessage(false, 'No se pudo cargar la selección geográfica.');
            }).then(function () {
                if (!hasExistingPerimeter) moveTo('district');
            });
        }
        initializeEditableMap(mapElement, form.find('[data-zone-perimeter]'));
    }

function parseGeoJson(value) {
    if (!value) return null;

    if (typeof value !== 'string') {
        return value;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        try {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = value;

            return JSON.parse(textarea.value);
        } catch (decodeError) {
            console.error('Error al interpretar el GeoJSON:', decodeError);
            return null;
        }
    }
}

    function loadExistingZones(map, layerGroup, currentZoneId) {
        $.getJSON(perimetersUrl)
            .done(function (zones) {
                if (!map._loaded) return;

                layerGroup.clearLayers();

                zones.forEach(function (zone) {
                    if (String(zone.id) === String(currentZoneId)) return;

                    const geometry = parseGeoJson(zone.perimeter);
                    if (!geometry || geometry.type !== 'Polygon') return;

                    const polygon = L.geoJSON(geometry, {
                        style: {
                            color: '#dc3545',
                            weight: 2,
                            fillColor: '#dc3545',
                            fillOpacity: 0.22
                        }
                    });

                    polygon.bindPopup(
                        $('<div>').text(zone.name).html()
                    );

                    polygon.addTo(layerGroup);
                });
            })
            .fail(function () {
                console.error('No se pudieron cargar las zonas existentes.');
            });
    }
    function initializeEditableMap(element, input) {
        if (!element.length || typeof L === 'undefined' || element.data('leaflet-map')) return;
        const map = L.map(element[0]);
        map.fitWorld();
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors; geocoding: Nominatim'
        }).addTo(map);
        map._zoneLocationLayer = new L.FeatureGroup().addTo(map);

        // Capa independiente para visualizar las zonas existentes.
        const existingZonesLayer = new L.FeatureGroup().addTo(map);

        // Capa exclusiva del polígono que se está dibujando o editando.
        const layers = new L.FeatureGroup().addTo(map);

        const currentZoneId = element.closest('form').attr('data-zone-id') || '';
        loadExistingZones(map, existingZonesLayer, currentZoneId);
        const controls = new L.Control.Draw({
            edit: { featureGroup: layers },
            draw: { polyline: false, rectangle: false, circle: false, circlemarker: false, marker: false }
        });
        map.addControl(controls);

        let confirmedPerimeter = parseGeoJson(input.val());
        const editState = { active: false, dirty: false };

        function sync() {
            input.val(confirmedPerimeter ? JSON.stringify(confirmedPerimeter) : '');
            editState.dirty = false;
        }

        function confirmLayer(layer) {
            confirmedPerimeter = layer ? layer.toGeoJSON().geometry : null;
            sync();
        }

        function fitPerimeter() {
            if (!confirmedPerimeter || confirmedPerimeter.type !== 'Polygon') return;
            const bounds = L.geoJSON(confirmedPerimeter).getBounds();
            if (bounds.isValid()) map.fitBounds(bounds, { padding: [20, 20] });
        }

        const existing = confirmedPerimeter;
        if (existing && existing.type === 'Polygon') {
            L.geoJSON(existing, { style: { color: '#198754' } }).eachLayer(function (layer) {
                layers.addLayer(layer);
            });
            fitPerimeter();
        }
        map.on(L.Draw.Event.CREATED, function (event) {
            layers.clearLayers();
            layers.addLayer(event.layer);
            confirmLayer(event.layer);
        });
        map.on(L.Draw.Event.EDITED, function () {
            confirmLayer(layers.getLayers()[0]);
        });
        map.on(L.Draw.Event.DELETED, function () {
            confirmLayer(null);
        });
        map.on(L.Draw.Event.EDITSTART, function () { editState.active = true; });
        map.on(L.Draw.Event.EDITVERTEX, function () { editState.dirty = true; });
        map.on(L.Draw.Event.EDITSTOP, function () {
            editState.active = false;
        });
        element.data('zone-sync-perimeter', sync);
        element.data('zone-fit-perimeter', fitPerimeter);
        element.data('zone-edit-state', editState);
        element.data('zone-geolocate', function (query) { geolocate(map, query); });
        element.data('leaflet-map', map);
    }

    function initializeReadonlyMap(element) {
        if (!element.length || typeof L === 'undefined' || element.data('leaflet-map')) return;
        const map = L.map(element[0], { zoomControl: true });
        map.fitWorld();
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        const geometry = parseGeoJson(element.attr('data-perimeter'));
        function fitPerimeter() {
            if (!geometry || geometry.type !== 'Polygon') return;
            const bounds = L.geoJSON(geometry).getBounds();
            if (bounds.isValid()) map.fitBounds(bounds, { padding: [20, 20] });
        }
        if (geometry && geometry.type === 'Polygon') {
            const polygon = L.geoJSON(geometry, { style: { color: '#0d6efd', fillOpacity: .25 } }).addTo(map);
            const ring = geometry.coordinates?.[0] || [];
            const vertices = L.layerGroup(ring.slice(0, -1).map(function (coordinate) {
                return L.circleMarker([coordinate[1], coordinate[0]], {
                    radius: 5, color: '#084298', weight: 2, fillColor: '#0d6efd', fillOpacity: 1
                });
            })).addTo(map);
            fitPerimeter();
        }
        element.data('zone-fit-perimeter', fitPerimeter);
        element.data('leaflet-map', map);
    }

    function destroyMaps(container) {
        geocodeSequence++;
        if (activeGeocodeRequest) activeGeocodeRequest.abort();
        if (geocodeTimer) clearTimeout(geocodeTimer);
        container.find('[data-zone-map], [data-zone-show-map]').each(function () {
            const element = $(this);
            const map = element.data('leaflet-map');
            if (map) {
                map.off();
                map.remove();
                map._zoneLocationLayer = null;
            }
            element.removeData('leaflet-map zone-geolocate zone-sync-perimeter zone-fit-perimeter zone-edit-state');
        });
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
            formModal.show();
        }).fail(function (xhr) { requestError(xhr, 'No se pudo abrir el formulario de zonas.'); });
    }

    $('#btnNuevaZona').on('click', function () { openForm(@json(route('zones.create'))); });
    $('#zonesTable').on('click', '.btnVer', function () {
        $.get($(this).data('url')).done(function (response) {
            $('#zoneShowContent').html(response);
            showModal.show();
        }).fail(function (xhr) { requestError(xhr, 'No se pudo cargar el detalle de la zona.'); });
    });
    $('#zonesTable').on('click', '.btnEditar', function () { openForm($(this).data('url')); });

    $('#zoneFormModal').on('submit', 'form[data-zone-form]', function (event) {
        event.preventDefault();
        const form = $(this);
        const submit = form.find('[type="submit"]').prop('disabled', true);
        const mapElement = form.find('[data-zone-map]');
        const syncPerimeter = mapElement.data('zone-sync-perimeter');
        if (typeof syncPerimeter === 'function') syncPerimeter();
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

    formModalElement.addEventListener('hidden.bs.modal', function () {
        destroyMaps($('#zoneFormContent'));
        $('#zoneFormContent').empty();
    });
    showModalElement.addEventListener('hidden.bs.modal', function () {
        destroyMaps($('#zoneShowContent'));
        $('#zoneShowContent').empty();
    });
    $('#zoneFormModal').on('shown.bs.modal', function () {
        initializeZoneForm($('#zoneFormContent form[data-zone-form]'));
        $(this).find('[data-zone-map]').each(function () {
            const map = $(this).data('leaflet-map');
            const fitPerimeter = $(this).data('zone-fit-perimeter');
            if (map) {
                map.invalidateSize();
                if (typeof fitPerimeter === 'function') fitPerimeter();
            }
        });
    });
    $('#zoneShowModal').on('shown.bs.modal', function () {
        initializeReadonlyMap($('#zoneShowContent [data-zone-show-map]'));
        $(this).find('[data-zone-map], [data-zone-show-map]').each(function () {
            const map = $(this).data('leaflet-map');
            if (map) {
                map.invalidateSize();
                const fitPerimeter = $(this).data('zone-fit-perimeter');
                if (typeof fitPerimeter === 'function') fitPerimeter();
            }
        });
    });
});
</script>
@stop
