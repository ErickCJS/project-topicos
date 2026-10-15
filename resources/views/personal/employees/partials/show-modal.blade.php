<div class="modal fade" id="showEmployeeModal{{ $employee->id }}" tabindex="-1" aria-labelledby="showEmployeeModalLabel{{ $employee->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="showEmployeeModalLabel{{ $employee->id }}">Detalle del empleado</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
            <div class="text-center mb-4"><img src="{{ $employee->profile_photo_path ? asset('storage/'.$employee->profile_photo_path) : asset('storage/images/no_logo.png') }}" onerror="this.onerror=null;this.src='{{ asset('storage/images/no_logo.png') }}'" alt="Foto de {{ $employee->first_name }}" class="img-fluid rounded" style="max-height:320px"></div>
            <dl class="row">
                <dt class="col-sm-4">ID</dt><dd class="col-sm-8">{{ $employee->id }}</dd>
                <dt class="col-sm-4">DNI</dt><dd class="col-sm-8">{{ $employee->dni }}</dd>
                <dt class="col-sm-4">Nombres</dt><dd class="col-sm-8">{{ $employee->first_name }}</dd>
                <dt class="col-sm-4">Apellidos</dt><dd class="col-sm-8">{{ $employee->last_name }}</dd>
                <dt class="col-sm-4">Fecha de nacimiento</dt><dd class="col-sm-8">{{ $employee->birthdate }}</dd>
                <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $employee->email }}</dd>
                <dt class="col-sm-4">Tipo</dt><dd class="col-sm-8">{{ $employee->employeeType?->name }}</dd>
                <dt class="col-sm-4">Estado</dt><dd class="col-sm-8">{{ $employee->status }}</dd>
                <dt class="col-sm-4">Dirección</dt><dd class="col-sm-8">{{ $employee->address }}</dd>
                <dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8">{{ $employee->phone }}</dd>
                <dt class="col-sm-4">Licencia</dt><dd class="col-sm-8">{{ $employee->license }}</dd>
                <dt class="col-sm-4">Creación</dt><dd class="col-sm-8">{{ $employee->created_at }}</dd>
                <dt class="col-sm-4">Actualización</dt><dd class="col-sm-8">{{ $employee->updated_at }}</dd>
            </dl>
        </div>
    </div></div>
</div>
