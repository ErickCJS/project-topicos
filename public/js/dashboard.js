(function ($) {
    $(function () {
        const modalElement = document.getElementById('dashboardProgrammingModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const body = $('#dashboardProgrammingModalBody');
        const routes = {
            details: modalElement.dataset.detailsUrl,
            options: modalElement.dataset.optionsUrl,
            update: modalElement.dataset.updateUrl,
            related: modalElement.dataset.relatedUrl
        };
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        let currentId = null;
        let detailsData = null;

        function endpoint(template, id) {
            return template.replace('__ID__', encodeURIComponent(id));
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function errorMessage(xhr) {
            const response = xhr.responseJSON || {};
            return Object.values(response.errors || {}).flat()[0] || response.message || 'No se pudo completar la operación.';
        }

        function loading() {
            body.html('<div class="dashboard-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Cargando...</div>');
        }

        function renderDetails(payload) {
            detailsData = payload;
            const item = payload.current;
            const people = item.helper_people || [];
            const editingAllowed = modalElement.dataset.canEdit === '1' && ['Programado', 'Reprogramado'].includes(item.status) && Number(item.missing_count || 0) > 0;
            const helperList = people.length ? people.map(person => '<li><span>Ayudante \u00b7 ID ' + escapeHtml(person.id) + ' \u00b7 ' + escapeHtml(person.name) + '</span><strong>' + (person.present ? 'Presente' : 'Faltante') + '</strong></li>').join('') : '<li>Sin ayudantes</li>';
            const missingPeople = (item.missing || []).filter(person => !person.present);
            const missing = missingPeople.length ? missingPeople.map(person => '<li>' + escapeHtml(person.role === 'driver' ? 'Conductor' : 'Ayudante') + ' \u00b7 ID ' + escapeHtml(person.id) + ' \u00b7 ' + escapeHtml(person.name) + '</li>').join('') : '<li>Sin personal faltante</li>';
            const history = (payload.history || []).length ? '<ol>' + payload.history.map(change => '<li><strong>' + escapeHtml(change.type) + '</strong> · ' + escapeHtml(change.before?.value || '') + ' → ' + escapeHtml(change.after?.value || '') + ' · ' + escapeHtml(change.at) + '</li>').join('') + '</ol>' : '<p>Sin cambios registrados.</p>';
            const status = item.status === 'Programado' ? 'Programado' : escapeHtml(item.status);
            const related = (payload.related || []).length ? '<div class="dashboard-related"><h6>Recorridos relacionados</h6><div class="d-flex flex-wrap gap-2">' + payload.related.map(route => '<button type="button" class="btn btn-sm ' + (Number(route.id) === Number(item.id) ? 'btn-primary' : 'btn-outline-primary') + ' dashboard-related-route" data-related-id="' + escapeHtml(route.id) + '">#' + escapeHtml(route.id) + ' · ' + escapeHtml(route.status) + ' · ' + escapeHtml(route.date) + '</button>').join('') + '</div></div>' : '';
            body.html(
                '<div class="dashboard-details-grid">' +
                    '<div class="dashboard-detail"><small>Zona</small><strong>' + escapeHtml(item.zone || 'Sin zona') + '</strong></div>' +
                    '<div class="dashboard-detail"><small>Turno</small><strong>' + escapeHtml(item.shift || 'Sin turno') + ' ' + escapeHtml(item.hours || '') + '</strong></div>' +
                    '<div class="dashboard-detail"><small>Grupo</small><strong>' + escapeHtml(item.group || 'Sin grupo') + '</strong></div>' +
                    '<div class="dashboard-detail"><small>Vehículo</small><strong>' + escapeHtml((item.vehicle || '') + ' ' + (item.plate || '')) + '</strong></div>' +
                    '<div class="dashboard-detail"><small>Estado</small><strong>' + status + '</strong></div>' +
                    '<div class="dashboard-detail"><small>Personal</small><strong>' + escapeHtml(item.present_count || 0) + ' presentes · ' + escapeHtml(item.missing_count || 0) + ' faltantes</strong></div>' +
                '</div>' +
                '<div class="dashboard-people"><section><h6>Personal asignado</h6><ul><li><span>Conductor \u00b7 ID ' + escapeHtml(item.driver_id || '') + ' \u00b7 ' + escapeHtml(item.driver || 'Sin conductor') + '</span><strong>' + ((item.people || []).find(person => person.role === 'driver')?.present ? 'Presente' : 'Faltante') + '</strong></li>' + helperList + '</ul></section><section><h6>Personal faltante \u00b7 ' + escapeHtml(item.date || '') + ' \u00b7 ' + escapeHtml(item.shift || '') + '</h6><ul>' + missing + '</ul></section></div>' +
                (editingAllowed ? '<div class="dashboard-edit"><h6>Modificar programación</h6><div id="dashboard-edit-fields"></div><div id="dashboard-edit-error" class="dashboard-modal-alert" role="alert"></div><div class="dashboard-modal-footer"><button type="button" class="btn btn-primary" id="dashboard-save-modifications">Guardar cambios</button></div></div>' : '') +
                related + '<div class="dashboard-history"><h6>Historial de cambios</h6>' + history + '</div>'
            );
            $('#dashboardProgrammingModalTitle').text('Detalles de programación');
            if (editingAllowed) renderEditor(payload);
        }

        function optionMarkup(items, selected, label) {
            const options = (items || []).map(item => {
                const value = item.id;
                const text = item.name || item.label || (item.code ? item.code + ' · ' + item.plate : '');
                return '<option value="' + escapeHtml(value) + '" data-retainer="' + (item.retainer ? '1' : '0') + '" ' + (String(value) === String(selected) ? 'selected ' : '') + (item.available === false ? 'disabled' : '') + '>' + escapeHtml(text) + (item.available === false ? ' (No disponible)' : '') + '</option>';
            }).join('');
            return '<select class="form-control dashboard-select" data-kind="' + label + '"><option value="">Seleccione</option>' + options + '</select>';
        }

        function reasonMarkup(reasons) {
            return '<select class="form-control dashboard-reason"><option value="">Motivo del cambio</option>' + (reasons || []).map(reason => '<option value="' + escapeHtml(reason.name) + '">' + escapeHtml(reason.name) + '</option>').join('') + '</select>';
        }

        function changeRow(label, kind, currentId, choices, currentLabel, reasons, oldId) {
            const selected = currentId;
            const list = (choices || []).slice();
            const personnel = kind === 'driver' || kind === 'helper';
            const row = $('<div class="dashboard-edit-row"></div>');
            row.attr('data-type', kind).attr('data-old-id', oldId || selected || '');
            row.append($('<label>').text(label));
            if (personnel) row.append($('<div class="dashboard-current-person"></div>').text('Actual: ' + currentLabel + ' \u00b7 ID ' + (oldId || selected)));
            row.append($(optionMarkup(list, personnel ? '' : selected, kind)));
            row.append($(reasonMarkup(reasons)));
            row.append('<div class="dashboard-change-details"><input class="form-control dashboard-custom-reason" type="text" maxlength="120" placeholder="Especifique el motivo"><input class="form-control dashboard-description" type="text" maxlength="1000" placeholder="Descripción"></div>');
            row.find('.dashboard-custom-reason, .dashboard-description').prop('disabled', true);
            return row;
        }

        function renderEditor(payload) {
            const item = payload.current;
            const options = payload.options || {};
            const fields = $('#dashboard-edit-fields').empty();
            const retainers = options.retainers || [];
            const withRetainers = (items, role) => items.concat(retainers
                .filter(person => person.role === role)
                .map(person => ({ ...person, name: person.name + ' · Retén presente' })));
            const shiftPanel = $('<section class="dashboard-edit-panel dashboard-edit-panel-shift"><h6><i class="bi bi-clock" aria-hidden="true"></i> Cambio de turno</h6></section>');
            fields.append(shiftPanel.append(changeRow('Turno', 'shift', item.shift_id, options.shifts, item.shift, options.reasons)));
            const vehiclePanel = $('<section class="dashboard-edit-panel dashboard-edit-panel-vehicle"><h6><i class="bi bi-truck" aria-hidden="true"></i> Cambio de vehículo</h6></section>');
            fields.append(vehiclePanel.append(changeRow('Vehículo', 'vehicle', item.vehicle_id, options.vehicles.map(vehicle => ({ id: vehicle.id, name: [vehicle.name, vehicle.code, vehicle.plate].filter(Boolean).join(' · '), available: vehicle.available })), [item.vehicle, item.plate].filter(Boolean).join(' · '), options.reasons)));
            const personnelPanel = $('<section class="dashboard-edit-panel dashboard-edit-panel-people"><h6><i class="bi bi-people" aria-hidden="true"></i> Cambio de personal</h6></section>');
            fields.append(personnelPanel);
            if (!(item.people || []).some(person => person.role === 'driver' && person.present)) personnelPanel.append(changeRow('Conductor', 'driver', item.driver_id, withRetainers(options.drivers, 'driver').map(person => ({ id: person.id, name: person.name + ' · ' + person.dni, available: person.available })), item.driver, options.reasons));
            (item.helper_people || []).filter(helper => !helper.present).forEach(helper => {
                personnelPanel.append(changeRow('Ayudante', 'helper', helper.id, withRetainers(options.helpers, 'helper').map(person => ({ id: person.id, name: person.name + ' · ' + person.dni, available: person.available })), helper.name, options.reasons, helper.id));
            });
            syncDashboardHelpers();
        }

        function syncDashboardHelpers() {
            const fields = $('#dashboard-edit-fields .dashboard-edit-row[data-type="helper"] .dashboard-select');
            const selected = fields.map((index, element) => element.value).get().filter(Boolean);
            fields.each(function () {
                const current = this.value;
                Array.from(this.options).forEach(option => {
                    if (option.value) option.disabled = selected.includes(option.value) && option.value !== current;
                });
            });
        }

        function refreshOptions(shiftId) {
            $.get(endpoint(routes.options, currentId), { shift_id: shiftId }).done(function (options) {
                if (!detailsData) return;
                detailsData.options = options;
                renderEditor(detailsData);
                $('#dashboard-edit-fields [data-type="shift"] .dashboard-select').val(String(shiftId));
            }).fail(function (xhr) {
                $('#dashboard-edit-error').text(errorMessage(xhr));
            });
        }

        $(document).on('click', '.rsu-btn-details', function () {
            const trigger = $(this).prop('disabled', true);
            currentId = $(this).data('programming-id');
            try {
                detailsData = { summary: JSON.parse($(this).attr('data-summary')) };
            } catch (error) {
                detailsData = { summary: {} };
            }
            loading();
            modal.show();
            $.when($.get(endpoint(routes.details, currentId)), $.get(endpoint(routes.related, currentId))).done(function (detailResult, relatedResult) {
                const payload = detailResult[0];
                const summary = detailsData?.summary || {};
                payload.current = Object.assign({}, summary, payload.current);
                payload.related = relatedResult[0].data || [];
                renderDetails(payload);
                trigger.prop('disabled', false);
            }).fail(function (xhr) {
                body.html('<div class="dashboard-modal-alert" role="alert">' + escapeHtml(errorMessage(xhr)) + '</div>');
                trigger.prop('disabled', false);
            });
        });

        body.on('click', '.dashboard-related-route', function () {
            const id = Number($(this).data('related-id'));
            currentId = id;
            loading();
            $.when($.get(endpoint(routes.details, id)), $.get(endpoint(routes.related, id))).done(function (detailResult, relatedResult) {
                const payload = detailResult[0];
                payload.related = relatedResult[0].data || [];
                renderDetails(payload);
            }).fail(function (xhr) { body.html('<div class="dashboard-modal-alert" role="alert">' + escapeHtml(errorMessage(xhr)) + '</div>'); });
        });

        body.on('change', '[data-type="shift"] .dashboard-select', function () {
            if (detailsData && String(this.value) !== String(detailsData.current.shift_id)) refreshOptions(this.value);
        });

        body.on('change', '.dashboard-select, .dashboard-reason', function () {
            const row = $(this).closest('.dashboard-edit-row');
            const selected = row.find('.dashboard-select').val();
            const changed = Boolean(selected) && String(selected) !== String(row.attr('data-old-id'));
            row.find('.dashboard-reason').prop('disabled', !changed);
            row.find('.dashboard-custom-reason, .dashboard-description').prop('disabled', !changed);
            if (row.find('.dashboard-reason').val() !== 'Otro') row.find('.dashboard-custom-reason').prop('disabled', true);
            if (row.attr('data-type') === 'helper') syncDashboardHelpers();
        });

        body.on('change', '.dashboard-reason', function () {
            const row = $(this).closest('.dashboard-edit-row');
            row.find('.dashboard-custom-reason').prop('disabled', !$(this).val() || $(this).val() !== 'Otro');
        });

        body.on('click', '#dashboard-save-modifications', function () {
            const changes = [];
            $('#dashboard-edit-fields .dashboard-edit-row').each(function () {
                const row = $(this);
                const selected = row.find('.dashboard-select').val();
                const oldId = String(row.attr('data-old-id'));
                if (!selected || String(selected) === oldId) return;
                changes.push({
                    type: row.attr('data-type'),
                    old_id: row.attr('data-type') === 'helper' ? oldId : null,
                    new_id: Number(selected),
                    is_retainer: row.find('.dashboard-select option:selected').attr('data-retainer') === '1',
                    reason: row.find('.dashboard-reason').val(),
                    custom_reason: row.find('.dashboard-custom-reason').val(),
                    description: row.find('.dashboard-description').val()
                });
            });
            $('#dashboard-edit-error').text('');
            if (!changes.length) {
                $('#dashboard-edit-error').text('Seleccione al menos un cambio.');
                return;
            }
            const invalid = changes.find(change => !change.reason || (change.reason === 'Otro' && !change.custom_reason.trim()));
            if (invalid) {
                $('#dashboard-edit-error').text(invalid.reason === 'Otro' ? 'Especifique el motivo del cambio.' : 'Seleccione el motivo de cada cambio.');
                return;
            }
            const button = $(this).prop('disabled', true);
            $.ajax({
                url: endpoint(routes.update, currentId),
                type: 'PATCH',
                data: JSON.stringify({ changes: changes }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
            }).done(function () {
                Swal.fire({
                    title: 'Proceso exitoso',
                    icon: 'success',
                    text: 'Modificaciones guardadas correctamente.',
                    confirmButtonText: 'Entendido'
                }).then(function () {
                    window.location.reload();
                });
            }).fail(function (xhr) {
                $('#dashboard-edit-error').text(errorMessage(xhr));
            }).always(function () {
                button.prop('disabled', false);
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            body.empty();
            detailsData = null;
        });
    });
})(jQuery);
