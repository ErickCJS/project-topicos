<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro de asistencia</title>
    <style>
        :root{font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:#172b3a;background:#f3f6f8}*{box-sizing:border-box}body{margin:0;padding:24px}.page{max-width:900px;margin:0 auto}.top{display:flex;justify-content:space-between;align-items:center;gap:18px;margin:12px 0 22px}.top h1{font-size:clamp(1.45rem,4vw,2rem);margin:0}.clock{font-weight:650;text-align:right}.card{background:#fff;border:1px solid #e2e8ed;border-radius:14px;padding:22px;box-shadow:0 8px 24px #142a3a0b;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field label{display:block;font-weight:600;margin-bottom:6px}.field input,.field select{width:100%;padding:11px 12px;border:1px solid #cbd5dc;border-radius:8px;font:inherit}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}.btn{border:0;border-radius:8px;padding:11px 16px;font:inherit;font-weight:650;cursor:pointer}.btn:disabled{opacity:.6;cursor:wait}.primary{background:#157347;color:#fff}.secondary{background:#e9eff3;color:#213746}.message{display:none;margin-top:14px;padding:12px;border-radius:8px}.message.error{display:block;background:#fff0f0;color:#9b2525}.message.success{display:block;background:#edf9f1;color:#146c3a}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:500px}th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #e8edf0}th{font-size:.9rem;color:#526574}#history-empty{color:#617481}@media(max-width:600px){body{padding:14px}.top{align-items:flex-start;flex-direction:column}.clock{text-align:left}.grid{grid-template-columns:1fr}.card{padding:16px}.actions .btn{width:100%}}
    </style>
</head>
<body>
<main class="page">
    <header class="top"><h1>Registro de asistencia</h1><div><div class="clock" id="clock"></div><small>Hora del sistema</small></div></header>
    <section class="card">
        <form id="attendance-form">
            @csrf
            <div class="grid">
                <div class="field"><label for="dni">DNI</label><input id="dni" name="dni" inputmode="numeric" autocomplete="username" maxlength="8" pattern="[0-9]{8}" required></div>
                <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
                <div class="field"><label for="type">Marcación</label><select id="type" name="type" required><option value="Entrada">Entrada</option><option value="Salida">Salida</option></select></div>
            </div>
            <div class="actions"><button class="btn primary" id="submit-attendance" type="submit">Registrar asistencia</button><button class="btn secondary" id="show-history" type="button">Consultar mi historial</button></div>
            <div class="message" id="message" role="status" aria-live="polite"></div>
        </form>
    </section>
    <section class="card"><h2>Mis últimas asistencias</h2><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Hora</th><th>Marcación</th><th>Turno</th><th>Estado</th></tr></thead><tbody id="history"><tr><td id="history-empty" colspan="5">Verifique sus credenciales para consultar el historial.</td></tr></tbody></table></div></section>
</main>
<script>
const form=document.getElementById('attendance-form'), message=document.getElementById('message'), historyBody=document.getElementById('history'), clock=document.getElementById('clock');
const serverDate=new Date(@json($serverTime)),serverReceived=Date.now();const tick=()=>clock.textContent=new Intl.DateTimeFormat('es-PE',{dateStyle:'full',timeStyle:'medium',timeZone:@json(config('app.timezone'))}).format(new Date(serverDate.getTime()+Date.now()-serverReceived));tick();setInterval(tick,1000);
function showMessage(text,kind){message.textContent=text;message.className='message '+kind;}
async function send(url,data){const response=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(data)});const body=await response.json();if(!response.ok){const errors=body.errors?Object.values(body.errors).flat().join(' '):body.message||'No se pudo completar la solicitud.';throw new Error(errors)}return body;}
function credentials(){return{dni:form.elements.dni.value,password:form.elements.password.value};}
form.addEventListener('submit',async event=>{event.preventDefault();const button=document.getElementById('submit-attendance');button.disabled=true;try{const result=await send(@json(route('attendances.worker.store')),{...credentials(),type:form.elements.type.value});showMessage(result.mensaje,'success');await loadHistory();}catch(error){showMessage(error.message,'error')}finally{button.disabled=false}});
async function loadHistory(){historyBody.innerHTML='';try{const result=await send(@json(route('attendances.worker.history')),credentials());if(!result.registros.length){historyBody.innerHTML='<tr><td colspan="5">No hay asistencias registradas.</td></tr>';return;}result.registros.forEach(item=>{const row=document.createElement('tr');[item.date,item.time,item.type,item.shift||'—',item.status].forEach(value=>{const cell=document.createElement('td');cell.textContent=value;row.appendChild(cell)});historyBody.appendChild(row)});}catch(error){historyBody.innerHTML='<tr><td colspan="5">'+error.message.replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]))+'</td></tr>';}}
document.getElementById('show-history').addEventListener('click',loadHistory);
</script>
</body>
</html>
