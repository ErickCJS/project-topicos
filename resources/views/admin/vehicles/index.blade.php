@extends('adminlte::page')

@section('title', 'RSUProject')
@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link href="{{ asset('css/vehicle-select2.css') }}" rel="stylesheet">
<style>
    .vehicle-logo-preview {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        background: transparent;
        border: 0;
        border-radius: .25rem;
        cursor: zoom-in;
    }

    .vehicle-logo-preview img {
        width: 70px;
        height: 50px;
        object-fit: contain;
    }

    .vehicle-logo-preview:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }
</style>
@stop
@section('content')
    <div class="card">
        <div class="card-header d-flex">
            <div class="w-50">
                <h4>Listado de vehículos</h4>
            </div>
            <div class="w-50 d-flex justify-content-end">
                <button class="btn btn-success" id="btnNuevo"><i class="fas fa-plus-circle"></i> Nuevo
                    Vehículo</button>
            </div>

        </div>
        <div class="card-body">
            <table class="table table-striped" id="DataTable">
    <thead>
        <tr>
            <th>Logo</th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Placa</th>
            <th>Año</th>
            <th>Capacidad</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Tipo</th>
            <th>Color</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody></tbody>
</table>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="formModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalLabel">Modal title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ...
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vehicleLogoModal" tabindex="-1" aria-labelledby="vehicleLogoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vehicleLogoModalLabel">Imagen del vehículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="vehicleLogoModalImage" class="img-fluid" alt="" style="max-height: 70vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>
@stop


@section('js')
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>


    function mostrar_modal (titulo, response){
        $('#formModal #formModalLabel').html(titulo);
        $('#formModal .modal-body').html(response);
        $('#formModal').modal('show');
        $('#formModal select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#formModal')
        });
    }
    function cerrar_modal(){
        $('#formModal').modal('hide');
    }

    
    window.mostrar_alerta = (tipo, mensaje) => {
        var icono = tipo == 0 ? 'error' : 'success';
        var titulo = tipo == 0 ? 'Ocurrió un error!' : 'Proceso exitoso!';

        Swal.fire({
            title: titulo,
            icon: icono,
            text: mensaje,
            draggable: true
        });
    };

    var table;

    // ==========================================
    // NUEVA MARCA
    // ==========================================

    $('#btnNuevo').click(function () {

        $.ajax({
            url: "{{ route('vehicles.create') }}",
            type: "GET",
            success: function (response) {
                mostrar_modal('Nuevo vehículo', response);
            }
        });

    });

    //FUNCIONALIDAD GENERAL PARA PETICIONES 
    $(document).on('submit', '#formModal form', function (e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                //cerramos el modal
                cerrar_modal();
                // Recargar DataTable
                refresTable();
                //mostramos una alerta
                mostrar_alerta(1, response.mensaje);
            },
            error: function (err) {
                var response = err.responseJSON;

                if (err.status === 422) {
                    var firstError = response?.errors
                        ? Object.values(response.errors).flat()[0]
                        : response?.message;
                    mostrar_alerta(0, firstError || 'Revisa los datos del formulario.');
                    return;
                }

                cerrar_modal();
                mostrar_alerta(0, response?.mensaje || 'No se pudo guardar el vehículo.');
            }
        });
    });

    // ==========================================
    // EDITAR
    // ==========================================
    // guarda el evento en el document, y espera si ese btn tiene esa clase se ejecuta
    $(document).on('click', '.btnEditar', function () {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('vehicles.edit', ':id') }}".replace(':id', id),
            type: "GET",
            success: function (response) {
                mostrar_modal('Editar Vehículo',response);
            }

        });
    });


    // ==========================================
    // ELIMINAR
    // ==========================================

    $(document).on('click', '.btnEliminar', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: "¿Está seguro de eliminar?",
            text: "Esto no se puede revertir!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, eliminar!",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('vehicles.destroy', ':id') }}".replace(':id', id),
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        refresTable();
                        //mostramos una alerta
                        mostrar_alerta(1, response.mensaje);
                    },
                    error: function (err) {
                        var response = err.responseJSON;
                        mostrar_alerta(0, response.mensaje);
                    }
                });
            }
        });
    });


    // ==========================================
    // DATATABLE
    // ==========================================

    
    $(document).ready(function () {
        table = $('#DataTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('vehicles.index') }}",
            order: [[1, 'asc']],
            columns: [
                { data: "logo", name: "vehicle_images.imagen" },
                { data: "code", name: "vehicles.code" },
                { data: "name", name: "vehicles.name" },
                { data: "plate", name: "vehicles.plate" },
                { data: "year", name: "vehicles.year" },
                { data: "load_capacity", name: "vehicles.load_capacity" },
                { data: "brand", name: "brands.name" },
                { data: "model", name: "brandmodels.name" },
                { data: "type", name: "vehicletypes.name" },
                { data: "color", name: "color.name", orderable: false,
                    searchable: false },
                {
                    data: "actions",
                    orderable: false,
                    searchable: false
                }
            ],
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
            }
        });
    });

    $(document).on('click', '.vehicle-logo-preview, .ampliar_foto', function () {
        var isVehicleLogo = $(this).hasClass('vehicle-logo-preview');
        var vehicleName = $(this).data('vehicle');
        var imageTitle = isVehicleLogo ? 'Imagen de ' + vehicleName : 'Imagen del vehículo';
        var imageUrl = $(this).data('image') || this.src;

        $('#vehicleLogoModalLabel').text(imageTitle);
        $('#vehicleLogoModalImage')
            .attr('src', imageUrl)
            .attr('alt', imageTitle);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('vehicleLogoModal')).show();
    });

    $('#vehicleLogoModal').on('hidden.bs.modal', function () {
        $('#vehicleLogoModalImage').attr('src', '').attr('alt', '');
    });

    $(document).on('click', '.btnImagen', function(){
        let id = $(this).data('id');
        $.ajax({
            url: "{{ route('vehicles.photo.retornar_vista_imagenes', ':id') }}".replace(':id', id),
            type: "GET",
            success:  function(response) {
                mostrar_modal('Imagenes', response);
            }
        });
    })

    // ==========================================
    // RECARGAR TABLA
    // ==========================================

    window.refresTable = () => {
        table.ajax.reload(null, false);
    }

    //////////
        $(document).on('click', '.btn_subir', function () {
        let fotos = $('#photos')[0].files;
        if (fotos.length > 0) {
            let formData = new FormData();
            for (let i = 0; i < fotos.length; i++) {
                formData.append('photos[]', fotos[i]);
            }
            formData.append('_token', '{{ csrf_token() }}');
            $.ajax({
                url: "{{ route('vehicles.photo.subirimagenes', ':id') }}".replace(':id', vehicle_id),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#photos').val('');
                    cargarFotos();
                    refresTable();
                    mostrar_alerta(1, response.mensaje, response.titulo)
                },
                error: function (response) {
                    mostrar_alerta(0, response.responseJSON?.mensaje ?? 'Ocurrió un error al subir la imagen', response.responseJSON?.titulo)
                }
            });
        }else{
            mostrar_alerta(0, 'selecciona una imagen para subir');
        }
    });


    function cargarFotos() {
        $.ajax({
            url: "{{ route('vehicles.photo.retonar_rutas_fotos', ':id') }}".replace(':id', vehicle_id),
            type: 'GET',
            success: function (photos) {
                let filas = '';

                if (photos.length > 0) {
                    photos.forEach(function (photo) {
                        filas += `
                            <tr>
                                <td>
                                    <img src="/storage/${photo.imagen}"
                                        class="img-thumbnail ampliar_foto"
                                        alt="Foto del vehículo"
                                        width="80"
                                        height="80"
                                        style="object-fit: cover; cursor: zoom-in;">
                                </td>
                                <td class="text-center">
                                    <input type="radio"
                                        name="foto_principal"
                                        class="form-check-input radio_principal"
                                        value="${photo.id}"
                                        ${photo.profile ? 'checked' : ''}>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-danger btn-sm btn_eliminar_foto"
                                            data-id="${photo.id}">
                                            <i class="bi bi-trash"></i>
                                    </button>
                                       
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    filas = `
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                No hay imágenes registradas.
                            </td>
                        </tr>
                    `;
                }

                $('#preview-photos').html(filas);
            },
            error: function (xhr) {
                console.error(xhr.responseJSON ?? xhr.responseText);
                mostrar_alerta(0, 'Error al cargar las imágenes');
            }
    })};


    $(document).on('click', '.btn_eliminar_foto', function () {
        let id_foto = $(this).data('id');

        $.ajax({
            url: "{{ route('vehicles.photo.eliminar_imagen', ':id') }}".replace(':id', id_foto),
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                cargarFotos();
                refresTable();
                mostrar_alerta(1, response.mensaje, response.titulo);
            },
            error: function (xhr) {
                mostrar_alerta(0, xhr.responseJSON?.mensaje ?? 'Error al eliminar la imagen', xhr.responseJSON?.titulo);
            }
        });
    });

    $(document).on('change', '.radio_principal', function () {
        let id_foto = $(this).val();

        $.ajax({
            url: "{{ route('vehicles.photo.seleccionar', ':id') }}".replace(':id', id_foto),
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                refresTable();
                mostrar_alerta(1, response.mensaje, response.titulo);
            },
            error: function (xhr) {
                mostrar_alerta(0, xhr.responseJSON?.mensaje ?? 'Error al seleccionar la imagen principal', xhr.responseJSON?.titulo);
            }
        });
    });
    
</script>

@stop



