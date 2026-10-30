<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Control de asistencia</title>
    <style>
        :root{color-scheme:light;--navy:#17324d;--blue:#1f5f91;--line:#cfd8e2;--page:#f5f7f9}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;padding:clamp(24px,6vw,72px) 20px;background:var(--page);color:var(--navy);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;display:flex;justify-content:center;align-items:center}
        .card{width:min(440px,100%);padding:34px 36px 36px;background:#fff;border:1px solid #dfe5ea;border-top:3px solid var(--blue);border-radius:6px;box-shadow:0 6px 18px rgba(23,50,77,.07)}
        .brand{margin:0 0 22px;color:var(--blue);font-size:.78rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
        .title{margin:0 0 28px;font-size:clamp(1.65rem,4vw,2rem);font-weight:650;letter-spacing:-.02em;line-height:1.2}
        .field{margin:0 0 20px}
        .field label{display:block;margin:0 0 8px;font-size:.95rem;font-weight:600}
        .field input{width:100%;min-height:56px;padding:14px 15px;border:1px solid var(--line);border-radius:4px;background:#fff;color:var(--navy);font:inherit;font-size:1.08rem;outline:none;transition:border-color .15s ease,box-shadow .15s ease}
        .field input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(31,95,145,.13)}
        .btn{width:100%;min-height:56px;margin-top:4px;padding:14px 18px;border:1px solid #174d78;border-radius:4px;background:var(--blue);color:#fff;font:inherit;font-size:1.05rem;font-weight:650;cursor:pointer;transition:background .15s ease,opacity .15s ease}
        .btn:hover:not(:disabled){background:#174d78}
        .btn:focus-visible{outline:3px solid rgba(31,95,145,.25);outline-offset:2px}
        .btn:disabled{opacity:.62;cursor:wait}
        .message{min-height:0;margin:0 0 18px;padding:11px 13px;border:1px solid transparent;border-radius:4px;font-size:.92rem;line-height:1.4}
        .message:empty{display:none}
        .message.success{border-color:#b9d9c5;background:#f0f8f2;color:#245b35}
        .message.error{border-color:#e6bcbc;background:#fdf2f2;color:#8b2d2d}
        @media (max-width:520px){.card{padding:28px 24px 30px}}
    </style>
</head>
<body>
<main class="card">
    <div class="brand">TOPICOS</div>
    <h1 class="title">Control de asistencia</h1>
    <div id="message" class="message" role="status" aria-live="polite"></div>
    <form id="attendance-form" method="POST" action="{{ route('employee.authenticate') }}" autocomplete="off">
        @csrf
        <div class="field"><label for="dni">DNI</label><input id="dni" name="dni" inputmode="numeric" maxlength="8" pattern="[0-9]{8}" required autofocus></div>
        <div class="field"><label for="password">Contrase&ntilde;a</label><input id="password" name="password" type="password" required></div>
        <button class="btn" id="submit-button" type="submit">Registrar asistencia</button>
    </form>
</main>
<script>
(() => {
    const form = document.getElementById('attendance-form');
    const dni = document.getElementById('dni');
    const password = document.getElementById('password');
    const button = document.getElementById('submit-button');
    const message = document.getElementById('message');
    let timer;

    function showMessage(text, kind) {
        window.clearTimeout(timer);
        message.textContent = text;
        message.className = 'message ' + kind;
        timer = window.setTimeout(() => {
            message.textContent = '';
            message.className = 'message';
        }, 3500);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        button.disabled = true;
        showMessage('Validando...', 'success');
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                credentials: 'same-origin',
                body: new FormData(form)
            });
            const payload = await response.json();
            if (! response.ok) {
                const errors = payload.errors || {};
                const values = Object.values(errors).flat();
                throw new Error(values[0] || payload.message || 'No se pudo registrar la asistencia.');
            }
            showMessage(payload.message, 'success');
            form.reset();
            dni.focus();
        } catch (error) {
            password.value = '';
            showMessage(error.message || 'No se pudo registrar la asistencia.', 'error');
            dni.focus();
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
</body>
</html>
