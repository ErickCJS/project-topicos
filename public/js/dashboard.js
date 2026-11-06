(function ($) {
    $(function () {
        const modalElement = document.getElementById('dashboardProgrammingModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const rescheduleElement = document.getElementById('dashboardReschedulingModal');
        const rescheduleModal = bootstrap.Modal.getOrCreateInstance(rescheduleElement);
        const body = $('#dashboardProgrammingModalBody');
        const routes = {
            details: modalElement.dataset.detailsUrl,
            options: modalElement.dataset.optionsUrl,
            update: modalElement.dataset.updateUrl,
            related: modalElement.dataset.relatedUrl,
            rescheduleOptions: modalElement.dataset.rescheduleOptionsUrl,
            rescheduleCheck: modalElement.dataset.rescheduleCheckUrl,
            rescheduleStore: modalElement.dataset.rescheduleStoreUrl
        };
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        let currentId = null;
        let detailsData = null;
        let rescheduleId = null;

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

        function todayInLima() {
            const parts = new Intl.DateTimeFormat('en', {timeZone:'America/Lima',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());
            const values = Object.fromEntries(parts.map(part => [part.type, part.value]));
            return `${values.year}-${values.month}-${values.day}`;
        }

        function jsonRequest(method, url, data) {
            return $.ajax({url, method, data: JSON.stringify(data || {}), contentType:'application/json', headers:{'X-CSRF-TOKEN':csrf, Accept:'application/json'}});
        }

        function endpointOption(selector, items, selected, placeholder) {
            const element = $(selector).empty();
            if (placeholder) element.append($('<option>').val('').text(placeholder));
            (items || []).forEach(item => element.append($('<option>').val(item.id).text(item.name || item.code || item.plate).prop('selected', String(item.id) === String(selected))));
        }

        function openRescheduling(id) {
            rescheduleId = id;
            $('#dashboardReschedulingError').addClass('d-none').empty();
            jsonRequest('GET', endpoint(routes.rescheduleOptions, id)).done(options => {
                const origin = options.origin || {};
                $('#dashboardRescheduleDate').attr('min', todayInLima()).val('');
                endpointOption('#dashboardRescheduleShift', options.shifts, origin.shift_id);
                endpointOption('#dashboardRescheduleVehicle', options.vehicles, origin.vehicle_id);
                endpointOption('#dashboardRescheduleDriver', options.drivers, origin.driver_id);
                endpointOption('#dashboardRescheduleHelpers', options.helpers);
                endpointOption('#dashboardRescheduleReason', options.reasons, null, 'Seleccione un motivo');
                $('#dashboardRescheduleDescription').val('');
                rescheduleModal.show();
            }).fail(xhr => $('#dashboardReschedulingError').removeClass('d-none').text(errorMessage(xhr)));
        }

        function reschedulePayload() {
            return {destination_date:String($('#dashboardRescheduleDate').val() || '').trim(), shift_id:Number($('#dashboardRescheduleShift').val()), vehicle_id:Number($('#dashboardRescheduleVehicle').val()), driver_id:Number($('#dashboardRescheduleDriver').val()), helpers:$('#dashboardRescheduleHelpers').val() || [], reason_id:Number($('#dashboardRescheduleReason').val()), reason:$('#dashboardRescheduleReason option:selected').text().trim(), description:$('#dashboardRescheduleDescription').val()};
        }

        function renderDetails(payload) {
            detailsData = payload;
            const item = payload.current;
            const people = item.helper_people || [];
            const editingAllowed = item.status === 'Programado' && Number(item.missing_count || 0) > 0;
            const helperList = people.length ? people.map(person => '<li>' + escapeHtml(person.name) + '</li>').join('') : '<li>Sin ayudantes</li>';
            const missingPeople = (item.missing || []).filter(person => !person.present);
            const missing = missingPeople.length ? missingPeople.map(person => '<li>' + escapeHtml(person.name) + '</li>').join('') : '<li>Sin personal faltante</li>';
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
                '<div class="dashboard-people"><section><h6>Personal asignado</h6><ul><li>Conductor: ' + escapeHtml(item.driver || 'Sin conductor') + '</li>' + helperList + '</ul></section><section><h6>Personal faltante</h6><ul>' + missing + '</ul></section></div>' +
                (editingAllowed ? '<div class="dashboard-edit"><h6>Modificar programación</h6><div id="dashboard-edit-fields"></div><div id="dashboard-edit-error" class="dashboard-modal-alert" role="alert"></div><div class="dashboard-modal-footer"><button type="button" class="btn btn-primary" id="dashboard-save-modifications">Guardar cambios</button></div></div>' : '') +
                related + '<div class="dashboard-history"><h6>Historial de cambios</h6>' + history + '</div>'
            );
            $('#dashboardProgrammingModalTitle').text('Detalles de programación');
            if (editingAllowed) renderEditor(payload);
        }

        function optionMarkup(items, selected, label, includeCurrent) {
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
            if (selected && !list.some(item => String(item.id) === String(selected))) list.unshift({ id: selected, name: currentLabel, available: true });
            const row = $('<div class="dashboard-edit-row"></div>');
            row.attr('data-type', kind).attr('data-old-id', oldId || selected || '');
            row.append($('<label>').text(label));
            row.append($(optionMarkup(list, selected, kind)));
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
            fields.append(changeRow('Turno', 'shift', item.shift_id, options.shifts, item.shift, options.reasons));
            fields.append(changeRow('Vehículo', 'vehicle', item.vehicle_id, options.vehicles.map(vehicle => ({ id: vehicle.id, name: [vehicle.name, vehicle.code, vehicle.plate].filter(Boolean).join(' · '), available: vehicle.available })), [item.vehicle, item.plate].filter(Boolean).join(' · '), options.reasons));
            fields.append(changeRow('Conductor', 'driver', item.driver_id, withRetainers(options.drivers, 'driver').map(person => ({ id: person.id, name: person.name + ' · ' + person.dni, available: person.available })), item.driver, options.reasons));
            (item.helper_people || []).forEach(helper => {
                fields.append(changeRow('Ayudante actual: ' + helper.name, 'helper', helper.id, withRetainers(options.helpers, 'helper').map(person => ({ id: person.id, name: person.name + ' · ' + person.dni, available: person.available })), helper.name, options.reasons, helper.id));
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
            }).fail(function (xhr) {
                body.html('<div class="dashboard-modal-alert" role="alert">' + escapeHtml(errorMessage(xhr)) + '</div>');
            });
        });

        $(document).on('click', '.rsu-btn-reschedule', function () { openRescheduling(Number($(this).data('programming-id'))); });

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

        $('#dashboardRescheduleValidate').on('click', function () {
            const button = $(this).prop('disabled', true);
            const data = reschedulePayload();
            jsonRequest('POST', endpoint(routes.rescheduleCheck, rescheduleId), data).done(check => {
                if (!check.valid) {
                    $('#dashboardReschedulingError').removeClass('d-none').text((check.issues || []).map(issue => issue.message).join('\n') || 'La disponibilidad no es válida.');
                    return;
                }
                rescheduleModal.hide();
                Swal.fire({icon:'warning', title:'Confirmar reprogramación', text:'Se liberarán las reservas originales y se creará un nuevo recorrido.', showCancelButton:true, confirmButtonText:'Confirmar', cancelButtonText:'Cancelar'}).then(result => {
                    if (!result.isConfirmed) return;
                    jsonRequest('POST', endpoint(routes.rescheduleStore, rescheduleId), {...data, validation_token:check.validation_token}).done(response => {
                        Swal.fire({icon:'success', title:'Reprogramación confirmada', text:'Se creó el recorrido #' + response.destination_id, confirmButtonText:'Entendido'}).then(() => window.location.reload());
                    }).fail(xhr => { rescheduleModal.show(); $('#dashboardReschedulingError').removeClass('d-none').text(errorMessage(xhr)); });
                });
            }).fail(xhr => $('#dashboardReschedulingError').removeClass('d-none').text(errorMessage(xhr))).always(() => button.prop('disabled', false));
        });

        body.on('change', '[data-type="shift"] .dashboard-select', function () {
            if (detailsData && String(this.value) !== String(detailsData.current.shift_id)) refreshOptions(this.value);
        });

        body.on('change', '.dashboard-select, .dashboard-reason', function () {
            const row = $(this).closest('.dashboard-edit-row');
            const changed = String(row.find('.dashboard-select').val()) !== String(row.attr('data-old-id'));
            row.find('.dashboard-reason').prop('disabled', !changed);
            row.find('.dashboard-custom-reason, .dashboard-description').prop('disabled', !changed);
            if (row.find('.dashboard-reason').val() !== 'Otro') row.find('.dashboard-custom-reason').prop('disabled', true);
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
