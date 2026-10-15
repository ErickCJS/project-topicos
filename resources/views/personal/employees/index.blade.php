@extends('adminlte::page')

@section('title', 'Personal | RSU')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><h4 class="mb-0"><i class="bi bi-people me-2"></i>Personal</h4><button type="button" class="btn btn-success" id="btnNuevoEmpleado"><i class="bi bi-person-plus me-1"></i>Nuevo empleado</button></div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle" id="tablaEmpleados" style="width:100%">
            <thead><tr><th><i class="bi bi-image me-1"></i>Foto</th><th><i class="bi bi-card-text me-1"></i>DNI</th><th><i class="bi bi-person me-1"></i>Nombre</th><th><i class="bi bi-people me-1"></i>Apellidos</th><th><i class="bi bi-envelope me-1"></i>Email</th><th><i class="bi bi-person-badge me-1"></i>Tipo</th><th><i class="bi bi-toggle-on me-1"></i>Estado</th><th><i class="bi bi-calendar3 me-1"></i>Creación</th><th><i class="bi bi-gear me-1"></i>Acciones</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="employeeFormModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" id="employeeFormContent"></div></div></div>
<div id="employeeShowModalHost"></div>
@stop

@section('js')
<script>
$(function () {
    const formModal = new bootstrap.Modal(document.getElementById('employeeFormModal'));
    const table = $('#tablaEmpleados').DataTable({
        processing: true,
        serverSide: true,
        ajax: @json(route('personal.employees.index')),
        order: [[7, 'desc']],
        columns: [
            { data: 'photo', name: 'profile_photo_path', orderable: false, searchable: false },
            { data: 'dni', name: 'dni' },
            { data: 'first_name', name: 'first_name' },
            { data: 'last_name', name: 'last_name' },
            { data: 'email', name: 'email' },
            { data: 'type', name: 'employeeType.name' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        language: {
            processing: 'Cargando personal...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: 'Cargando registros...',
            zeroRecords: 'No se encontraron empleados',
            emptyTable: 'Aún no hay personal registrado',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

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
                    table.ajax.reload(null, false);
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

    $(document).on('click', '.btnVerEmpleado', function () {
        $.get($(this).data('url'), function (html) {
            $('#employeeShowModalHost').html(html);
            const modalElement = $('#employeeShowModalHost .modal').get(0);
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modalElement.addEventListener('hidden.bs.modal', function () {
                $('#employeeShowModalHost').empty();
            }, { once: true });
            modal.show();
        }).fail(function (error) { mostrarError(error, 'No se pudieron cargar los datos del empleado.'); });
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
                        table.ajax.reload(null, false);
                    });
                },
                error: function (error) { mostrarError(error, 'No se pudo eliminar el empleado.'); }
            });
        });
    });
});
</script>
@stop
