/* Interactions only: groups, people, availability and routes come from Laravel. */
$(function () {
    'use strict';
    const base = $('.programming-module').data('url');
    const form = document.getElementById('programmingForm');
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('programmingFormModal'));
    const detail = bootstrap.Modal.getOrCreateInstance(document.getElementById('programmingDetailModal'));
    const modificationsModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('programmingModificationsModal'));
    const csrf = $(form).find('[name="_token"]').val();
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const dateLabel = value => String(value || '').slice(0, 10).split('-').reverse().join('/');
    function todayInLima() {
        const parts = new Intl.DateTimeFormat('en', {timeZone:'America/Lima',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());
        const values = Object.fromEntries(parts.map(part => [part.type,part.value]));
        return `${values.year}-${values.month}-${values.day}`;
    }
    let editing = null, validationToken = null, revision = 0, groupRequest = null;
    let checking = false, saving = false;
    let holidayRequest = null, holidayRevision = 0, holidaysReady = false;
    let peopleRequest = null, peopleRevision = 0, peopleReady = false;
    let preferredPeople = {driver: '', helpers: []}, preferredVehicle = '', currentGroupCapacity = 1;
    let modificationId = null, modificationCurrent = null, modificationHistory = [], modificationOptions = null;

    function request(method, path, data = {}) {
        return $.ajax({url: base + path, method, data: method === 'GET' ? data : JSON.stringify(data),
            contentType: 'application/json', headers: {'X-CSRF-TOKEN': csrf, Accept: 'application/json'}});
    }
    function errors(xhr) {
        const response = xhr.responseJSON || {};
        const messages = xhr.status >= 500 ? 'No se pudo completar la operación. Intente nuevamente.' : response.errors ? Object.values(response.errors).flat().join('\n') : response.message || 'No se pudo completar la operación. Intente nuevamente.';
        return Swal.fire({icon:'error',title:'Revise los datos',text:messages,confirmButtonText:'Entendido'});
    }
    function status(kind, html) { $('#programValidation').attr('class', 'alert alert-' + kind + ' mt-3 mb-0').html(html); }
    function invalidate() {
        revision++; validationToken = null;
        $('#saveProgramming').prop('disabled', true);
        status('info','Los datos han cambiado. Valide la disponibilidad nuevamente.');
    }
    function button(action, icon, label, style = 'outline-primary') {
        return `<button type="button" class="btn btn-${style}  ${action === 'detail' ? 'flex-grow-1' : ''} table-action-button" data-action="${action}" title="${label}" aria-label="${label}"><i class="bi bi-${icon}" aria-hidden="true"></i></button>`;
    }
    const table = $('#programmingTable').DataTable({
        processing:true, order:[[0,'asc']], pageLength:10,
        ajax:{url:base, data:d => {
            d.start_date = $('#filterStart').val(); d.end_date = $('#filterEnd').val();
            d.zone_id = $('#filterZone').val(); d.shift_id = $('#filterShift').val();
        }, error:errors},
        columns:[
            {data:'date',render:(v,t) => ['display','export'].includes(t) ? dateLabel(v) : v},
            {data:'status',render:(v,t) => {
                const statusClass = v === 'Anulado' ? 'programming-status--annulled'
                    : v === 'Finalizado' ? 'programming-status--finished'
                        : v === 'Reprogramado' ? 'programming-status--rescheduled'
                            : v === 'Programado' ? 'programming-status--scheduled' : 'programming-status--default';
                return t === 'display' ? `<span class="programming-status ${statusClass}">${escape(v)}</span>` : v;
            }},
            {data:'zone',render:$.fn.dataTable.render.text()},
            {data:'shift',render:(v,t) => t === 'display' ? `<span class="badge bg-warning-subtle text-warning-emphasis">${escape(v)}</span>` : v},
            {data:'vehicle',render:(v,t,row) => t === 'display' ? `<span class="programming-vehicle">${escape(v)}<small>${escape(row.plate)}</small></span>` : `${v} ${row.plate}`},
            {data:'driver',render:$.fn.dataTable.render.text()},
            {data:'helpers',render:(v,t) => t === 'display' ? v.map(name => `<span class="programming-helper">${escape(name)}</span>`).join('') : v.join(', ')},
            { data: null, orderable: false, searchable: false, render: row => `<div class="programming-actions table-action-buttons d-flex justify-content-center" data-id="${row.id}">${['Programado','Reprogramado'].includes(row.status) ? button('edit', 'pencil-square', 'Modificar programación', 'outline-warning') + button('annul','ban','Anular programación','outline-danger') + button('group','people','Ver grupo asociado','outline-primary') + button('finish','check-circle','Aceptar y finalizar recorrido','outline-success') : button('detail','eye','Ver detalle','outline-primary')}</div>`}
        ],
        language:{search:'Buscar:',lengthMenu:'Mostrar _MENU_ registros',info:'Mostrando _START_ a _END_ de _TOTAL_ registros',infoEmpty:'Sin registros',infoFiltered:'(filtrado de _MAX_ registros)',zeroRecords:'No se encontraron programaciones',emptyTable:'No hay programaciones para las fechas seleccionadas',paginate:{first:'Primero',previous:'Anterior',next:'Siguiente',last:'Último'}}
    });
    const exportOptions = {columns:index => index !== 7 && table.column(index).visible(),orthogonal:'export',modifier:{search:'applied',order:'applied',selected:null}};
    function reportMetadata() {
        const rows = table.rows({search:'applied'}).data().toArray();
        const filters = [`Período: ${$('#filterStart').val() ? dateLabel($('#filterStart').val()) : 'Sin inicio'} - ${$('#filterEnd').val() ? dateLabel($('#filterEnd').val()) : 'Sin fin'}`,
            'Zona: ' + $('#filterZone option:selected').text(), 'Turno: ' + $('#filterShift option:selected').text()];
        if (table.search()) filters.push('Búsqueda: ' + table.search());
        return {issued:new Intl.DateTimeFormat('es-PE',{dateStyle:'short',timeStyle:'short',timeZone:'America/Lima'}).format(new Date()),count:rows.length,
            scheduled:rows.filter(r => ['Programado','Reprogramado'].includes(r.status)).length,finished:rows.filter(r => r.status === 'Finalizado').length,filters};
    }
    new $.fn.dataTable.Buttons(table,{buttons:[
        {extend:'excelHtml5',text:'<i class="bi bi-file-earmark-excel me-1"></i>Excel',title:'Programaciones | SGRS JLO',exportOptions},
        {extend:'pdfHtml5',text:'<i class="bi bi-file-earmark-pdf me-1"></i>PDF',title:'Programaciones',filename:'SGRS-JLO-programaciones',orientation:'landscape',pageSize:'A4',exportOptions,customize:doc => window.ProgrammingReports.customizePdf(doc,reportMetadata())},
        {extend:'print',text:'<i class="bi bi-printer me-1"></i>Imprimir',title:'Programaciones',exportOptions,customize:win => window.ProgrammingReports.customizePrint(win,reportMetadata(),table.buttons.exportData(exportOptions))}
    ]}).container().appendTo('#programmingExports');
    table.columns().every(function(index) {
        if (index !== 7) $('#programmingColumns').append(`<label class="form-check mb-2"><input type="checkbox" class="form-check-input" data-column="${index}" checked><span class="form-check-label">${escape(this.header().textContent)}</span></label>`);
    });
    $('#programmingColumns').on('change','input',function(){
        const visible = table.columns().indexes().toArray().filter(index => index !== 7 && table.column(index).visible()).length;
        if(!this.checked && visible === 1){this.checked=true;return;}
        table.column(Number(this.dataset.column)).visible(this.checked);
    });
    $('#programmingTable').on('preXhr.dt',() => table.buttons().enable(false)).on('xhr.dt',() => table.buttons().enable(true));
    function reload() {
        const invalid = $('#filterStart').val() && $('#filterEnd').val() && $('#filterEnd').val() < $('#filterStart').val();
        $('#filterError').toggleClass('d-none',!invalid);
        if (invalid) table.clear().draw();
        else table.ajax.reload();
    }
    $('#programmingFilters').on('submit',event => event.preventDefault()).on('change',reload).on('reset',() => setTimeout(() => {table.search('');reload();},0));
    $('#programGroup').select2({theme:'bootstrap-5',width:'100%',dropdownParent:$('#programmingFormModal'),placeholder:'Busque por nombre, zona o turno',allowClear:true});
    $('#programmingModificationsModal select').select2({
        theme:'bootstrap-5',width:'100%',dropdownParent:$('#programmingModificationsModal'),allowClear:true
    });
    $('#modPeopleRole').select2('destroy').select2({
        theme:'bootstrap-5',width:'100%',dropdownParent:$('#programmingModificationsModal'),
        minimumResultsForSearch:Infinity
    });

    function helpers(capacity, ids = []) {
        const container = document.getElementById('programHelpers'); container.replaceChildren();
        for (let index = 0; index < Math.max(0,capacity - 1); index++) {
            const node = document.getElementById('programHelperTemplate').content.cloneNode(true);
            const select = node.querySelector('select'), label = node.querySelector('label');
            select.id = 'programHelper' + index; label.htmlFor = select.id; label.textContent = 'Ayudante ' + (index + 1);
            select.value = ids[index] || ''; container.appendChild(node);
        }
        syncHelperOptions();
    }
    function syncHelperOptions() {
        const selected = $('.programming-helper-select').map((i, element) => element.value).get().filter(Boolean);
        $('.programming-helper-select').each(function () {
            const current = this.value;
            Array.from(this.options).forEach(option => {
                if (option.value) option.disabled = selected.includes(option.value) && option.value !== current;
            });
        });
    }
    function resetPeople(message) {
        if (peopleReady) {
            preferredPeople = {driver: $('#programDriver').val(), helpers: $('.programming-helper-select').map((i, el) => el.value).get()};
            preferredVehicle = $('#programVehicle').val() || preferredVehicle;
        }
        peopleReady = false; peopleRevision++;
        if (peopleRequest) peopleRequest.abort();
        peopleRequest = null;
        $('#programVehicle, #programDriver, .programming-helper-select').each(function () {
            this.replaceChildren(new Option(message, '')); this.disabled = true;
        });
        $('#programPeopleNote').text(message);
    }
    function loadPeople() {
        resetPeople('Consultando personal disponible…');
        const data = payload(), current = peopleRevision;
        if (!holidaysReady || !data.staff_group_id || !data.days.length) return;
        peopleRequest = request('GET', '/people', data).done(result => {
            if (current !== peopleRevision) return;
            let cleared = false;
            function fill(select, options, selected, placeholder) {
                select.replaceChildren(new Option(placeholder, ''));
                options.forEach(person => select.add(new Option(person.name, person.id)));
                const eligible = options.some(person => String(person.id) === String(selected));
                select.value = eligible ? selected : '';
                if (selected && !eligible) cleared = true;
                select.disabled = false;
            }
            const vehicleSelect = document.getElementById('programVehicle');
            vehicleSelect.replaceChildren(new Option('Seleccione un vehículo disponible', ''));
            result.vehicles.forEach(vehicle => {
                const option = new Option([vehicle.code, vehicle.name, vehicle.plate].filter(Boolean).join(' \u00b7 '), vehicle.id);
                option.dataset.capacity = vehicle.capacity;
                vehicleSelect.add(option);
            });
            const selectedVehicleAvailable = result.vehicles.some(vehicle => String(vehicle.id) === String(preferredVehicle));
            vehicleSelect.value = selectedVehicleAvailable ? preferredVehicle : '';
            vehicleSelect.disabled = false;
            if (preferredVehicle && !selectedVehicleAvailable) cleared = true;
            const selectedVehicle = result.vehicles.find(vehicle => String(vehicle.id) === String(vehicleSelect.value));
            const selectedCapacity = selectedVehicle ? Number(selectedVehicle.capacity) : currentGroupCapacity;
            if (preferredPeople.helpers.slice(Math.max(0, selectedCapacity - 1)).some(Boolean)) cleared = true;
            helpers(selectedCapacity, preferredPeople.helpers);
            fill(document.getElementById('programDriver'), result.drivers, preferredPeople.driver, 'Seleccione un conductor');
            $('.programming-helper-select').each(function (index) {
                fill(this, result.helpers, preferredPeople.helpers[index], 'Sin asignar');
            });
            syncHelperOptions();
            peopleReady = true;
            invalidate();
            $('#programPeopleNote').text(!result.dates.length ? 'No hay fechas nuevas para programar. Revise los días y feriados seleccionados.'
                : (cleared ? 'Se quitaron selecciones que no están disponibles. ' : '')
                + `Personal disponible para todos los días a programar: ${result.drivers.length} conductor(es) y ${result.helpers.length} ayudante(s).`);
        }).fail(xhr => {
            if (current !== peopleRevision || xhr.statusText === 'abort') return;
            $('#programPeopleNote').text('No se pudo consultar el personal disponible.');
            $('#programPeopleNote').append('<button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="retryProgrammingPeople">Reintentar</button>');
            errors(xhr);
        }).always(() => {if (current === peopleRevision) peopleRequest = null;});
    }
    $('#programPeopleNote').on('click', '#retryProgrammingPeople', loadPeople);
    $(form).on('change', '.programming-work-holiday', loadPeople);
    $(form).on('change', '.programming-helper-select', syncHelperOptions);
    function loadHolidays(selected = null) {
        resetPeople('Seleccione un grupo, un período válido y días de trabajo.');
        const chosen = selected || $('.programming-work-holiday:checked').map((i, el) => el.value).get();
        const current = ++holidayRevision;
        if (holidayRequest) holidayRequest.abort();
        holidayRequest = null; holidaysReady = false;
        const start = $('#programStart').val(), end = $('#programEnd').val();
        const days = $(form).find('[name="workdays"]:checked').map((i, el) => el.value).get();
        $('#programHolidayOptions').text('Seleccione un período válido y los días de trabajo.');
        if (!start || !end || start < todayInLima() || end < start || !days.length) return;
        $('#programHolidayOptions').text('Consultando feriados…');
        holidayRequest = request('GET', '/holidays', {start_date:start,end_date:end,days}).done(result => {
            if (current !== holidayRevision) return;
            $('#programHolidayOptions').html(result.data.length ? result.data.map(holiday =>
                `<div class="form-check mb-2"><input type="checkbox" class="form-check-input programming-work-holiday" id="workHoliday-${escape(holiday.date)}" value="${escape(holiday.date)}" ${chosen.includes(holiday.date) ? 'checked' : ''}><label class="form-check-label" for="workHoliday-${escape(holiday.date)}"><strong>${escape(dateLabel(holiday.date))}</strong> · ${holiday.names.map(escape).join(', ')} — Trabajar este día</label></div>`
            ).join('') : 'No hay feriados activos en los días seleccionados.');
            holidaysReady = true;
            invalidate();
            loadPeople();
        }).fail(xhr => {
            if (current !== holidayRevision || xhr.statusText === 'abort') return;
            $('#programHolidayOptions').text('No se pudieron consultar los feriados.');
            $('#programHolidayOptions').append('<button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="retryProgrammingHolidays">Reintentar</button>');
            errors(xhr);
        }).always(() => {if (current === holidayRevision) holidayRequest = null;});
    }
    $('#programHolidayOptions').on('click', '#retryProgrammingHolidays', () => loadHolidays());
    $('#programEnd').on('change', () => loadHolidays());
    $(form).on('change', '[name="workdays"]', () => loadHolidays());
    function syncEditingDay() {
        document.getElementById('programStart').setCustomValidity('');
        if (!editing || !$('#programStart').val()) return;
        const weekday = new Date($('#programStart').val() + 'T00:00:00Z').getUTCDay() || 7;
        form.querySelectorAll('[name="workdays"]').forEach(input => {input.checked = Number(input.value) === weekday;});
    }
    function loadGroup(id, row = null) {
        if (groupRequest) groupRequest.abort();
        resetPeople('Seleccione un grupo, un período válido y días de trabajo.');
        preferredPeople = {driver: '', helpers: []};
        holidayRevision++;
        if (holidayRequest) holidayRequest.abort();
        holidayRequest = null; holidaysReady = false;
        form.querySelectorAll('[name="workdays"]').forEach(input => {input.checked = false;});
        document.getElementById('programStart').setCustomValidity('');
        $('#programGroupSummary').prop('hidden',true); helpers(1);
        if (!id) {$('#programDriver').val('');return;}
        $('#validateProgramming').prop('disabled',true);
        groupRequest = request('GET','/groups/' + id).done(group => {
            if (String($('#programGroup').val()) !== String(id)) return;
            currentGroupCapacity = Number(group.capacity || 1);
            preferredVehicle = row ? row.vehicle_id : group.vehicle_id;
            $('#programGroupSummary').html([['Grupo',group.name],['Zona',group.zone],['Turno',`${group.shift} (${group.hours})`],['Vehículo del grupo',`${group.vehicle} · ${group.plate}`]].map(([label,value]) => `<div><small>${label}</small><strong>${escape(value)}</strong></div>`).join('')).prop('hidden',false);
            preferredPeople = {driver: row ? row.driver_id : group.driver_id, helpers: row ? row.helper_ids : group.helpers};
            helpers(group.capacity,row ? row.helper_ids : group.helpers);
            $('.programming-helper-select').prop('disabled', true);
            const days = (row ? row.days : group.days).map(Number);
            form.querySelectorAll('[name="workdays"]').forEach(input => {input.checked = days.includes(Number(input.value));});
            const dayNames = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
            $('#programGroupDaysNote').text('Días del grupo: ' + group.days.map(Number).sort((a,b) => a-b).map(day => dayNames[day-1]).join(', ') + '. Puede cambiar los días para esta programación sin modificar el grupo.');
            syncEditingDay();
            invalidate();
            loadHolidays(row ? row.work_holidays : []);
        }).fail(xhr => {if(xhr.statusText !== 'abort') errors(xhr);}).always(() => $('#validateProgramming').prop('disabled',false));
    }
    $('#programGroup').on('change',function(){loadGroup(this.value);});
    $('#programVehicle').on('change',function(){
        if (!peopleReady) return;
        preferredPeople = {driver: $('#programDriver').val(), helpers: $('.programming-helper-select').map((i, element) => element.value).get()};
        preferredVehicle = this.value;
        peopleReady = false;
        loadPeople();
    });
    $('#programmingForm').on('input change','input,select,textarea',invalidate);
    $('#programStart').on('change',function(){
        $('#programEnd').attr('min',this.value > todayInLima() ? this.value : todayInLima());
        if(editing) {$('#programEnd').val(this.value);syncEditingDay();}
        loadHolidays();
    });
    function openForm(row = null) {
        form.reset(); editing = row ? row.id : null; preferredVehicle = ''; currentGroupCapacity = 1;
        $('#programGroupDaysNote').text('Seleccione un grupo para cargar sus días de trabajo como selección inicial.');
        loadHolidays([]);
        $('#programStart, #programEnd').attr('min',todayInLima());
        $('#programEnd').prop('readOnly',Boolean(editing));
        $('#programmingFormTitle').text(editing ? 'Modificar programación' : 'Nueva programación');
        $('#programGroup').val(row ? row.group_id : '').trigger('change.select2');
        if(row){$('#programStart').val(row.date);$('#programEnd').val(row.date).attr('min',row.date > todayInLima() ? row.date : todayInLima());$('#programNotes').val(row.notes);}
        invalidate(); loadGroup($('#programGroup').val(),row); modal.show();
        status('info','Revise los datos del grupo y valide la disponibilidad antes de guardar.');
    }
    $('#newProgramming').on('click',() => openForm());
    $('#programmingFormModal').on('hidden.bs.modal',() => {if(groupRequest) groupRequest.abort();holidayRevision++;if(holidayRequest) holidayRequest.abort();holidayRequest=null;holidaysReady=false;resetPeople('Seleccione un grupo y un período.');invalidate();});
    function payload() {
        return {staff_group_id:$('#programGroup').val(),vehicle_id:$('#programVehicle').val(),driver_id:$('#programDriver').val(),helpers:$('.programming-helper-select').map((i,el) => el.value).get().filter(Boolean),
            start_date:$('#programStart').val(),end_date:$('#programEnd').val(),days:$(form).find('[name="workdays"]:checked').map((i,el) => el.value).get(),notes:$('#programNotes').val(),programming_id:editing,
            work_holidays:$('.programming-work-holiday:checked').map((i,el) => el.value).get()};
    }
    function omitted(result) {
        return [['Feriados omitidos',result.omitted_holidays],['Feriados habilitados para trabajar',result.included_holidays || []],['Fechas ya programadas',result.omitted_duplicates]].filter(([,dates]) => dates.length).map(([label,dates]) => `<div class="mt-2">${label}: ${dates.slice(0,8).map(dateLabel).join(', ')}${dates.length > 8 ? ' y ' + (dates.length - 8) + ' más' : ''}.</div>`).join('');
    }
    $('#validateProgramming').on('click',function(){
        if(checking || saving) return;
        if(!$('#programGroup').val()){status('danger','Seleccione un grupo de personal.');return;}
        if (!peopleReady || peopleRequest) {status('info','Espere la consulta de personal disponible o vuelva a intentarla antes de validar.');return;}
        if(!form.reportValidity()) return;
        if(!holidaysReady || holidayRequest){status('info','Espere la consulta de feriados o vuelva a intentarla antes de validar.');return;}
        invalidate(); const current = revision; checking = true; $(this).prop('disabled',true);
        request('POST','/check',payload()).done(result => {
            if(current !== revision) return;
            if(!result.count && !result.issues.length){
                const message = result.omitted_holidays.length && !result.omitted_duplicates.length
                    ? '<strong>La fecha seleccionada es feriado y todavía no está habilitada.</strong><div>Marque “Trabajar este día” en Feriados del período y vuelva a validar la disponibilidad.</div>'
                    : '<strong>No se puede guardar: no hay fechas nuevas para generar.</strong><div>Las fechas elegidas ya están programadas o corresponden a días omitidos. Cambie el período o los días de trabajo.</div>';
                status('warning',message + omitted(result));
            } else if(!result.valid){
                const issues = result.issues.map(issue => `<li>${escape(issue.person)}: ${escape(issue.reason)} (${issue.dates.slice(0,5).map(dateLabel).join(', ')}${issue.dates.length > 5 ? ' y más fechas' : ''}).${(issue.routes || []).map(route => '<div class="small">Recorrido #' + escape(route.id) + ' · ' + escape(route.group) + ' · ' + escape(route.shift) + (route.holiday_authorized ? ' · Feriado habilitado' + (route.authorized_by ? ' por ' + escape(route.authorized_by) : '') : '') + '</div>').join('')}${(result.suggestions[issue.employee_id] || []).length ? '<br>Sugerencias disponibles: ' + result.suggestions[issue.employee_id].map(p => escape(p.name)).join(', ') : ''}</li>`).join('');
                status('danger','<strong>Hay inconsistencias que corregir</strong><ul class="mt-2 mb-0">' + issues + '</ul>' + omitted(result));
            } else {
                validationToken = result.validation_token;
                $('#saveProgramming').prop('disabled',result.count === 0);
                status(result.count ? 'success' : 'info',(result.count ? `Disponibilidad correcta. Se guardarán ${result.count} recorrido(s).` : 'No hay fechas nuevas para generar.') + omitted(result));
            }
        }).fail(xhr => {if(current === revision){status('danger','No se pudo validar. Revise los datos y vuelva a intentar.');errors(xhr);}}).always(() => {checking=false;$('#validateProgramming').prop('disabled',false);});
    });
    $('#programmingForm').on('submit',function(event){
        event.preventDefault(); if(!validationToken || saving) return;
        saving=true; $('#saveProgramming').prop('disabled',true);
        $(form).find('input,select,textarea,button').prop('disabled',true);
        const data = payload(); // Read values even while disabled; no form serialization.
        request(editing ? 'PUT' : 'POST',editing ? '/' + editing : '',{...data,validation_token:validationToken}).done(result => {
            modal.hide();
            // Show the saved period even when the initial list was limited to today.
            $('#filterStart').val(data.start_date); $('#filterEnd').val(data.end_date);
            $('#filterZone, #filterShift').val(''); table.search(''); table.ajax.reload();
            Swal.fire({icon:'success',title:'Proceso exitoso',text:result.message,confirmButtonText:'Entendido'});
        }).fail(xhr => {invalidate();errors(xhr);}).always(() => {saving=false;$(form).find('input,select,textarea,button').prop('disabled',false);$('#saveProgramming').prop('disabled',true);});
    });
    function auditValue(value) {
        if (value == null) return '—';
        if (typeof value !== 'object') return String(value);
        if (value.value != null) return String(value.value);
        const snapshot = value.snapshot || {};
        const parts = [
            value.status ? `Estado: ${value.status}` : '',
            snapshot.shift || value.shift ? `Turno: ${snapshot.shift || value.shift}` : '',
            snapshot.vehicle || value.vehicle ? `Vehículo: ${snapshot.vehicle || value.vehicle}${snapshot.plate || value.plate ? ` · ${snapshot.plate || value.plate}` : ''}` : '',
            snapshot.driver || value.driver ? `Conductor: ${snapshot.driver || value.driver}` : '',
        ].filter(Boolean);
        return parts.join(' | ') || '—';
    }
    function renderDetailHistory(history) {
        if (!history.length) return '<tr><td colspan="6" class="text-center text-body-secondary py-3">Esta programación todavía no tiene cambios registrados.</td></tr>';
        return history.map(change => `<tr>
            <td>${escape(change.at)}</td><td>${escape(change.action)}</td>
            <td>${escape(auditValue(change.before))}</td><td>${escape(auditValue(change.after))}</td>
            <td>${escape(change.after?.reason ? modificationReasonLabel(change.after) : '—')}</td><td>${escape(change.user)}</td>
        </tr>`).join('');
    }
    function details(row, history) {
        const helpers = Array.isArray(row.helpers) ? row.helpers : [];
        const statusClass = row.status === 'Anulado' ? 'programming-status--annulled'
            : row.status === 'Finalizado' ? 'programming-status--finished'
                : row.status === 'Reprogramado' ? 'programming-status--rescheduled'
                    : row.status === 'Programado' ? 'programming-status--scheduled' : 'programming-status--default';
        const facts = [
            ['Fecha',dateLabel(row.date)], ['Estado',`<span class="programming-status ${statusClass}">${escape(row.status)}</span>`],
            ['Grupo',row.group], ['Zona',row.zone], ['Turno',`${row.shift} · ${row.hours}`],
            ['Vehículo',`${row.vehicle} · ${row.plate}`], ['Observaciones',row.notes || 'Sin observaciones'],
            ['Finalización',row.finished_at || 'Pendiente'],
            ...(row.holiday_work ? [['Trabajo en feriado',`${row.holiday_work.names.join(', ')} · Autorizado el ${dateLabel(row.holiday_work.authorized_at)}`]] : []),
        ];
        return `<div class="programming-detail-content">
            <div class="programming-detail-layout">
                <section class="programming-detail-panel" aria-label="Información de la programación">
                    <h6 class="programming-detail-heading"><i class="bi bi-calendar2-check me-2" aria-hidden="true"></i>Datos del recorrido</h6>
                    <div class="programming-detail-facts">${facts.map(([label,value]) => `<div class="programming-detail-fact"><span>${escape(label)}</span><strong>${label === 'Estado' ? value : escape(value)}</strong></div>`).join('')}</div>
                </section>
                <section class="programming-detail-panel" aria-label="Personal asociado">
                    <h6 class="programming-detail-heading"><i class="bi bi-people me-2" aria-hidden="true"></i>Equipo de trabajo <span class="programming-detail-count">${helpers.length + (row.driver ? 1 : 0)}</span></h6>
                    <div class="programming-detail-person"><span>Conductor</span><strong>${escape(row.driver || 'Sin conductor asignado')}</strong></div>
                    <div class="programming-detail-person"><span>Ayudantes (${helpers.length})</span>
                        ${helpers.length ? `<ul>${helpers.map(name => `<li>${escape(name)}</li>`).join('')}</ul>` : '<strong>Sin ayudantes asignados</strong>'}
                    </div>
                </section>
            </div>
            <section class="programming-detail-panel programming-detail-history" aria-label="Historial de cambios">
                <h6 class="programming-detail-heading"><i class="bi bi-clock-history me-2" aria-hidden="true"></i>Historial de cambios</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead><tr><th>Fecha y hora</th><th>Acción</th><th>Valor anterior</th><th>Valor nuevo</th><th>Motivo</th><th>Usuario</th></tr></thead>
                        <tbody>${renderDetailHistory(history)}</tbody>
                    </table>
                </div>
            </section>
        </div>`;
    }
    function modificationReasonLabel(change) {
        return change.reason === 'Otro' ? `Otro: ${change.custom_reason}` : change.reason;
    }
    function fillModificationSelect(selector, rows, placeholder, label, selected = '') {
        const options = rows.map(row => `<option value="${escape(row.id)}" ${String(row.id) === String(selected) ? 'selected' : ''}>${escape(label(row))}${row.available === false ? ' · No disponible' : ''}</option>`).join('');
        $(selector).html(`<option value="">${escape(placeholder)}</option>${options}`).val(selected).trigger('change.select2');
    }
    function refreshModificationHelperSelects() {
        const selectedCurrent = $('#modCurrentHelper').val() || '';
        const helpers = modificationCurrent.helper_people;
        fillModificationSelect('#modCurrentHelper',helpers,'Selecciona un ayudante actual',person => person.name,selectedCurrent);
    }
    function populateModificationOptions(options, selectedShift = '') {
        modificationOptions = options;
        const current = modificationCurrent;
        const selectedVehicle = $('#modNewVehicle').val() || '';
        const selectedDriver = $('#modNewDriver').val() || '';
        const selectedHelper = $('#modNewHelper').val() || '';
        const selectedReasons = ['#modShiftReason','#modVehicleReason','#modPeopleReason'].map(selector => $(selector).val());
        fillModificationSelect('#modNewShift',options.shifts,'Seleccione un turno',item => `${item.name} · ${String(item.start_time).slice(0,5)} - ${String(item.end_time).slice(0,5)}`,selectedShift);
        const currentCapacity = Number(options.vehicles.find(item => Number(item.id) === Number(current.vehicle_id))?.capacity);
        const replacementVehicles = options.vehicles.filter(item =>
            item.available
                && Number(item.capacity) === currentCapacity
                && Number(item.id) !== Number(current.vehicle_id)
        );
        fillModificationSelect('#modNewVehicle',replacementVehicles,'Seleccione un vehículo',item => `${item.code} · ${item.name} · ${item.plate}`,selectedVehicle);
        fillModificationSelect('#modNewDriver',options.drivers.filter(item => item.available && Number(item.id) !== Number(current.driver_id)),'Selecciona un conductor disponible',item => `${item.name} · DNI ${item.dni}`,selectedDriver);
        fillModificationSelect('#modNewHelper',options.helpers.filter(item => item.available && !current.helper_ids.map(Number).includes(Number(item.id))),'Selecciona un ayudante disponible',item => `${item.name} · DNI ${item.dni}`,selectedHelper);
        ['#modShiftReason','#modVehicleReason','#modPeopleReason'].forEach((selector,index) => {
            $(selector).html(`<option value="">Seleccione un motivo</option>${options.reasons.map(reason => `<option value="${escape(reason.name)}">${escape(reason.name)}</option>`).join('')}`).val(selectedReasons[index]).trigger('change');
        });
        updateVehicleCapacityWarning();
    }
    function renderModificationHistory() {
        $('#programmingModHistoryBody').html(modificationHistory.length ? modificationHistory.map(change => `<tr>
            <td>${escape(change.at)}</td><td>${escape(change.type)}</td><td>${escape(change.before?.value || '—')}</td><td>${escape(change.after?.value || '—')}</td>
            <td>${escape(modificationReasonLabel(change.after || {}))}</td><td>${escape(change.user)}</td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" data-modification-history="${escape(change.id)}" aria-label="Ver detalle del cambio"><i class="bi bi-eye" aria-hidden="true"></i></button></td>
        </tr>`).join('') : '<tr><td colspan="7" class="text-center text-body-secondary py-3">No hay modificaciones registradas.</td></tr>');
    }
    function openModificationModule(id) {
        modificationId = id;
        modificationCurrent = null;
        modificationHistory = [];
        $('#programmingModificationsSubtitle').text('Cargando datos de la programación…');
        $('#programmingModHistoryBody').html('<tr><td colspan="7" class="text-center text-body-secondary py-3">Cargando historial…</td></tr>');
        $('#modNewShift,#modNewVehicle,#modNewDriver,#modCurrentHelper,#modNewHelper').html('<option value="">Cargando…</option>').trigger('change');
        modificationsModal.show();
        refreshModificationData(true);
    }
    function refreshModificationData(resetInputs = false) {
        if (!modificationId) return;
        if (resetInputs) {
            $('#modNewShift,#modNewVehicle,#modNewDriver,#modCurrentHelper,#modNewHelper').val('').trigger('change');
            $('.programming-mod-reason').val('').trigger('change');
            $('#modShiftDescription,#modVehicleDescription,#modPeopleDescription,#modShiftCustomReason,#modVehicleCustomReason,#modPeopleCustomReason').val('');
            $('#modShiftCustomReason,#modVehicleCustomReason,#modPeopleCustomReason').addClass('d-none');
        }
        return request('GET','/' + modificationId + '/modifications').done(result => {
            modificationCurrent = result.current;
            modificationHistory = result.history;
            const current = modificationCurrent;
            $('#programmingModificationsSubtitle').text(`${current.group} · ${dateLabel(current.date)} · ${current.status}`);
            $('#modCurrentShift').val(`${current.shift} (${current.hours})`);
            $('#modCurrentVehicle').val(`${current.vehicle} · ${current.plate}`);
            $('#modCurrentDriverName').text(current.driver);
            refreshModificationHelperSelects();
            populateModificationOptions(result.options,$('#modNewShift').val());
            renderModificationHistory();
        }).fail(errors);
    }
    function refreshModificationOptions() {
        if (!modificationId || !modificationCurrent) return;
        const shiftId = $('#modNewShift').val() || modificationCurrent.shift_id;
        request('GET','/' + modificationId + '/modification-options',{shift_id:shiftId})
            .done(options => populateModificationOptions(options,shiftId)).fail(errors);
    }
    function updateVehicleCapacityWarning() {
        if (!modificationCurrent) return;
        const vehicleId = $('#modNewVehicle').val();
        const vehicle = modificationOptions?.vehicles.find(item => Number(item.id) === Number(vehicleId));
        const currentVehicle = modificationOptions?.vehicles.find(item => Number(item.id) === Number(modificationCurrent.vehicle_id));
        const capacityMismatch = Boolean(vehicle && currentVehicle && Number(vehicle.capacity) !== Number(currentVehicle.capacity));
        const replacementAvailable = modificationOptions?.vehicles.some(item =>
            item.available
                && Number(item.capacity) === Number(currentVehicle?.capacity)
                && Number(item.id) !== Number(modificationCurrent.vehicle_id)
        );
        const warning = $('#modVehicleCapacityWarning');
        if (capacityMismatch) {
            warning.removeClass('d-none').text(`No se puede aplicar el cambio: el vehículo elegido tiene una capacidad distinta. Selecciona uno de ${currentVehicle.capacity} ocupantes, igual que el vehículo actual.`);
        } else if (currentVehicle && !replacementAvailable) {
            warning.removeClass('d-none').text(`No hay vehículos sustitutos disponibles con la misma capacidad (${currentVehicle.capacity} ocupantes). No se puede aplicar el cambio hasta que haya uno disponible.`);
        } else {
            warning.addClass('d-none').text('');
        }
        $('[data-add-change="vehicle"]').prop('disabled',capacityMismatch || !replacementAvailable);
        const role = $('#modPeopleRole').val() || 'driver';
        $('#modPeopleDriverFields').toggleClass('d-none',role !== 'driver');
        $('#modPeopleHelperReplaceFields').toggleClass('d-none',role !== 'helper');
        $('#modPeopleSubmitButton')
            .attr('data-add-change',role)
            .html(`<i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aplicar cambio`);
    }
    function addModification(type) {
        const isPeople = ['driver','helper'].includes(type);
        const reasonSelector = isPeople ? '#modPeopleReason' : type === 'shift' ? '#modShiftReason' : '#modVehicleReason';
        const descriptionSelector = isPeople ? '#modPeopleDescription' : type === 'shift' ? '#modShiftDescription' : '#modVehicleDescription';
        const customSelector = isPeople ? '#modPeopleCustomReason' : type === 'shift' ? '#modShiftCustomReason' : '#modVehicleCustomReason';
        const reason = $(reasonSelector).val(), customReason = $(customSelector).val().trim(), description = $(descriptionSelector).val().trim();
        const selection = type === 'driver' ? $('#modNewDriver').val()
            : type === 'helper' ? $('#modCurrentHelper').val() && $('#modNewHelper').val()
                : type === 'shift' ? $('#modNewShift').val() : $('#modNewVehicle').val();
        if (!selection) {
            const message = type === 'helper' ? 'Selecciona el ayudante actual y luego el ayudante que lo reemplazará.'
                : type === 'driver' ? 'Selecciona el nuevo conductor.'
                    : type === 'vehicle' ? 'Selecciona un solo vehículo sustituto de la lista. Debe estar disponible y tener la misma capacidad de ocupantes que el vehículo actual.'
                        : 'Selecciona el nuevo turno antes de aplicar el cambio.';
            Swal.fire({icon:'warning',title:'Completa la selección',text:message});return;
        }
        if (!reason || (reason === 'Otro' && !customReason)) {
            Swal.fire({icon:'warning',title:'Complete los datos del cambio',text:'El motivo es obligatorio. Si elige “Otro”, especifique el motivo.'});return;
        }
        let change;
        if (type === 'shift' || type === 'vehicle') {
            const selector = type === 'shift' ? '#modNewShift' : '#modNewVehicle';
            const id = $(selector).val();
            const currentId = type === 'shift' ? modificationCurrent.shift_id : modificationCurrent.vehicle_id;
            if (Number(id) === Number(currentId)) {Swal.fire({icon:'warning',title:'Sin cambios',text:type === 'vehicle' ? 'Elige un vehículo distinto al actual para poder aplicar el cambio.' : 'El nuevo turno debe ser diferente del turno actual.'});return;}
            change = {type,new_id:id,reason,custom_reason:customReason,description};
        } else if (type === 'driver') {
            const driverId = $('#modNewDriver').val();
            if (Number(driverId) === Number(modificationCurrent.driver_id)) {Swal.fire({icon:'warning',title:'Sin cambios',text:'El nuevo conductor debe ser diferente del conductor actual.'});return;}
            change = {type:'driver',new_id:driverId,reason,custom_reason:customReason,description};
        } else if (type === 'helper') {
            const oldHelperId = $('#modCurrentHelper').val();
            const newHelperId = $('#modNewHelper').val();
            if (Number(oldHelperId) === Number(newHelperId)) {Swal.fire({icon:'warning',title:'Sin cambios',text:'El nuevo ayudante debe ser diferente del ayudante actual.'});return;}
            change = {type:'helper',old_id:oldHelperId,new_id:newHelperId,reason,custom_reason:customReason,description};
        }
        if (!change || !modificationId) return;
        const buttons = $('#programmingModificationsModal [data-add-change]');
        buttons.prop('disabled',true);
        let applied = false;
        request('PATCH','/' + modificationId + '/modifications',{changes:[change]})
            .done(response => {
                applied = true;
                table.ajax.reload(null,false);
                Swal.fire({icon:'success',title:'Cambio aplicado',text:response.message,confirmButtonText:'Entendido'});
                refreshModificationData(true).always(() => {
                    buttons.prop('disabled',false);
                    updateVehicleCapacityWarning();
                });
            })
            .fail(errors)
            .always(() => {
                if (!applied) {
                    buttons.prop('disabled',false);
                    updateVehicleCapacityWarning();
                }
            });
    }
    $('.programming-mod-reason').on('change',function(){
        const target = $(this).data('custom');
        $(target).toggleClass('d-none',this.value !== 'Otro').prop('required',this.value === 'Otro');
    });
    $('#modNewShift').on('change',refreshModificationOptions);
    $('#modNewVehicle').on('change',updateVehicleCapacityWarning);
    $('#modPeopleRole').on('change',updateVehicleCapacityWarning);
    $('#programmingModificationsModal').on('click','[data-add-change]',function(){addModification(this.dataset.addChange);});
    $('#programmingModHistoryBody').on('click','[data-modification-history]',function(){
        const change = modificationHistory.find(item => Number(item.id) === Number(this.dataset.modificationHistory));
        if (!change) return;
        Swal.fire({title:'Detalle de modificación',confirmButtonText:'Cerrar',width:650,html:`<dl class="text-start mb-0">
            <dt>Tipo de modificación</dt><dd>${escape(change.type)}</dd>
            <dt>Valor anterior</dt><dd>${escape(change.before?.value || '—')}</dd>
            <dt>Valor nuevo</dt><dd>${escape(change.after?.value || '—')}</dd>
            <dt>Motivo</dt><dd>${escape(modificationReasonLabel(change.after || {}))}</dd>
            <dt>Descripción</dt><dd>${escape(change.after?.description || '—')}</dd>
            <dt>Usuario responsable</dt><dd>${escape(change.user)}</dd>
            <dt>Fecha y hora</dt><dd>${escape(change.at)}</dd>
        </dl>`});
    });
    $('#programmingModificationsModal').on('hidden.bs.modal',function(){
        modificationId=null;modificationCurrent=null;modificationHistory=[];modificationOptions=null;
    });
    $('#programmingTable').on('click','[data-action]',async function(){
        const id = Number(this.closest('[data-id]').dataset.id), action = this.dataset.action;
        if(action === 'edit') {openModificationModule(id);return;}
        if(action === 'detail' || action === 'group') {
            $('#programmingDetailTitle').text('Detalle de programación y grupo');
            $('#programmingDetailBody').html('<div class="text-center text-body-secondary py-5"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Cargando datos e historial…</div>');
            detail.show();
            $.when(request('GET','/' + id),request('GET','/' + id + '/history'))
                .done((rowResult,historyResult) => {
                    $('#programmingDetailBody').html(details(rowResult[0],historyResult[0].data || []));
                })
                .fail(errors);
            return;
        }
        const isAnnul = action === 'annul';
        const result = await Swal.fire({icon:'question',title:isAnnul ? '¿Anular programación?' : '¿Aceptar y finalizar recorrido?',text:isAnnul ? 'El recorrido quedará anulado, se liberarán sus reservas y se conservará su historial.' : 'Se registrará la finalización del recorrido diario seleccionado.',showCancelButton:true,confirmButtonText:isAnnul ? 'Anular' : 'Aceptar',cancelButtonText:'Cancelar'});
        if(result.isConfirmed) request(isAnnul ? 'PATCH' : 'POST','/' + id + (isAnnul ? '/annul' : '/finish')).done(response => {
            table.ajax.reload(null,false); Swal.fire({icon:'success',title:'Proceso exitoso',text:response.message,confirmButtonText:'Entendido'});
        }).fail(errors);
    });

});
