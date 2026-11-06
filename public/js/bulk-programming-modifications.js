document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('bulkModificationModal');
    const openButton = document.getElementById('bulkModification');
    if (!modalElement || !openButton) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const $ = id => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('[name="_token"]')?.value;
    let token = null;
    let options = {people: [], old_helpers: [], vehicles: []};
    let reports = [];

    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const call = async (path, data) => {
        const response = await fetch(`/admin/programming/bulk-modifications/${path}`, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(data)});
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo completar la operación.');
        return result;
    };
    const type = () => $('bulkModificationType').value;
    const selectedId = id => $(id).value ? Number($(id).value) : null;
    const formData = () => {
        const data = {start_date: $('bulkModificationStart').value, end_date: $('bulkModificationEnd').value, zone_id: selectedId('bulkModificationZone'), type: type(), reason: $('bulkModificationReason').value, custom_reason: $('bulkModificationCustomReason').value, description: $('bulkModificationDescription').value, ids: reports.map(row => Number(row.id))};
        if (type() === 'shift') data.new_shift_id = selectedId('bulkModificationShift');
        if (type() === 'driver') data.new_driver_id = selectedId('bulkModificationDriver');
        if (type() === 'helper') { data.old_employee_id = selectedId('bulkModificationHelperOld'); data.new_employee_id = selectedId('bulkModificationHelperNew'); }
        if (type() === 'vehicle') data.new_vehicle_id = selectedId('bulkModificationVehicle');
        return data;
    };
    const fill = (select, rows, label) => { select.innerHTML = '<option value="">Seleccione</option>' + rows.map(row => `<option value="${row.id}">${esc(label(row))}</option>`).join(''); $(select.id).dispatchEvent(new Event('change')); };
    const loadOptions = async () => {
        if (!$('bulkModificationStart').value || !$('bulkModificationEnd').value || !$('bulkModificationZone').value || !type()) return;
        try {
            options = await call('options', {start_date: $('bulkModificationStart').value, end_date: $('bulkModificationEnd').value, zone_id: Number($('bulkModificationZone').value), type: type()});
            if (type() === 'driver') fill($('bulkModificationDriver'), options.people, row => `${row.dni} — ${row.name}`);
            if (type() === 'helper') { fill($('bulkModificationHelperOld'), options.old_helpers, row => `${row.dni} — ${row.name}`); fill($('bulkModificationHelperNew'), options.people, row => `${row.dni} — ${row.name}`); }
            if (type() === 'vehicle') fill($('bulkModificationVehicle'), options.vehicles, row => `${row.code} · ${row.name} · ${row.plate}`);
        } catch (error) { showError(error.message); }
    };
    const showError = message => Swal.fire({icon: 'error', title: 'Revise los datos', text: message, confirmButtonText: 'Entendido'});
    const readable = value => {
        if (value === null || value === undefined || value === '') return 'Sin asignar';
        if (Array.isArray(value)) return value.length ? value.map(readable).join(', ') : 'Sin ayudantes';
        if (typeof value === 'object') return readable(value.name ?? value.value ?? value.label ?? value.id);
        return String(value);
    };
    const employeeText = id => {
        const employee = [...(options.people || []), ...(options.old_helpers || [])].find(row => Number(row.id) === Number(id));
        return employee ? `${employee.dni} \u2014 ${employee.name}` : `ID ${id}`;
    };
    const fieldValue = (row, field, next = false) => {
        const value = next ? row.new?.[field] : row.current?.[field];
        if (next && field === 'driver_id') return employeeText(value);
        if (next && field === 'helper_ids') return Array.isArray(value) ? value.map(employeeText).join(', ') || 'Sin ayudantes' : readable(value);
        return readable(value);
    };
    const previewFields = row => [
        {key: 'shift', changed: type() === 'shift'},
        {key: 'vehicle', changed: type() === 'vehicle'},
        {key: 'driver_id', currentKey: 'driver', changed: type() === 'driver'},
        {key: 'helper_ids', currentKey: 'helpers', changed: type() === 'helper'},
    ].map(field => ({...field, current: fieldValue(row, field.currentKey || field.key), next: fieldValue(row, field.key, true), same: (row.errors || []).some(error => error.includes('ya tiene el valor seleccionado'))}));
    const renderField = field => field.changed && (field.same || field.current === field.next)
        ? `<div class="bulk-preview-same"><small>Sin cambio</small>${esc(field.current)}</div>`
        : field.changed
        ? `<div class="bulk-preview-change"><span class="bulk-preview-old"><small>Anterior</small>${esc(field.current)}</span><span class="bulk-preview-arrow" aria-hidden="true">\u2192</span><span class="bulk-preview-new"><small>Nuevo</small>${esc(field.next)}</span></div>`
        : `<span class="bulk-preview-value">${esc(field.current)}</span>`;
    const renderConflictSummary = rows => {
        const counts = new Map();
        rows.flatMap(row => row.errors || []).forEach(message => counts.set(message, (counts.get(message) || 0) + 1));
        const summary = $('bulkModificationConflictSummary');
        if (!counts.size) {
            summary.classList.add('d-none');
            summary.replaceChildren();
            return;
        }
        summary.classList.remove('d-none');
        summary.innerHTML = `<strong>Conflictos agrupados</strong><ul class="mb-0 mt-2">${[...counts].map(([message, count]) => `<li>${esc(message)} <span class="badge text-bg-danger">${count}</span></li>`).join('')}</ul>`;
    };
    const invalidate = () => {
        token = null;
        reports = [];
        $('bulkModificationApply').disabled = true;
        $('bulkModificationReportWrap').classList.add('d-none');
        $('bulkModificationSummary').classList.add('d-none');
        $('bulkModificationConflictSummary').classList.add('d-none');
        $('bulkModificationReport').replaceChildren();
    };
    const syncDates = () => {
        const start = $('bulkModificationStart').value;
        const end = $('bulkModificationEnd');
        end.min = start || '';
        if (start && end.value && end.value < start) {
            end.value = '';
            end.setCustomValidity('');
        } else {
            end.setCustomValidity('');
        }
    };
    const render = result => {
        reports = result.rows || [];
        $('bulkModificationReportWrap').classList.toggle('d-none', !reports.length);
        $('bulkModificationReport').innerHTML = reports.map(row => {
            const fields = previewFields(row);
            return `<tr><td class="text-nowrap">${esc(row.date)}</td>${fields.map(field => `<td>${renderField(field)}</td>`).join('')}<td><span class="badge ${row.valid ? 'text-bg-success' : 'text-bg-danger'}">${row.valid ? 'V\u00e1lida' : 'Con conflicto'}</span></td><td>${row.errors?.length ? `<ul class="mb-0 ps-3 bulk-preview-errors">${row.errors.map(error => `<li>${esc(error)}</li>`).join('')}</ul>` : '<span class="text-body-secondary">Sin conflictos</span>'}</td></tr>`;
        }).join('');
        $('bulkModificationSummary').classList.toggle('d-none', !reports.length);
        $('bulkModificationSummary').textContent = reports.length ? `${reports.length} programaci\u00f3n(es) encontradas. ${reports.filter(row => row.valid).length} v\u00e1lidas y ${reports.filter(row => !row.valid).length} con conflictos.` : 'No se encontraron programaciones.';
        renderConflictSummary(reports);
        $('bulkModificationApply').disabled = !result.valid || !result.validation_token;
    };
    const setFields = () => {
        ['Shift','Driver','HelperOld','HelperNew','Vehicle'].forEach(name => $(`bulkModification${name}Field`)?.classList.add('d-none'));
        if (type() === 'shift') $('bulkModificationShiftField').classList.remove('d-none');
        if (type() === 'driver') $('bulkModificationDriverField').classList.remove('d-none');
        if (type() === 'helper') { $('bulkModificationHelperOldField').classList.remove('d-none'); $('bulkModificationHelperNewField').classList.remove('d-none'); }
        if (type() === 'vehicle') $('bulkModificationVehicleField').classList.remove('d-none');
        invalidate();
    };
    const validate = async () => {
        syncDates();
        const start = $('bulkModificationStart').value;
        const end = $('bulkModificationEnd').value;
        if (!start || !end) return showError('La fecha inicial y la fecha final son obligatorias.');
        if (end < start) return showError('La fecha final no puede ser anterior a la fecha inicial.');
        try {
            const result = await call('check', formData());
            token = result.validation_token;
            render(result);
            if (!result.valid) Swal.fire({icon: 'warning', title: 'Hay conflictos', text: result.message || 'Corrija los conflictos indicados.', confirmButtonText: 'Entendido'});
        } catch (error) { showError(error.message); }
    };
    openButton.addEventListener('click', () => modal.show());
    $('bulkModificationType').addEventListener('change', () => { setFields(); loadOptions(); });
    $('bulkModificationStart').addEventListener('change', () => { syncDates(); invalidate(); loadOptions(); });
    $('bulkModificationEnd').addEventListener('change', () => { syncDates(); invalidate(); loadOptions(); });
    $('bulkModificationZone').addEventListener('change', () => { invalidate(); loadOptions(); });
    ['bulkModificationShift','bulkModificationDriver','bulkModificationHelperOld','bulkModificationHelperNew','bulkModificationVehicle','bulkModificationReason','bulkModificationCustomReason','bulkModificationDescription'].forEach(id => $(id).addEventListener('change', event => {
        if (id === 'bulkModificationReason') $('bulkModificationCustomReasonField').classList.toggle('d-none', event.target.value !== 'Otro');
        invalidate();
    }));
    ['bulkModificationCustomReason','bulkModificationDescription'].forEach(id => $(id).addEventListener('input', invalidate));
    $('bulkModificationPreview').addEventListener('click', validate);
    $('bulkModificationApply').addEventListener('click', async () => {
        if (!token) return;
        const confirmation = await Swal.fire({icon: 'warning', title: '¿Confirmar cambio masivo?', text: 'Todas las programaciones válidas se modificarán en una sola operación.', showCancelButton: true, confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar'});
        if (!confirmation.isConfirmed) return;
        try {
            const result = await call('apply', {...formData(), validation_token: token});
            invalidate(); modal.hide(); Swal.fire({icon: 'success', title: 'Cambio aplicado', text: result.message, confirmButtonText: 'Entendido'}); window.jQuery?.('#programmingTable').DataTable?.().ajax.reload(null, false);
        } catch (error) { showError(error.message); }
    });
    syncDates();
    modalElement.addEventListener('hidden.bs.modal', () => { invalidate(); });
});
