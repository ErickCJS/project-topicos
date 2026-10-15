@extends('adminlte::page')

@section('title', 'Personal | RSU')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><h4 class="mb-0"><i class="bi bi-people me-2"></i>Personal</h4><button type="button" class="btn btn-success" id="btnNuevoEmpleado"><i class="bi bi-person-plus me-1"></i>Nuevo empleado</button></div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead><tr><th><i class="bi bi-image me-1"></i>Foto</th><th><i class="bi bi-card-text me-1"></i>DNI</th><th><i class="bi bi-person me-1"></i>Nombre</th><th><i class="bi bi-people me-1"></i>Apellidos</th><th><i class="bi bi-envelope me-1"></i>Email</th><th><i class="bi bi-person-badge me-1"></i>Tipo</th><th><i class="bi bi-toggle-on me-1"></i>Estado</th><th><i class="bi bi-calendar3 me-1"></i>Creación</th><th><i class="bi bi-gear me-1"></i>Acciones</th></tr></thead>
            <tbody>
                @forelse($empleados as $empleado)
                    <tr>
                        <td><img src="{{ $empleado->profile_photo_path ? asset('storage/'.$empleado->profile_photo_path) : asset('storage/images/no_logo.png') }}" onerror="this.onerror=null;this.src='{{ asset('storage/images/no_logo.png') }}'" alt="Foto de {{ $empleado->first_name }}" class="rounded-circle" width="48" height="48" style="object-fit:cover"></td>
                        <td>{{ $empleado->dni }}</td><td>{{ $empleado->first_name }}</td><td>{{ $empleado->last_name }}</td><td>{{ $empleado->email }}</td><td>{{ $empleado->employeeType?->name }}</td>
                        <td><span class="badge {{ $empleado->status === 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $empleado->status }}</span></td><td>{{ $empleado->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-info btn-sm btnVerEmpleado" data-target="#showEmployeeModal{{ $empleado->id }}" title="Ver"><i class="bi bi-eye"></i></button>
                            <button type="button" class="btn btn-primary btn-sm btnEditarEmpleado" data-url="{{ route('personal.employees.edit', $empleado) }}" title="Editar"><i class="bi bi-pencil-square"></i></button>
                            <button type="button" class="btn btn-danger btn-sm btnEliminarEmpleado" data-url="{{ route('personal.employees.destroy', $empleado) }}" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    @include('personal.employees.partials.show-modal', ['employee' => $empleado])
                @empty
                    <tr><td colspan="9" class="text-center">No hay personal registrado.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="d-flex justify-content-end">{{ $empleados->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
<div class="modal fade" id="employeeFormModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" id="employeeFormContent"></div></div></div>
@stop

@section('js')
<script>
$(function () {
    const formModal = new bootstrap.Modal(document.getElementById('employeeFormModal'));

    function mostrarMensaje(texto, error = false) {
        return Swal.fire({
            icon: error ? 'error' : 'success',
            title: error ? 'Error en los datos del personal' : 'Proceso exitoso',
            text: texto,
            confirmButtonColor: '#2563EB',
            confirmButtonText: 'Entendido'
        });
    }

    function mostrarError(xhr, fallback) {
        const data = xhr.responseJSON || {};
        const errores = data.errors ? Object.values(data.errors).flat() : [];
        mostrarMensaje(errores.length ? errores.join(' ') : (data.mensaje || fallback), true);
    }

    function actualizarCampoLicencia(form) {
        const tipo = form.find('[name="tipo_personal_id"] option:selected');
        const campo = form.find('[data-campo-licencia]');
        const input = campo.find('[name="licencia"]');
        const requiereLicencia = tipo.data('requiere-licencia') == 1;

        campo.prop('hidden', !requiereLicencia);
        input.prop('disabled', !requiereLicencia).prop('required', requiereLicencia);
        if (!requiereLicencia) input.val('');
    }

    $(document).on('change', '#employeeFormContent [name="tipo_personal_id"]', function () {
        actualizarCampoLicencia($(this).closest('form'));
    });

    $(document).on('change', '#employeeProfilePhoto', function () {
        const archivo = this.files && this.files[0];
        if (!archivo) return;

        const preview = document.getElementById('employeePhotoPreview');
        preview.src = URL.createObjectURL(archivo);
        preview.classList.remove('d-none');
        document.getElementById('employeePhotoPlaceholder').classList.add('d-none');
    });

    $(document).on('submit', '#employeeFormContent form', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('[type="submit"]').prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                mostrarMensaje(response.mensaje || 'Personal guardado correctamente.').then(function () {
                    window.location.reload();
                });
            },
            error: function (error) {
                mostrarError(error, 'No se pudo guardar el personal. Verifique los datos e intente nuevamente.');
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    $('#btnNuevoEmpleado').on('click', function () {
        $.get(@json(route('personal.employees.create')), function (html) {
            $('#employeeFormContent').html(html);
            actualizarCampoLicencia($('#employeeFormContent form'));
            formModal.show();
        });
    });

    $(document).on('click', '.btnEditarEmpleado', function () {
        $.get($(this).data('url'), function (html) {
            $('#employeeFormContent').html(html);
            actualizarCampoLicencia($('#employeeFormContent form'));
            formModal.show();
        }).fail(function (error) { mostrarError(error, 'No se pudo cargar el empleado.'); });
    });

    $(document).on('click', '.btnEliminarEmpleado', function () {
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar este empleado?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563EB'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
                success: function (response) {
                    mostrarMensaje(response.mensaje || 'Personal eliminado correctamente.').then(function () {
                        window.location.reload();
                    });
                },
                error: function (error) { mostrarError(error, 'No se pudo eliminar el empleado.'); }
            });
        });
    });

    $('.btnVerEmpleado').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.querySelector($(this).data('target'))).show();
    });
});
</script>
@stop
