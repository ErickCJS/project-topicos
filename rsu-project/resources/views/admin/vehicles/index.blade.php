@extends('adminlte::page')

@section('title', 'RSUProject')

@section('content')
    <div class="card">
        <div class="card-header d-flex">
            <div class="w-50">
                <h4>Listado de Marcas</h4>
            </div>
            <div class="w-50 d-flex justify-content-end">
                <button class="btn btn-success" id="btnNuevo"><i class="bi bi-plus-circle"></i> Nueva
                    Marca</button>
            </div>

        </div>
        <div class="card-body">
            <table class="table table-striped" id="DataTable">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Código</th>
            <th>Placa</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Color</th>
            <th>Tipo</th>

            <th>Editar</th>
            <th>Eliminar</th>
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
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
<script>

    var table;

    // ==========================================
    // NUEVA MARCA
    // ==========================================

    $('#btnNuevo').click(function () {

        $.ajax({

            url: "{{ route('vehicles.create') }}",
            type: "GET",

            success: function (response) {

                $('#formModal #formModalLabel').html("Nuevo vehículo");

                $('#formModal .modal-body').html(response);

                $('#formModal').modal('show');


                $("#formModal form").on('submit', function (e) {

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

                            // Cerrar modal
                            $('#formModal').modal('hide');


                            // Recargar DataTable
                            refresTable();


                            Swal.fire({
                                title: "Proceso exitoso!",
                                icon: "success",
                                text: response.message,
                                draggable: true
                            });

                        },


                        error: function (err) {

                            console.log(err);

                            var response = err.responseJSON;

                            Swal.fire({
                                title: "Ocurrió un error!",
                                icon: "error",
                                text: response?.message ?? "Ocurrió un error.",
                                draggable: true
                            });

                        }

                    });

                });

            }

        });

    });


    // ==========================================
    // EDITAR
    // ==========================================

    $(document).on('click', '.btnEditar', function () {

        var id = $(this).data('id');

        $.ajax({

            url: "{{ route('vehicles.edit', ':id') }}".replace(':id', id),

            type: "GET",

            success: function (response) {

                $('#formModal #formModalLabel').html("Editar Marca");

                $('#formModal .modal-body').html(response);

                $('#formModal').modal('show');

            }

        });

    });


    // ==========================================
    // ELIMINAR
    // ==========================================

    $(document).on('submit', '.frmEliminar', function (e) {

        e.preventDefault();

        var form = this;

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

                form.submit();

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

            columns: [
                {
                    data: "name"
                },

                {
                    data: "plate"
                },

                {
                    data: "brand"
                },
                  {
                    data: "model"
                },
                  {
                    data: "color"
                },
                ,
                  {
                    data: "type"
                }
                {
                    data: "updated_at"
                },

                {
                    data: "edit",
                    orderable: false,
                    searchable: false
                },

                {
                    data: "delete",
                    orderable: false,
                    searchable: false
                }

            ],

            language: {
                url: "https://cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
            }

        });

    });


    // ==========================================
    // RECARGAR TABLA
    // ==========================================

    function refresTable() {

        table.ajax.reload(null, false);

    }

</script>



    @if (session('success') != null)
        <script>
            Swal.fire({
                title: "Proceso exitoso!",
                icon: "success",
                text: '{{ session('success') }}',
                draggable: true
            });
        </script>
    @endif

    @if (session('error') != null)
        <script>
            Swal.fire({
                title: "Ocurrió un error!",
                icon: "error",
                text: '{{ session('error') }}',
                draggable: true
            });
        </script>
    @endif

@stop
