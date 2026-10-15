@extends('adminlte::page')

@section('title', 'RSUProject')

@section('content')
    <div class="card">
        <div class="card-header d-flex">
            <div class="w-50">
                <h4>Listado de Asistencias</h4>
            </div>
            <div class="w-50 d-flex justify-content-end">
                <button class="btn btn-success" id="btnNuevo"><i class="fas fa-plus-circle"></i> Agregar nueva asistencia</button>
            </div>

        </div>
        <div class="card-body">
            <table class="table table-striped" id="DataTable">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Empleado</th>
                        <th>Fecha y hora</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Turno</th>
                        <th>Notas</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    
                </tbody>
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

    
@stop

@section('css')
<style>
    .brand-logo-preview {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        background: transparent;
        border: 0;
        border-radius: .25rem;
        cursor: zoom-in;
    }

    .brand-logo-preview img {
        width: 70px;
        height: 50px;
        object-fit: contain;
    }

    .brand-logo-preview:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }
</style>
@stop

@section('js')
<script>

    function mostrar_modal (titulo, response){
        $('#formModal #formModalLabel').html(titulo);
        $('#formModal .modal-body').html(response);
        $('#formModal').modal('show');
    }
    function cerrar_modal(){
        $('#formModal').modal('hide');
    }


    function mostrar_alerta(tipo, mensaje){
        var icono = tipo == 0 ? 'error' : 'success';
        var titulo = tipo == 0 ? 'Ocurrió un error!' : 'Proceso exitoso!';
        Swal.fire({
            title: titulo,
            icon: icono,
            text: mensaje,
            draggable: true
        });
    }

    var table;

    // ==========================================
    // NUEVA MARCA
    // ==========================================

    $('#btnNuevo').click(function () {

        $.ajax({
            url: "{{ route('brands.create') }}",
            type: "GET",
            success: function (response) {
                mostrar_modal('Nueva Marca', response);
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
                //cerramos el modal
                cerrar_modal();
                var response = err.responseJSON;
                mostrar_alerta(0, response.mensaje);
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
            url: "{{ route('brands.edit', ':id') }}".replace(':id', id),
            type: "GET",
            success: function (response) {
                mostrar_modal('Editar Marca',response);
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
                    url: "{{ route('brands.destroy', ':id') }}".replace(':id', id),
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

            ajax: "{{ route('attendances.index') }}",

            columns: [

                {
                    data: "dni",
                    orderable: false,
                    searchable: false
                },

                {
                    data: "employee_name"
                },

                {
                    data: "date_time"
                },

                {
                    data: "type"
                },

                {
                    data: "status"
                },

                {
                    data: "shift_name"
                },

                {
                    data: "notes",
                    defaultContent: ""
                },

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

        $('#DataTable').on('click', '.brand-logo-preview', function () {
            var brandName = $(this).data('brand');
            $('#brandLogoModalLabel').text('Logo de ' + brandName);
            $('#brandLogoModalImage')
                .attr('src', $(this).data('image'))
                .attr('alt', 'Logo de ' + brandName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('brandLogoModal')).show();
        });

        $('#brandLogoModal').on('hidden.bs.modal', function () {
            $('#brandLogoModalImage').attr('src', '').attr('alt', '');
        });

    });


    // ==========================================
    // RECARGAR TABLA
    // ==========================================

    function refresTable() {

        table.ajax.reload(null, false);

    }

</script>

@stop
