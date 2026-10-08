<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class Color extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $colors = [
            ['name' => 'Negro', 'code' => '#000000', 'description' => 'Color negro estándar'],
            ['name' => 'Blanco', 'code' => '#FFFFFF', 'description' => 'Color blanco puro'],
            ['name' => 'Rojo', 'code' => '#FF0000', 'description' => 'Rojo intenso'],
            ['name' => 'Azul', 'code' => '#0000FF', 'description' => 'Azul rey'],
            ['name' => 'Verde', 'code' => '#008000', 'description' => 'Verde bosque'],
            ['name' => 'Amarillo', 'code' => '#FFFF00', 'description' => 'Amarillo brillante'],
            ['name' => 'Gris', 'code' => '#808080', 'description' => 'Gris neutro'],
            ['name' => 'Naranja', 'code' => '#FFA500', 'description' => 'Naranja vibrante'],
        ];

        DB::table('color')->insert($colors);

    }
}
