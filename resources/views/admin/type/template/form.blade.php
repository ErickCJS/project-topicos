<div class="form-group">
    {!! Form::label('name', 'Nombre') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Nombre del tipo de vehículo', 'required', 'maxlength' => 100]) !!}
</div>
<div class="form-group mt-2">
    {!! Form::label('description', 'Descripción') !!}
    {!! Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => 'Agregue una descripción', 'rows' => 3, 'maxlength' => 100]) !!}
</div>
