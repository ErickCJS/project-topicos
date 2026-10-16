@extends('adminlte::page')

@section('title', 'Marcas | RSUProject')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <h4 class="mb-0">Listado de Marcas</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nueva marca
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle w-100" id="DataTable">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Creación</th>
                            <th>Actualización</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header brand-modal-header">
                    <div class="brand-modal-heading">
                        <span class="brand-modal-icon" aria-hidden="true"><i class="bi bi-tags"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="formModalLabel">Marca</h5>
                            <small>Complete la información de la marca</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body brand-modal-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="brandShowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="brandShowContent"></div>
        </div>
    </div>

    <div class="modal fade" id="brandLogoModal" tabindex="-1" aria-labelledby="brandLogoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header brand-modal-header">
                    <div class="brand-modal-heading">
                        <span class="brand-modal-icon" aria-hidden="true"><i class="bi bi-image"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="brandLogoModalLabel">Logo de marca</h5>
                            <small>Vista ampliada de la imagen</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body brand-modal-body brand-logo-modal-body">
                    <img id="brandLogoModalImage" class="img-fluid" alt="">
                </div>
                <div class="modal-footer brand-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    #DataTable th,
    #DataTable td {
        vertical-align: middle;
    }

    #formModal .modal-content,
    #brandShowModal .modal-content,
    #brandLogoModal .modal-content {
        overflow: hidden;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
    }

    #formModal .brand-modal-header,
    #brandShowModal .brand-modal-header,
    #brandLogoModal .brand-modal-header {
        min-height: 76px;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }

    #formModal .brand-modal-heading,
    #brandShowModal .brand-modal-heading,
    #brandLogoModal .brand-modal-heading {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    #formModal .brand-modal-heading small,
    #brandShowModal .brand-modal-heading small,
    #brandLogoModal .brand-modal-heading small {
        display: block;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
    }

    #formModal .brand-modal-icon,
    #brandShowModal .brand-modal-icon,
    #brandLogoModal .brand-modal-icon {
        display: inline-grid;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        place-items: center;
        color: var(--bs-info-text-emphasis);
        background-color: var(--bs-info-bg-subtle);
        border: 1px solid var(--bs-info-border-subtle);
        border-radius: .65rem;
        font-size: 1.15rem;
    }

    #formModal .brand-modal-body,
    #brandShowModal .brand-modal-body,
    #brandLogoModal .brand-modal-body {
        padding: 1.5rem;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
    }

    #formModal .brand-form-intro {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    #formModal .brand-form-intro h6,
    #formModal .form-label {
        color: var(--bs-body-color);
    }

    #formModal .brand-form-intro p,
    #formModal .form-text,
    #formModal .brand-preview-placeholder {
        color: var(--bs-secondary-color);
    }

    #formModal .form-label {
        margin-bottom: .45rem;
    }

    #formModal .form-control {
        min-height: 42px;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }

    #formModal textarea.form-control {
        min-height: 112px;
    }

    #formModal .form-control::placeholder {
        color: var(--bs-secondary-color);
        opacity: .8;
    }

    #formModal .form-control:focus {
        border-color: rgba(var(--bs-info-rgb), .8);
        box-shadow: 0 0 0 .2rem rgba(var(--bs-info-rgb), .16);
    }

    #formModal .brand-preview-panel,
    #brandShowModal .brand-detail-card {
        height: 100%;
        padding: 1rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .6rem;
    }

    #formModal .brand-preview-frame,
    #brandShowModal .brand-detail-logo-frame {
        display: grid;
        min-height: 260px;
        place-items: center;
        padding: 1rem;
        background-color: var(--bs-body-bg);
        border: 1px dashed var(--bs-border-color);
        border-radius: .55rem;
    }

    #formModal .brand-preview-image,
    #brandShowModal .brand-detail-logo {
        width: 100%;
        max-width: 260px;
        height: 230px;
        object-fit: contain;
    }

    #formModal .brand-preview-placeholder {
        text-align: center;
    }

    #formModal .brand-preview-placeholder i {
        display: block;
        margin-bottom: .5rem;
        font-size: 4rem;
        line-height: 1;
    }

    #formModal .brand-modal-footer,
    #brandShowModal .brand-modal-footer,
    #brandLogoModal .brand-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
    }

    #formModal .brand-modal-footer {
        margin: 1.5rem -1.5rem -1.5rem;
    }

    #brandShowModal .brand-detail-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--bs-secondary-color);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    #brandShowModal .brand-detail-value {
        color: var(--bs-body-color);
        font-weight: 600;
    }

    #brandShowModal .brand-detail-description {
        color: var(--bs-body-color);
        white-space: pre-wrap;
    }

    html.dark #formModal .modal-content,
    html.dark #brandShowModal .modal-content,
    html.dark #brandLogoModal .modal-content,
    html[data-bs-theme="dark"] #formModal .modal-content,
    html[data-bs-theme="dark"] #brandShowModal .modal-content,
    html[data-bs-theme="dark"] #brandLogoModal .modal-content {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5);
    }

    .brand-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .brand-actions .brand-action-btn {
        display: inline-flex;
        width: 36px;
        min-width: 36px;
        height: 31px;
        flex: 0 0 36px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-width: 1px;
        border-style: solid;
        border-radius: .25rem;
    }

    .brand-actions .brand-action-btn i {
        line-height: 1;
    }

    .brand-logo-preview {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        color: inherit;
        background: transparent;
        border: 0;
        border-radius: .35rem;
        cursor: zoom-in;
    }

    #DataTable .brand-logo-preview img {
        width: 70px;
        height: 50px;
        object-fit: contain;
    }

    .brand-logo-preview:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }

    #brandLogoModal .brand-logo-modal-body {
        display: grid;
        min-height: 320px;
        place-items: center;
    }

    #brandLogoModalImage {
        width: 100%;
        max-height: 70vh;
        object-fit: contain;
    }

    @media (max-width: 575.98px) {
        #formModal .brand-modal-body,
        #brandShowModal .brand-modal-body,
        #brandLogoModal .brand-modal-body {
            padding: 1rem;
        }

        #formModal .brand-modal-footer {
            margin: 1rem -1rem -1rem;
        }

        #formModal .brand-modal-footer,
        #brandShowModal .brand-modal-footer,
        #brandLogoModal .brand-modal-footer {
            flex-wrap: wrap;
        }
    }
</style>
@stop

@section('js')
<script>
$(function () {
    const formModalElement = document.getElementById('formModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalElement);
    const showModalElement = document.getElementById('brandShowModal');
    const showModal = bootstrap.Modal.getOrCreateInstance(showModalElement);
    const logoModalElement = document.getElementById('brandLogoModal');
    const logoModal = bootstrap.Modal.getOrCreateInstance(logoModalElement);
    let previewObjectUrl = null;
    let reopenShowModalAfterLogo = false;

    function mostrarModal(titulo, contenido) {
        $('#formModalLabel').text(titulo);
        $('#formModal .modal-body').html(contenido);
        formModal.show();
    }

    function mostrarAlerta(esExito, mensaje) {
        return Swal.fire({
            title: esExito ? 'Proceso exitoso' : 'Ocurrió un error',
            icon: esExito ? 'success' : 'error',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    }

    function mensajesError(xhr, mensajePredeterminado) {
        const response = xhr.responseJSON || {};
        const errores = response.errors ? Object.values(response.errors).flat() : [];

        return errores.length
            ? errores
            : [response.mensaje || response.message || mensajePredeterminado];
    }

    function mostrarErrorFormulario(xhr) {
        const mensajes = mensajesError(xhr, 'No se pudo guardar la marca. Verifique los datos.');
        const items = mensajes.map(function (mensaje) {
            return $('<li>').text(mensaje).prop('outerHTML');
        }).join('');

        $('#formModal .brand-validation-errors').remove();
        $('#formModal form').prepend(
            '<div class="alert alert-danger brand-validation-errors" role="alert">' +
                '<div class="fw-semibold mb-1">Revise la información ingresada:</div>' +
                '<ul class="mb-0 ps-3">' + items + '</ul>' +
            '</div>'
        );
    }

    function liberarVistaPrevia() {
        if (previewObjectUrl) {
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
        }
    }

    const table = $('#DataTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: @json(route('brands.index')),
        columns: [
            { data: 'logo', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description', defaultContent: '' },
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: '',
            zeroRecords: 'No se encontraron marcas',
            emptyTable: 'Aún no hay marcas registradas',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' }
        }
    });

    function refresTable() {
        table.ajax.reload(null, false);
    }

    $('#btnNuevo').on('click', function () {
        $.get(@json(route('brands.create')), function (response) {
            mostrarModal('Nueva marca', response);
        }).fail(function (error) {
            mostrarAlerta(false, mensajesError(error, 'No se pudo cargar el formulario de marca.').join(' '));
        });
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#brandShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrarAlerta(false, mensajesError(error, 'No se pudo cargar la marca.').join(' '));
        });
    });

    $(document).on('click', '.btnEditar', function () {
        $.get($(this).data('url'), function (response) {
            mostrarModal('Editar marca', response);
        }).fail(function (error) {
            mostrarAlerta(false, mensajesError(error, 'No se pudo cargar el formulario de marca.').join(' '));
        });
    });

    $(document).on('submit', '#formModal form', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('[type="submit"]').prop('disabled', true);
        $('#formModal .brand-validation-errors').remove();

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (response) {
                formModal.hide();
                refresTable();
                mostrarAlerta(true, response.mensaje || 'Marca guardada correctamente.');
            },
            error: function (error) {
                mostrarErrorFormulario(error);
            },
            complete: function () {
                submitButton.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: '¿Está seguro de eliminar esta marca?',
            text: 'Esta acción no se puede revertir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    Accept: 'application/json'
                },
                success: function (response) {
                    refresTable();
                    mostrarAlerta(true, response.mensaje || 'Marca eliminada correctamente.');
                },
                error: function (error) {
                    mostrarAlerta(false, mensajesError(error, 'No se pudo eliminar la marca.').join(' '));
                }
            });
        });
    });

    $(document).on('click', '.brand-logo-preview', function () {
        const brandName = $(this).data('brand');
        $('#brandLogoModalLabel').text('Logo de ' + brandName);
        $('#brandLogoModalImage')
            .attr('src', $(this).data('image'))
            .attr('alt', 'Logo de ' + brandName);

        if (showModalElement.classList.contains('show')) {
            reopenShowModalAfterLogo = true;
            showModalElement.addEventListener('hidden.bs.modal', function () {
                logoModal.show();
            }, { once: true });
            showModal.hide();
        } else {
            logoModal.show();
        }
    });

    $(document).on('change', '#brandLogoInput', function (event) {
        const file = event.target.files[0];
        const preview = $('#preview-logo');
        const placeholder = $('#preview-placeholder');
        const existingSource = preview.data('existing-src') || '';

        liberarVistaPrevia();

        if (file) {
            previewObjectUrl = URL.createObjectURL(file);
            preview.attr('src', previewObjectUrl).removeClass('d-none');
            placeholder.addClass('d-none');
        } else if (existingSource) {
            preview.attr('src', existingSource).removeClass('d-none');
            placeholder.addClass('d-none');
        } else {
            preview.attr('src', '').addClass('d-none');
            placeholder.removeClass('d-none');
        }
    });

    formModalElement.addEventListener('hidden.bs.modal', function () {
        liberarVistaPrevia();
        $('#formModal .modal-body').empty();
    });

    showModalElement.addEventListener('hidden.bs.modal', function () {
        if (!reopenShowModalAfterLogo) {
            $('#brandShowContent').empty();
        }
    });

    logoModalElement.addEventListener('hidden.bs.modal', function () {
        $('#brandLogoModalImage').attr('src', '').attr('alt', '');

        if (reopenShowModalAfterLogo) {
            reopenShowModalAfterLogo = false;
            showModal.show();
        }
    });
});
</script>
@stop
