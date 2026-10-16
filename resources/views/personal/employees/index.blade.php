@extends('adminlte::page')

@section('title', 'Personal | RSU')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap"><h4 class="mb-0"><i class="bi bi-people me-2"></i>Personal</h4><button type="button" class="btn btn-success" id="btnNuevoEmpleado" style="margin-left:auto"><i class="bi bi-person-plus me-1"></i>Nuevo empleado</button></div>
    <div class="card-body"><div class="table-responsive">
        <table class="table table-striped table-hover align-middle w-100" id="tablaEmpleados">
            <thead class="bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700"><tr><th class="px-4 py-3">Foto</th><th class="px-4 py-3">DNI</th><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Apellidos</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Creación</th><th class="px-4 py-3">Acciones</th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>
<div class="modal fade" id="employeeFormModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content" id="employeeFormContent">@include('personal.employees.partials.form-modal')</div></div></div>
<div class="modal fade" id="employeeShowModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content" id="employeeShowContent"></div></div></div>
@stop

@section('js')
<style>
    #tablaEmpleados { width: 100% !important; }
    #tablaEmpleados th, #tablaEmpleados td { vertical-align: middle; white-space: nowrap; }
    #tablaEmpleados td:first-child { width: 72px; }
    .employee-show-photo { cursor: zoom-in; transition: transform .2s ease; }
    .employee-show-photo.is-zoomed { position: relative; z-index: 1056; cursor: zoom-out; transform: scale(1.5); }
    @media (max-width: 575.98px) {
        #btnNuevoEmpleado { width: 100%; margin-left: 0 !important; }
        #tablaEmpleados { min-width: 900px; }
        #employeeFormModal .modal-dialog { margin: .5rem; }
        #employeeFormModal .modal-body { padding: 1rem !important; }
    }
</style>
<script>
$(function () {
    const formModal = new bootstrap.Modal(document.getElementById('employeeFormModal'));
    const createFormHtml = $('#employeeFormContent').html();
    const showModalElement = document.getElementById('employeeShowModal');
    const showModal = new bootstrap.Modal(showModalElement);
    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#employeeShowContent').empty();
    });
    const table = $('#tablaEmpleados').DataTable({
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('personal.employees.index')),
        order: [[7, 'desc']],
        columns: [
            { data: 'photo', name: 'employees.profile_photo_path', orderable: false, searchable: false },
            { data: 'dni', name: 'employees.dni' },
            { data: 'first_name', name: 'employees.first_name' },
            { data: 'last_name', name: 'employees.last_name' },
            { data: 'email', name: 'employees.email' },
            { data: 'type', name: 'employee_types.name' },
            { data: 'status', name: 'employees.status' },
            { data: 'created_at', name: 'employees.created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
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

    $(document).on('change', '#employeeChangePassword', function () {
        const password = document.getElementById('employeePassword');
        password.disabled = !this.checked;
        password.required = this.checked;
        if (!this.checked) password.value = '';
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
                table.ajax.reload(null, false);
                mostrarMensaje(response.mensaje || 'Personal guardado correctamente.');
            },
            error: function (error) {
                mostrarError(error, 'No se pudo guardar el personal. Verifique los datos e intente nuevamente.');
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    function abrirFormularioEmpleado(url) {
        $.get(url, function (html) {
            $('#employeeFormContent').html(html);
            actualizarCampoLicencia($('#employeeFormContent form'));
            formModal.show();
        }).fail(function (error) {
            mostrarError(error, 'No se pudo cargar el formulario del empleado.');
        });
    }

    $('#btnNuevoEmpleado').on('click', function () {
        $('#employeeFormContent').html(createFormHtml);
        const form = $('#employeeFormContent form');
        actualizarCampoLicencia(form);
        formModal.show();
    });

    $(document).on('click', '.btnEditarEmpleado', function () {
        abrirFormularioEmpleado($(this).data('url'));
    });

    $(document).on('click', '.btnVerEmpleado', function () {
        $.get($(this).data('url'), function (html) {
            $('#employeeShowContent').html(html);
            showModal.show();
        }).fail(function (error) {
            mostrarError(error, 'No se pudieron cargar los datos del empleado.');
        });
    });

    $(document).on('click', '.employee-show-photo', function () {
        $(this).toggleClass('is-zoomed');
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
                    table.ajax.reload(null, false);
                    mostrarMensaje(response.mensaje || 'Personal eliminado correctamente.');
                },
                error: function (error) { mostrarError(error, 'No se pudo eliminar el empleado.'); }
            });
        });
    });
});
</script>
@stop
