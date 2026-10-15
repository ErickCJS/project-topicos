<div class="form-group">
    {!! Form::label('name', 'Nombre') !!}
    {!! Form::text('name', null, [
        'class' => 'form-control',
        'placeholder' => 'Nombre del tipo de personal',
        'required',
        'maxlength' => 50
    ]) !!}
</div>

<div class="form-group mt-2">
    {!! Form::label('description', 'Descripción') !!}
    {!! Form::textarea('description', null, [
        'class' => 'form-control',
        'placeholder' => 'Descripción del tipo de personal',
        'rows' => 3,
        'maxlength' => 255
    ]) !!}
</div>