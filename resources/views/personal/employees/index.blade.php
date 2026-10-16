@extends('adminlte::page')

@section('title', 'Personal | RSU')

@section('content')
    <div class="employees-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0"><i class="bi bi-people me-2" aria-hidden="true"></i>Personal</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevoEmpleado">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Nuevo empleado
            </button>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle w-100" id="tablaEmpleados">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>DNI</th>
                                <th>Nombre</th>
                                <th>Apellidos</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Creación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="employeeFormModal" data-bs-backdrop="static" data-bs-keyboard="false"
            tabindex="-1" aria-labelledby="employeeFormModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" id="employeeFormContent">
                    @include('personal.employees.partials.form-modal')
                </div>
            </div>
        </div>

        <div class="modal fade" id="employeeShowModal" tabindex="-1"
            aria-labelledby="employeeShowModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content" id="employeeShowContent"></div>
            </div>
        </div>
    </div>
@stop

@section('css')
@vite('resources/css/admin-indexes.css')
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('employeeFormModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const createFormHtml = $('#employeeFormContent').html();
    const showModalElement = document.getElementById('employeeShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);
    let employeePhotoObjectUrl = null;

    function liberarVistaPrevia() {
        if (employeePhotoObjectUrl) {
            URL.revokeObjectURL(employeePhotoObjectUrl);
            employeePhotoObjectUrl = null;
        }
    }

    function mostrarMensaje(texto, error = false) {
        return Swal.fire({
            icon: error ? 'error' : 'success',
            title: error ? 'Ocurrió un error' : 'Proceso exitoso',
            text: texto,
            confirmButtonText: 'Entendido'
        });
    }

    function mensajeError(xhr, fallback) {
        const data = xhr.responseJSON || {};

        return data.mensaje || data.message || fallback;
    }

    function limpiarErrores(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.employee-field-error').remove();
        form.find('.employee-photo-picker').removeClass('border-danger');
    }

    function mostrarErroresValidacion(form, errors) {
        limpiarErrores(form);

        Object.entries(errors).forEach(function ([field, messages]) {
            const input = form.find('[name="' + field + '"]').not(':disabled').first();

            if (!input.length) return;

            input.addClass('is-invalid');
            $('<div>', {
                class: 'invalid-feedback employee-field-error',
                text: messages.join(' ')
            }).insertAfter(input);

            if (field === 'foto') {
                form.find('.employee-photo-picker').addClass('border-danger');
            }
        });
    }

    function mostrarErrorGeneral(xhr, fallback) {
        mostrarMensaje(mensajeError(xhr, fallback), true);
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

    const table = $('#tablaEmpleados').DataTable({
        serverSide: true,
        searchDelay: 350,
        ajax: "{{ route('personal.employees.index') }}",
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
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
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

    $(document).on('change', '#employeeFormContent [name="tipo_personal_id"]', function () {
        actualizarCampoLicencia($(this).closest('form'));
    });

    $(document).on('change', '#employeeProfilePhoto', function () {
        const archivo = this.files && this.files[0];

        if (!archivo) return;

        liberarVistaPrevia();
        employeePhotoObjectUrl = URL.createObjectURL(archivo);

        const preview = document.getElementById('employeePhotoPreview');
        preview.src = employeePhotoObjectUrl;
        preview.classList.remove('d-none');
        document.getElementById('employeePhotoPlaceholder').classList.add('d-none');
    });

    $(document).on('change', '#employeeChangePassword', function () {
        const password = document.getElementById('employeePassword');
        password.disabled = !this.checked;
        password.required = this.checked;

        if (!this.checked) password.value = '';
    });

    $(document).on('input change', '#employeeFormContent [name]', function () {
        const input = $(this);
        input.removeClass('is-invalid');
        input.next('.employee-field-error').remove();

        if (input.attr('name') === 'foto') {
            input.closest('.employee-photo-panel').find('.employee-photo-picker').removeClass('border-danger');
        }
    });

    $(document).on('submit', '#employeeFormContent form', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('[type="submit"]').prop('disabled', true);
        limpiarErrores(form);

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
                const response = error.responseJSON || {};

                if (error.status === 422 && response.errors) {
                    mostrarErroresValidacion(form, response.errors);
                    return;
                }

                mostrarErrorGeneral(error, 'No se pudo guardar el personal. Intente nuevamente.');
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    function abrirFormularioEmpleado(url) {
        liberarVistaPrevia();

        $.get(url, function (html) {
            $('#employeeFormContent').html(html);
            actualizarCampoLicencia($('#employeeFormContent form'));
            formModal.show();
        }).fail(function (error) {
            mostrarErrorGeneral(error, 'No se pudo cargar el formulario del empleado.');
        });
    }

    $('#btnNuevoEmpleado').on('click', function () {
        liberarVistaPrevia();
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
            mostrarErrorGeneral(error, 'No se pudieron cargar los datos del empleado.');
        });
    });

    $(document).on('click', '.employee-show-photo', function () {
        $(this).toggleClass('is-zoomed');
    });

    $(document).on('click', '.btnEliminarEmpleado', function () {
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar este empleado?',
            text: 'Si tiene contratos históricos, se conservará como inactivo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#b54747'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    Accept: 'application/json'
                },
                success: function (response) {
                    table.ajax.reload(null, false);
                    mostrarMensaje(response.mensaje || 'Operación completada correctamente.');
                },
                error: function (error) {
                    mostrarErrorGeneral(error, 'No se pudo eliminar el empleado.');
                }
            });
        });
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        liberarVistaPrevia();
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        $('#employeeShowContent').empty();
    });
});
</script>
@stop
