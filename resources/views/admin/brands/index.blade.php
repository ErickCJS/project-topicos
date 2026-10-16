@extends('adminlte::page')

@section('title', 'Marcas | RSUProject')

@section('content')
    <div class="brands-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0">Listado de Marcas</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevo">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nueva marca
            </button>
        </div>
        <div class="card">
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
    </div>
@stop

@section('css')
@vite('resources/css/admin-indexes.css')
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

    function mostrar_modal(titulo, contenido) {
        $('#formModal #formModalLabel').html(titulo);
        $('#formModal .modal-body').html(contenido);
        formModal.show();
    }

    function cerrar_modal() {
        formModal.hide();
    }

    function mostrar_alerta(tipo, mensaje) {
        const esError = tipo === 0;

        return Swal.fire({
            title: esError ? 'Ocurrió un error!' : 'Proceso exitoso!',
            icon: esError ? 'error' : 'success',
            text: mensaje,
            confirmButtonText: 'Entendido',
            draggable: true
        });
    }

    function mensajeError(error, mensajePredeterminado) {
        const response = error.responseJSON || {};

        return response.mensaje || response.message || mensajePredeterminado;
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
        ajax: "{{ route('brands.index') }}",
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
        $.ajax({
            url: "{{ route('brands.create') }}",
            type: 'GET',
            success: function (response) {
                mostrar_modal('Nueva Marca', response);
            },
            error: function (error) {
                mostrar_alerta(0, mensajeError(error, 'No se pudo cargar el formulario de marca.'));
            }
        });
    });

    $(document).on('click', '.btnVer', function () {
        $.get($(this).data('url'), function (response) {
            $('#brandShowContent').html(response);
            showModal.show();
        }).fail(function (error) {
            mostrar_alerta(0, mensajeError(error, 'No se pudo cargar la marca.'));
        });
    });

    $(document).on('click', '.btnEditar', function () {
        const brandId = $(this).data('id');

        $.ajax({
            url: "{{ route('brands.edit', ':id') }}".replace(':id', brandId),
            type: 'GET',
            success: function (response) {
                mostrar_modal('Editar Marca', response);
            },
            error: function (error) {
                mostrar_alerta(0, mensajeError(error, 'No se pudo cargar el formulario de marca.'));
            }
        });
    });

    $(document).on('submit', '#formModal form', function (event) {
        event.preventDefault();

        const form = $(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function (response) {
                cerrar_modal();
                refresTable();
                mostrar_alerta(1, response.mensaje);
            },
            error: function (error) {
                cerrar_modal();
                mostrar_alerta(0, mensajeError(error, 'No se pudo guardar la marca.'));
            }
        });
    });

    $(document).on('click', '.btnEliminar', function (event) {
        event.preventDefault();
        const brandId = $(this).data('id');

        Swal.fire({
            title: '¿Está seguro de eliminar?',
            text: 'Esto no se puede revertir!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b54747',
            cancelButtonColor: '#f8faf8',
            confirmButtonText: 'Sí, eliminar!',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('brands.destroy', ':id') }}".replace(':id', brandId),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    refresTable();
                    mostrar_alerta(1, response.mensaje);
                },
                error: function (error) {
                    mostrar_alerta(0, mensajeError(error, 'No se pudo eliminar la marca.'));
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
        liberarVistaPrevia();
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
