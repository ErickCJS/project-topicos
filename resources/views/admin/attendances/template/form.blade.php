<div class="row"> <div class="col-md-6"> 
    <div class="form-group mb-3"> 
        {!! Form::label('dni', 'DNI', ['class' => 'form-label mb-1']) !!} 
        {!! Form::text('dni', null, [ 'class' => 'form-control', 'placeholder' => 'Ingrese su DNI', 'maxlength' => 8, 'inputmode' => 'numeric', 'pattern' => '[0-9]{8}', 'autocomplete' => 'username', 'required', ]) !!} 
    </div> 
</div> 
<div class="col-md-6"> 
    <div class="form-group mb-3"> 
        {!! Form::label('password', 'Contraseña', ['class' => 'form-label mb-1']) !!} 
        <div class="input-group"> 
            {!! Form::password('password', [ 'class' => 'form-control', 'placeholder' => 'Ingrese su contraseña', 'id' => 'password', 'autocomplete' => 'current-password', 'required', ]) !!} 
            <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar contraseña"> 
                <i class="bi bi-eye" id="passwordIcon"></i> 
            </button> 
        </div> 
    </div> 
</div> 
</div> 
<script> 
document.getElementById('togglePassword').addEventListener('click', function () { 
    const password = document.getElementById('password'); 
    const icon = document.getElementById('passwordIcon'); 
    const mostrar = password.type === 'password'; 
    password.type = mostrar ? 'text' : 'password';
    icon.classList.toggle('bi-eye', !mostrar); 
    icon.classList.toggle('bi-eye-slash', mostrar); 
    this.setAttribute( 'aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña' ); }); 
</script>