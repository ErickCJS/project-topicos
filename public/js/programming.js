/* Interactions only: groups, people, availability and routes come from Laravel. */
$(function () {
    'use strict';
    const base = $('.programming-module').data('url');
    const form = document.getElementById('programmingForm');
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('programmingFormModal'));
    const detail = bootstrap.Modal.getOrCreateInstance(document.getElementById('programmingDetailModal'));
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
    let allowedGroupDays = [];

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
        return `<button type="button" class="btn btn-${style} table-action-button" data-action="${action}" title="${label}" aria-label="${label}"><i class="bi bi-${icon}" aria-hidden="true"></i></button>`;
    }
    const table = $('#programmingTable').DataTable({
        processing:true, order:[[0,'asc']], pageLength:10,
        ajax:{url:base, data:d => {
            d.start_date = $('#filterStart').val(); d.end_date = $('#filterEnd').val();
            d.zone_id = $('#filterZone').val(); d.shift_id = $('#filterShift').val();
        }, error:errors},
        columns:[
            {data:'date',render:(v,t) => ['display','export'].includes(t) ? dateLabel(v) : v},
            {data:'status',render:(v,t) => t === 'display' ? `<span class="programming-status ${v === 'Finalizado' ? 'text-success-emphasis bg-success-subtle' : 'text-info-emphasis bg-info-subtle'}">${escape(v)}</span>` : v},
            {data:'zone',render:$.fn.dataTable.render.text()},
            {data:'shift',render:(v,t) => t === 'display' ? `<span class="badge bg-warning-subtle text-warning-emphasis">${escape(v)}</span>` : v},
            {data:'vehicle',render:(v,t,row) => t === 'display' ? `<span class="programming-vehicle">${escape(v)}<small>${escape(row.plate)}</small></span>` : `${v} ${row.plate}`},
            {data:'driver',render:$.fn.dataTable.render.text()},
            {data:'helpers',render:(v,t) => t === 'display' ? v.map(name => `<span class="programming-helper">${escape(name)}</span>`).join('') : v.join(', ')},
            {data:null,orderable:false,searchable:false,render:row => `<div class="programming-actions table-action-buttons" data-id="${row.id}">${button('detail','eye','Ver detalle')}${row.status === 'Programado' ? button('edit','pencil-square','Modificar programación','outline-warning') + button('delete','trash','Eliminar programación','outline-danger') : ''}${button('history','clock-history','Historial de cambios','outline-secondary')}${row.status === 'Programado' ? button('finish','check-circle','Finalizar programación','outline-success') : ''}</div>`}
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
            scheduled:rows.filter(r => r.status === 'Programado').length,finished:rows.filter(r => r.status === 'Finalizado').length,filters};
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

    function helpers(capacity, ids = []) {
        const container = document.getElementById('programHelpers'); container.replaceChildren();
        for (let index = 0; index < Math.max(0,capacity - 1); index++) {
            const node = document.getElementById('programHelperTemplate').content.cloneNode(true);
            const select = node.querySelector('select'), label = node.querySelector('label');
            select.id = 'programHelper' + index; label.htmlFor = select.id; label.textContent = 'Ayudante ' + (index + 1);
            select.value = ids[index] || ''; container.appendChild(node);
        }
    }
    function loadHolidays(selected = null) {
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
        form.querySelectorAll('[name="workdays"]').forEach(input => {input.checked = !input.disabled && Number(input.value) === weekday;});
        if (allowedGroupDays.length && !allowedGroupDays.includes(weekday)) {
            document.getElementById('programStart').setCustomValidity('La fecha no corresponde a los días de trabajo del grupo. Elija otra fecha o cambie de grupo.');
        }
    }
    function applyGroupDays() {
        form.querySelectorAll('[name="workdays"]').forEach(input => {
            input.disabled = !allowedGroupDays.includes(Number(input.value));
            if (input.disabled) input.checked = false;
        });
    }
    function loadGroup(id, row = null) {
        if (groupRequest) groupRequest.abort();
        allowedGroupDays = []; applyGroupDays();
        document.getElementById('programStart').setCustomValidity('');
        $('#programGroupSummary').prop('hidden',true); helpers(1);
        if (!id) {$('#programDriver').val('');return;}
        $('#validateProgramming').prop('disabled',true);
        groupRequest = request('GET','/groups/' + id).done(group => {
            if (String($('#programGroup').val()) !== String(id)) return;
            $('#programGroupSummary').html([['Grupo',group.name],['Zona',group.zone],['Turno',`${group.shift} (${group.hours})`],['Vehículo',`${group.vehicle} · ${group.plate}`]].map(([label,value]) => `<div><small>${label}</small><strong>${escape(value)}</strong></div>`).join('')).prop('hidden',false);
            $('#programDriver').val(row ? row.driver_id : group.driver_id);
            helpers(group.capacity,row ? row.helper_ids : group.helpers);
            const days = (row ? row.days : group.days).map(Number);
            allowedGroupDays = group.days.map(Number);
            applyGroupDays();
            form.querySelectorAll('[name="workdays"]').forEach(input => {input.checked = days.includes(Number(input.value));});
            applyGroupDays();
            const dayNames = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
            $('#programGroupDaysNote').text('Días del grupo: ' + allowedGroupDays.slice().sort((a,b) => a-b).map(day => dayNames[day-1]).join(', ') + '. Los demás días están bloqueados.');
            syncEditingDay();
            invalidate();
            loadHolidays(row ? row.work_holidays : []);
        }).fail(xhr => {if(xhr.statusText !== 'abort') errors(xhr);}).always(() => $('#validateProgramming').prop('disabled',false));
    }
    $('#programGroup').on('change',function(){loadGroup(this.value);});
    $('#programmingForm').on('input change','input,select,textarea',invalidate);
    $('#programStart').on('change',function(){
        $('#programEnd').attr('min',this.value > todayInLima() ? this.value : todayInLima());
        if(editing) {$('#programEnd').val(this.value);syncEditingDay();}
        loadHolidays();
    });
    function openForm(row = null) {
        form.reset(); editing = row ? row.id : null;
        $('#programGroupDaysNote').text('Seleccione un grupo para habilitar sus días de trabajo.');
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
    $('#programmingFormModal').on('hidden.bs.modal',() => {if(groupRequest) groupRequest.abort();holidayRevision++;if(holidayRequest) holidayRequest.abort();holidayRequest=null;holidaysReady=false;invalidate();});
    $('#bulkProgramming').prop('disabled',true).attr('title','Programación masiva: siguiente avance');
    function payload() {
        return {staff_group_id:$('#programGroup').val(),driver_id:$('#programDriver').val(),helpers:$('.programming-helper-select').map((i,el) => el.value).get().filter(Boolean),
            start_date:$('#programStart').val(),end_date:$('#programEnd').val(),days:$(form).find('[name="workdays"]:checked').map((i,el) => el.value).get(),notes:$('#programNotes').val(),programming_id:editing,
            work_holidays:$('.programming-work-holiday:checked').map((i,el) => el.value).get()};
    }
    function omitted(result) {
        return [['Feriados omitidos',result.omitted_holidays],['Feriados habilitados para trabajar',result.included_holidays || []],['Fechas ya programadas',result.omitted_duplicates]].filter(([,dates]) => dates.length).map(([label,dates]) => `<div class="mt-2">${label}: ${dates.slice(0,8).map(dateLabel).join(', ')}${dates.length > 8 ? ' y ' + (dates.length - 8) + ' más' : ''}.</div>`).join('');
    }
    $('#validateProgramming').on('click',function(){
        if(checking || saving) return;
        if(!$('#programGroup').val()){status('danger','Seleccione un grupo de personal.');return;}
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
        }).fail(xhr => {invalidate();errors(xhr);}).always(() => {saving=false;$(form).find('input,select,textarea,button').prop('disabled',false);applyGroupDays();$('#saveProgramming').prop('disabled',true);});
    });
    function details(row) {
        return [['Fecha',dateLabel(row.date)],['Estado',row.status],['Grupo',row.group],['Zona',row.zone],['Turno',row.shift + ' (' + row.hours + ')'],['Vehículo',row.vehicle + ' · ' + row.plate],['Conductor',row.driver],['Ayudantes',row.helpers.join(', ') || 'Sin ayudantes'],...(row.holiday_work ? [['Trabajo en feriado',row.holiday_work.names.join(', ') + ' · Autorizado el ' + dateLabel(row.holiday_work.authorized_at)]] : []),['Observaciones',row.notes || 'Sin observaciones'],['Finalización',row.finished_at || 'Pendiente']].map(([label,value]) => `<div class="col-12 col-md-6"><span class="programming-detail-label">${label}</span><span class="text-break">${escape(value)}</span></div>`).join('');
    }
    $('#programmingTable').on('click','[data-action]',async function(){
        const id = Number(this.closest('[data-id]').dataset.id), action = this.dataset.action;
        if(action === 'detail' || action === 'edit') {
            request('GET','/' + id).done(row => {
                if(action === 'edit') openForm(row);
                else {$('#programmingDetailTitle').text('Detalle de programación');$('#programmingDetailBody').html('<div class="row g-3">' + details(row) + '</div>');detail.show();}
            }).fail(errors);return;
        }
        if(action === 'history') {
            request('GET','/' + id + '/history').done(result => {
                $('#programmingDetailTitle').text('Historial de cambios');
                $('#programmingDetailBody').html(result.data.map(change => {
                    const snapshot = value => value ? `<div class="small">${escape(dateLabel(value.date))} · ${escape(value.status)}<br>${escape(value.snapshot.zone)} / ${escape(value.snapshot.shift)} / ${escape(value.snapshot.vehicle)}<br>${escape(value.snapshot.driver)}; ${value.snapshot.helpers.map(escape).join(', ')}${value.snapshot.holiday_work ? '<br>Trabajo en feriado autorizado: ' + value.snapshot.holiday_work.names.map(escape).join(', ') : ''}<br>${escape(value.notes || 'Sin observaciones')}</div>` : '<div class="small">Sin registro previo</div>';
                    return `<section class="mb-3 pb-3 border-bottom"><strong>${escape(change.action)}</strong><div class="small text-body-secondary mb-2">${escape(change.at)} · ${escape(change.user)}</div><div class="row g-3"><div class="col-md-6">Antes${snapshot(change.before)}</div><div class="col-md-6">Después${snapshot(change.after)}</div></div></section>`;
                }).join('') || 'Sin cambios registrados.'); detail.show();
            }).fail(errors);return;
        }
        const result = await Swal.fire({icon:'question',title:action === 'delete' ? '¿Eliminar programación?' : '¿Finalizar recorrido?',text:action === 'delete' ? 'Se eliminará el recorrido diario seleccionado y se conservará su historial.' : 'Se registrará la finalización del recorrido diario seleccionado.',showCancelButton:true,confirmButtonText:action === 'delete' ? 'Eliminar' : 'Finalizar',cancelButtonText:'Cancelar'});
        if(result.isConfirmed) request(action === 'delete' ? 'DELETE' : 'POST','/' + id + (action === 'finish' ? '/finish' : '')).done(response => {
            table.ajax.reload(null,false); Swal.fire({icon:'success',title:'Proceso exitoso',text:response.message,confirmButtonText:'Entendido'});
        }).fail(errors);
    });
});
