/* UI prototype: all records and availability rules below are sample data.
 * No requests or database writes are made. Refreshing restores the examples.
 */
$(function () {
    'use strict';
    const people = [
        { id: 'd1', name: 'Miguel Díaz Vidarte', role: 'driver' },
        { id: 'd2', name: 'Carlos Guerrero', role: 'driver' },
        { id: 'd3', name: 'Miguel García', role: 'driver' },
        { id: 'h1', name: 'Miguel Gómez', role: 'helper' },
        { id: 'h2', name: 'Fernando Ramírez', role: 'helper' },
        { id: 'h3', name: 'Juan Martínez', role: 'helper' },
        { id: 'h4', name: 'Juan Díaz', role: 'helper' }
    ];
    const groups = [
        { id: 'a', name: 'Grupo A', zone: 'Zona Norte', shift: 'Mañana', hours: '06:00 - 14:00', vehicle: 'Vehículo 03', plate: 'CLO-004', driver: 'd1', helpers: ['h1', 'h2'], days: [1, 3, 5, 0] },
        { id: 'b', name: 'Grupo B', zone: 'Zona Centro', shift: 'Tarde', hours: '14:00 - 22:00', vehicle: 'Vehículo 02', plate: 'ABC-205', driver: 'd2', helpers: ['h3', 'h4'], days: [2, 4, 6] },
        { id: 'c', name: 'Grupo C', zone: 'Zona Sur', shift: 'Noche', hours: '22:00 - 06:00', vehicle: 'Vehículo 01', plate: 'XYZ-310', driver: 'd3', helpers: ['h1', 'h4'], days: [1, 2, 3, 4, 5] }
    ];
    const unavailable = [
        { person: 'd1', start: '2026-10-26', end: '2026-10-30', reason: 'Vacaciones aprobadas', replacement: 'Carlos Guerrero' },
        { person: 'h2', start: '2026-10-26', end: '2026-10-31', reason: 'Contrato no disponible', replacement: 'Juan Martínez' }
    ];
    const escape = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));
    const personName = id => people.find(person => person.id === id)?.name || '—';
    const groupById = id => groups.find(group => group.id === id);
    const formatDate = date => date.split('-').reverse().join('/');
    const form = document.getElementById('programmingForm');
    const formModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('programmingFormModal'));
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('programmingDetailModal'));
    const fields = { start: $('#programStart'), end: $('#programEnd'), group: $('#programGroup'), driver: $('#programDriver'), helper1: $('#programHelper1'), helper2: $('#programHelper2'), notes: $('#programNotes') };
    let records = [
        ['2026-10-02', 'a', 'Finalizado'], ['2026-10-05', 'a', 'Programado'],
        ['2026-10-07', 'a', 'Programado'], ['2026-10-09', 'b', 'Programado'],
        ['2026-10-12', 'c', 'Programado'], ['2026-10-14', 'b', 'Programado']
    ].map(([date, groupId, status], index) => {
        const group = groupById(groupId);
        return { id: index + 1, date, groupId, status, driver: group.driver, helpers: [...group.helpers], notes: '', history: ['Programación creada con datos de ejemplo.'] };
    });
    let nextId = records.length + 1;
    let editingId = null;
    let validated = false;

    groups.forEach(group => fields.group.append(new Option(`${group.name} · ${group.zone} · ${group.shift}`, group.id)));
    fields.group.select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#programmingFormModal'), placeholder: 'Busque por nombre, zona o turno', allowClear: true });
    people.forEach(person => {
        if (person.role === 'driver') fields.driver.append(new Option(person.name, person.id));
        else {
            fields.helper1.append(new Option(person.name, person.id));
            fields.helper2.append(new Option(person.name, person.id));
        }
    });

    function actionButton(action, icon, label, style = 'outline-secondary') {
        return `<button type="button" class="btn btn-${style}" data-action="${action}" title="${label}" aria-label="${label}"><i class="bi bi-${icon}" aria-hidden="true"></i></button>`;
    }
    const table = $('#programmingTable').DataTable({
        data: records,
        order: [[0, 'asc']],
        pageLength: 10,
        columns: [
            { data: 'date', render: (value, type) => ['display', 'export'].includes(type) ? formatDate(value) : value },
            { data: 'status', render: (value, type) => type === 'display' ? `<span class="programming-status ${value === 'Finalizado' ? 'text-success-emphasis bg-success-subtle' : 'text-info-emphasis bg-info-subtle'}">${escape(value)}</span>` : value },
            { data: 'groupId', render: id => escape(groupById(id).zone) },
            { data: 'groupId', render: (id, type) => type === 'display' ? `<span class="badge bg-warning-subtle text-warning-emphasis">${escape(groupById(id).shift)}</span>` : groupById(id).shift },
            { data: 'groupId', render: (id, type) => { const group = groupById(id); return type === 'display' ? `<span class="programming-vehicle">${escape(group.vehicle)}<small>${escape(group.plate)}</small></span>` : `${group.vehicle} ${group.plate}`; } },
            { data: 'driver', render: id => escape(personName(id)) },
            { data: 'helpers', render: (ids, type) => type === 'display' ? ids.map(id => `<span class="programming-helper">${escape(personName(id))}</span>`).join('') : ids.map(personName).join(', ') },
            { data: null, orderable: false, searchable: false, render: row => `<div class="programming-actions" data-id="${row.id}">${actionButton('detail', 'eye', 'Ver detalle')}${row.status === 'Programado' ? actionButton('edit', 'pencil-square', 'Modificar programación', 'outline-warning') + actionButton('delete', 'trash', 'Eliminar programación', 'outline-danger') : ''}${actionButton('history', 'clock-history', 'Historial de cambios')}${row.status === 'Programado' ? actionButton('finish', 'check-circle', 'Finalizar programación', 'outline-success') : ''}</div>` }
        ],
        language: {
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros', info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Sin registros', infoFiltered: '(filtrado de _MAX_ registros)', zeroRecords: 'No se encontraron programaciones',
            emptyTable: 'No hay programaciones para mostrar', paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });
    const exportOptions = { columns: index => index !== 7 && table.column(index).visible(), orthogonal: 'export', modifier: { search: 'applied', order: 'applied', selected: null } };
    function reportMetadata() {
        const rows = table.rows({ search: 'applied' }).data().toArray();
        const start = $('#filterStart').val(), end = $('#filterEnd').val();
        const filters = [
            `Período: ${start ? formatDate(start) : 'Sin inicio'} - ${end ? formatDate(end) : 'Sin fin'}`,
            `Zona: ${$('#filterZone').val() || 'Todas'}`, `Turno: ${$('#filterShift').val() || 'Todos'}`
        ];
        if (table.search()) filters.push(`Búsqueda: ${table.search()}`);
        return {
            issued: new Intl.DateTimeFormat('es-PE', { dateStyle: 'short', timeStyle: 'short', timeZone: 'America/Lima' }).format(new Date()),
            count: rows.length, scheduled: rows.filter(row => row.status === 'Programado').length,
            finished: rows.filter(row => row.status === 'Finalizado').length, filters
        };
    }
    new $.fn.dataTable.Buttons(table, { buttons: [
        { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel', title: 'Programaciones — datos de ejemplo', exportOptions },
        { extend: 'pdfHtml5', text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF', title: 'Programaciones', filename: 'SGRS-JLO-programaciones', orientation: 'landscape', pageSize: 'A4', exportOptions,
            customize: doc => window.ProgrammingReports.customizePdf(doc, reportMetadata()) },
        { extend: 'print', text: '<i class="bi bi-printer me-1"></i>Imprimir', title: 'Programaciones', exportOptions,
            customize: win => window.ProgrammingReports.customizePrint(win, reportMetadata(), table.buttons.exportData(exportOptions)) }
    ] }).container().appendTo('#programmingExports');
    table.columns().every(function (index) {
        if (index === 7) return;
        $('#programmingColumns').append(`<label class="form-check mb-2"><input type="checkbox" class="form-check-input" data-column="${index}" checked><span class="form-check-label">${escape(this.header().textContent)}</span></label>`);
    });
    $('#programmingColumns').on('change', 'input', function () { table.column(Number(this.dataset.column)).visible(this.checked); });

    function refresh() {
        const start = $('#filterStart').val(), end = $('#filterEnd').val();
        const zone = $('#filterZone').val(), shift = $('#filterShift').val();
        const invalid = Boolean(start && end && end < start);
        $('#filterError').toggleClass('d-none', !invalid);
        table.clear().rows.add(invalid ? [] : records.filter(row => {
            const group = groupById(row.groupId);
            return (!start || row.date >= start) && (!end || row.date <= end) && (!zone || group.zone === zone) && (!shift || group.shift === shift);
        })).draw();
    }
    $('#programmingFilters').on('submit', event => event.preventDefault()).on('change', refresh).on('reset', () => {
        setTimeout(() => { table.search(''); refresh(); }, 0);
    });

    function status(kind, content) {
        $('#programValidation').attr('class', `alert alert-${kind} mt-3 mb-0`).html(content);
    }
    function invalidate() {
        validated = false;
        $('#saveProgramming').prop('disabled', true);
        status('info', 'Los datos han cambiado. Valide la disponibilidad nuevamente.');
    }
    function showGroup() {
        const group = groupById(fields.group.val());
        const summary = document.getElementById('programGroupSummary');
        summary.hidden = !group;
        if (!group) return;
        summary.innerHTML = [['Grupo', group.name], ['Zona', group.zone], ['Turno', `${group.shift} (${group.hours})`], ['Vehículo', `${group.vehicle} · ${group.plate}`]].map(([label, value]) => `<div><small>${label}</small><strong>${escape(value)}</strong></div>`).join('');
    }
    fields.group.on('change', () => {
        const group = groupById(fields.group.val());
        fields.driver.val(group?.driver || '');
        fields.helper1.val(group?.helpers[0] || '');
        fields.helper2.val(group?.helpers[1] || '');
        form.querySelectorAll('[name="workdays"]').forEach(input => { input.checked = Boolean(group?.days.includes(Number(input.value))); });
        showGroup();
    });
    $('#programmingForm').on('input change', 'input, select, textarea', invalidate);
    fields.start.on('change', () => fields.end.attr('min', fields.start.val()));

    function openForm(row = null) {
        form.reset();
        fields.end.removeAttr('min');
        editingId = row?.id ?? null;
        $('#programmingFormTitle').text(row ? 'Modificar programación' : 'Nueva programación');
        if (row) {
            fields.start.val(row.date); fields.end.val(row.date).attr('min', row.date);
            fields.group.val(row.groupId); fields.driver.val(row.driver);
            fields.helper1.val(row.helpers[0]); fields.helper2.val(row.helpers[1] || ''); fields.notes.val(row.notes);
            const day = new Date(`${row.date}T00:00:00Z`).getUTCDay();
            form.querySelectorAll('[name="workdays"]').forEach(input => { input.checked = Number(input.value) === day; });
        }
        // Editing updates one listed occurrence; creating can generate several.
        fields.end.prop('readOnly', Boolean(row));
        fields.start.off('change.edit').on('change.edit', () => { if (editingId !== null) fields.end.val(fields.start.val()); });
        fields.group.trigger('change.select2');
        showGroup(); invalidate();
        status('info', 'Seleccione las fechas, el grupo y el personal. La disponibilidad se simula con datos de ejemplo.');
        formModal.show();
    }
    $('#newProgramming').on('click', () => openForm());
    $('#bulkProgramming').on('click', () => Swal.fire({ icon: 'info', title: 'Programación masiva', text: 'Esta pantalla está pendiente de definir. Por ahora puede crear programaciones individuales.', confirmButtonText: 'Entendido' }));

    function workDates() {
        const days = Array.from(form.querySelectorAll('[name="workdays"]:checked'), input => Number(input.value));
        const result = [];
        const cursor = new Date(`${fields.start.val()}T00:00:00Z`), last = new Date(`${fields.end.val()}T00:00:00Z`);
        // Keep a sample interaction bounded to one year.
        for (let count = 0; cursor <= last && count < 366; count++, cursor.setUTCDate(cursor.getUTCDate() + 1)) {
            if (days.includes(cursor.getUTCDay())) result.push(cursor.toISOString().slice(0, 10));
        }
        return result;
    }
    function validate() {
        validated = false; $('#saveProgramming').prop('disabled', true);
        if (!groupById(fields.group.val())) {
            status('danger', 'Seleccione un grupo de personal antes de validar.');
            fields.group.select2('open');
            return false;
        }
        if (!form.reportValidity()) return false;
        const errors = [], start = fields.start.val(), end = fields.end.val();
        if (end < start) errors.push('La fecha de fin no puede ser anterior a la fecha de inicio.');
        if ((new Date(`${end}T00:00:00Z`) - new Date(`${start}T00:00:00Z`)) / 86400000 > 365) errors.push('En esta demostración, seleccione un período máximo de 366 días.');
        const dates = workDates(), helpers = [fields.helper1.val(), fields.helper2.val()].filter(Boolean);
        if (!dates.length) errors.push('Seleccione al menos un día de trabajo dentro del período.');
        if (helpers.length !== new Set(helpers).size) errors.push('Los ayudantes deben ser personas distintas.');
        const selected = [fields.driver.val(), ...helpers], group = groupById(fields.group.val());
        unavailable.forEach(rule => {
            if (selected.includes(rule.person) && dates.some(date => date >= rule.start && date <= rule.end)) {
                errors.push(`${personName(rule.person)}: ${rule.reason} del ${formatDate(rule.start)} al ${formatDate(rule.end)}. Sugerencia: reemplazar por ${rule.replacement}.`);
            }
        });
        const conflict = records.some(row => row.id !== editingId && dates.includes(row.date) && (
            groupById(row.groupId).shift === group.shift && (groupById(row.groupId).vehicle === group.vehicle || selected.some(id => [row.driver, ...row.helpers].includes(id)))
        ));
        if (conflict) errors.push('El vehículo o el personal ya tiene una programación en el mismo turno y fecha. Cambie el grupo, personal o fechas.');
        if (errors.length) { status('danger', `<strong>Hay errores que corregir</strong><ul class="mb-0 mt-2">${errors.map(error => `<li>${escape(error)}</li>`).join('')}</ul>`); return false; }
        validated = true; $('#saveProgramming').prop('disabled', false);
        status('success', `<i class="bi bi-check-circle me-1"></i>Validación de ejemplo correcta. Puede guardar ${dates.length} programación(es) en esta vista.`);
        return true;
    }
    $('#validateProgramming').on('click', validate);
    $('#programmingForm').on('submit', event => {
        event.preventDefault();
        if (!validated || !validate()) return;
        const values = { groupId: fields.group.val(), driver: fields.driver.val(), helpers: [fields.helper1.val(), fields.helper2.val()].filter(Boolean), notes: fields.notes.val().trim() };
        if (editingId !== null) {
            const row = records.find(record => record.id === editingId);
            Object.assign(row, values, { date: fields.start.val() });
            row.history.push('Programación modificada en esta sesión de demostración.');
        } else {
            workDates().forEach(date => records.push({ ...values, helpers: [...values.helpers], id: nextId++, date, status: 'Programado', history: ['Programación creada en esta sesión de demostración.'] }));
        }
        formModal.hide(); refresh();
        Swal.fire({ icon: 'success', title: 'Vista actualizada', text: 'Los cambios son temporales y se perderán al recargar.', confirmButtonText: 'Entendido' });
    });

    $('#programmingTable').on('click', '[data-action]', async function () {
        const row = records.find(record => record.id === Number(this.closest('[data-id]').dataset.id));
        if (!row) return;
        const action = this.dataset.action;
        if (action === 'edit' && row.status === 'Programado') return openForm(row);
        if (action === 'detail' || action === 'history') {
            $('#programmingDetailTitle').text(action === 'history' ? 'Historial de cambios' : 'Detalle de programación');
            const group = groupById(row.groupId);
            const entries = [['Fecha', formatDate(row.date)], ['Estado', row.status], ['Grupo', group.name], ['Zona', group.zone], ['Turno', `${group.shift} (${group.hours})`], ['Vehículo', `${group.vehicle} · ${group.plate}`], ['Conductor', personName(row.driver)], ['Ayudantes', row.helpers.map(personName).join(', ')], ['Observaciones', row.notes || 'Sin observaciones']];
            $('#programmingDetailBody').html(action === 'history' ? `<ol>${row.history.map(item => `<li>${escape(item)}</li>`).join('')}</ol>` : `<div class="row g-3">${entries.map(([label, value]) => `<div class="col-12 col-md-6"><span class="programming-detail-label">${label}</span><span class="text-break">${escape(value)}</span></div>`).join('')}</div>`);
            return detailModal.show();
        }
        if (!['delete', 'finish'].includes(action) || row.status !== 'Programado') return;
        const result = await Swal.fire({ icon: 'question', title: action === 'delete' ? '¿Eliminar programación?' : '¿Finalizar programación?', text: 'Esta acción solo modifica los datos de ejemplo de la sesión actual.', showCancelButton: true, confirmButtonText: action === 'delete' ? 'Eliminar' : 'Finalizar', cancelButtonText: 'Cancelar' });
        if (!result.isConfirmed) return;
        if (action === 'delete') records = records.filter(record => record.id !== row.id);
        else { row.status = 'Finalizado'; row.history.push('Programación finalizada en esta sesión de demostración.'); }
        refresh();
    });
});
