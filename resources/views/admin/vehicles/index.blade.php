@extends('adminlte::page')

@section('title', 'Vehículos')

@section('content_header')
    <h1>Listado de vehículos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-striped" id="vehiclesTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Placa</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Color</th>
                        <th>Tipo</th>
                        <th>Actualización</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function () {
            $('#vehiclesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: @json(route('vehicles.index')),
                columns: [
                    { data: 'code', name: 'vehicles.code' },
                    { data: 'name', name: 'vehicles.name' },
                    { data: 'plate', name: 'vehicles.plate' },
                    { data: 'brand', name: 'brands.name' },
                    { data: 'model', name: 'brandmodels.name' },
                    { data: 'color', name: 'color.name' },
                    { data: 'type', name: 'vehicletypes.name' },
                    { data: 'updated_at', name: 'vehicles.updated_at' }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json'
                }
            });
        });
    </script>
@stop
