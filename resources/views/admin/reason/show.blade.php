
@section('title', 'Detalle del motivo | RSU')

@section('content')
<div class="container-fluid">

    <div class="card shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $motivo->name }}</dd>

                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $motivo->description ?: 'Sin descripción' }}</dd>

                <dt class="col-sm-3">Creado</dt>
                <dd class="col-sm-9">{{ $motivo->created_at?->format('d/m/Y H:i:s') }}</dd>

                <dt class="col-sm-3">Actualizado</dt>
                <dd class="col-sm-9">{{ $motivo->updated_at?->format('d/m/Y H:i:s') }}</dd>
            </dl>
        </div>
    </div>
</div>
@stop
