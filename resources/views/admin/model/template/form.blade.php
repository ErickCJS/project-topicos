<div class="form-group">
    {!! Form::label('brand_id', 'Marca') !!}
    {!! Form::select('brand_id', $brands, null, [
        'class' => 'form-control',
        'placeholder' => 'Seleccione una marca',
        'required'
    ]) !!}
</div>

<div class="form-group mt-2">
    {!! Form::label('name', 'Nombre del modelo') !!}
    {!! Form::text('name', null, [
        'class' => 'form-control',
        'placeholder' => 'Nombre del modelo',
        'required',
        'maxlength' => 100
    ]) !!}
</div>

<div class="form-group mt-2">
    {!! Form::label('code', 'Código') !!}
    {!! Form::text('code', null, [
        'class' => 'form-control',
        'placeholder' => 'Código del modelo',
        'maxlength' => 50
    ]) !!}
</div>

<div class="form-group mt-2">
    {!! Form::label('description', 'Descripción') !!}
    {!! Form::textarea('description', null, [
        'class' => 'form-control',
        'placeholder' => 'Descripción del modelo',
        'rows' => 3
    ]) !!}
</div>
