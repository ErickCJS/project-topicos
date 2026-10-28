
<div class="row g-3">

    {{-- Nombre del grupo --}}
    <div class="col-12 col-md-6">
        {!! Form::label('name', 'Nombre del Grupo *', [
            'class' => 'form-label fw-semibold'
        ]) !!}

        {!! Form::text('name', null, [
            'class' => 'form-control' . ($errors->has('name') ? ' is-invalid' : ''),
            'placeholder' => 'Grupo A',
            'required',
            'maxlength' => 100,
        ]) !!}
    </div>

    {{-- Turno --}}
    <div class="col-12 col-md-6">
        {!! Form::label('shift_id', 'Turno *', [
            'class' => 'form-label fw-semibold'
        ]) !!}

        {!! Form::select('shift_id', $shifts, null, [
            'class' => 'form-select' . ($errors->has('shift_id') ? ' is-invalid' : ''),
            'placeholder' => 'Seleccione un turno',
            'required',
        ]) !!}
    </div>

    {{-- Zona --}}
    <div class="col-12 col-md-6">
        {!! Form::label('zone_id', 'Zona *', [
            'class' => 'form-label fw-semibold'
        ]) !!}

        {!! Form::select('zone_id', $zones, null, [
            'class' => 'form-select' . ($errors->has('zone_id') ? ' is-invalid' : ''),
            'placeholder' => 'Seleccione una zona',
            'required',
        ]) !!}
    </div>

    {{-- Vehículo --}}
    <div class="col-12 col-md-6">
        {!! Form::label('vehicle_id', 'Vehículo *', [
            'class' => 'form-label fw-semibold'
        ]) !!}

        <select name="vehicle_id" id="vehicle_id"
            class="form-select" required>
            <option value="">Seleccione un vehículo</option>

            @foreach ($vehicles as $vehicle)
                <option
                    value="{{ $vehicle->id }}"
                    data-capacity="{{ $vehicle->occupats_capacity }}">
                    {{ $vehicle->code }} - {{ $vehicle->name }}
                    (Capacidad: {{ $vehicle->occupats_capacity }})
                </option>
            @endforeach
        </select>
    </div>
    
<div class="col-12">
    <label class="form-label fw-semibold">
        Días de trabajo <span class="text-danger">*</span>
    </label>

    <div class="row g-3">
        @php
            $dias = [
                'lunes' => 'Lunes',
                'martes' => 'Martes',
                'miercoles' => 'Miércoles',
                'jueves' => 'Jueves',
                'viernes' => 'Viernes',
                'sabado' => 'Sábado',
                'domingo' => 'Domingo',
            ];
        @endphp

        @foreach ($dias as $valor => $nombre)
            <div class="col-6 col-md-3 col-lg-2">
                <div class="form-check">
                    {!! Form::checkbox('days[]', $valor, false, [
                        'class' => 'form-check-input',
                        'id' => 'day_' . $valor,
                    ]) !!}

                    {!! Form::label('day_' . $valor, $nombre, [
                        'class' => 'form-check-label',
                    ]) !!}
                </div>
            </div>
        @endforeach
    </div>

    @error('days')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror

    @error('days.*')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
</div>

<div id="camposPersonal"></div>

</div>
