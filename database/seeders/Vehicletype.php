<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class Vehicletype extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleTypes = [
            ['name' => 'Compactadora de Basura', 'description' => 'Camión recolector con sistema de compactación'],
            ['name' => 'Camión Cisterna', 'description' => 'Vehículo para transporte de agua o combustibles'],
            ['name' => 'Volquete', 'description' => 'Camión de carga pesada con tolva basculante'],
            ['name' => 'Montacargas', 'description' => 'Maquinaria industrial para elevación y movimiento de palés'],
            ['name' => 'Retroexcavadora', 'description' => 'Maquinaria de construcción para excavación y carga'],
            ['name' => 'Camión Grúa', 'description' => 'Vehículo equipado con pluma para izaje de cargas'],
            ['name' => 'Furgón', 'description' => 'Vehículo cerrado para transporte de mercancías generales'],
        ];
        DB::table('vehicletypes')->insert($vehicleTypes);

    }
}
