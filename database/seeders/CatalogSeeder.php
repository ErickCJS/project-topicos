<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $brands = [
            ['name' => 'Toyota', 'description' => 'Fabricante japonés de vehículos'],
            ['name' => 'Volvo', 'description' => 'Fabricante sueco de vehículos'],
            ['name' => 'Caterpillar', 'description' => 'Fabricante de maquinaria pesada'],
            ['name' => 'Ford', 'description' => 'Fabricante estadounidense de vehículos'],
            ['name' => 'Isuzu', 'description' => 'Fabricante japonés de camiones y motores diésel'],
            ['name' => 'Mercedes-Benz', 'description' => 'Fabricante alemán de vehículos'],
            ['name' => 'Nissan', 'description' => 'Fabricante japonés de vehículos'],
            ['name' => 'Hyundai', 'description' => 'Fabricante surcoreano de vehículos'],
            ['name' => 'Komatsu', 'description' => 'Fabricante japonés de maquinaria pesada'],
            ['name' => 'Hino', 'description' => 'Fabricante japonés de camiones y autobuses'],
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->updateOrInsert(
                ['name' => $brand['name']],
                [
                    'description' => $brand['description'],
                    'logo' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $brandIds = DB::table('brands')->pluck('id', 'name')->all();

        $models = [
            ['name' => 'Hilux', 'code' => 'TOY-HIL', 'brand' => 'Toyota'],
            ['name' => 'Land Cruiser', 'code' => 'TOY-LC', 'brand' => 'Toyota'],
            ['name' => 'FMX', 'code' => 'VOL-FMX', 'brand' => 'Volvo'],
            ['name' => '320 GC', 'code' => 'CAT-320', 'brand' => 'Caterpillar'],
            ['name' => 'F-550', 'code' => 'FOR-F550', 'brand' => 'Ford'],
            ['name' => 'NPR', 'code' => 'ISU-NPR', 'brand' => 'Isuzu'],
            ['name' => 'Arocs 3345', 'code' => 'MB-AROCS', 'brand' => 'Mercedes-Benz'],
            ['name' => 'Frontier', 'code' => 'NIS-FRONT', 'brand' => 'Nissan'],
            ['name' => 'Mighty EX8', 'code' => 'HYU-EX8', 'brand' => 'Hyundai'],
            ['name' => 'PC200', 'code' => 'KOM-PC200', 'brand' => 'Komatsu'],
            ['name' => '500 Series', 'code' => 'HIN-500', 'brand' => 'Hino'],
            ['name' => 'Corolla', 'code' => 'TOY-COR', 'brand' => 'Toyota'],
            ['name' => 'FH', 'code' => 'VOL-FH', 'brand' => 'Volvo'],
            ['name' => 'D-Max', 'code' => 'ISU-DMAX', 'brand' => 'Isuzu'],
            ['name' => 'Navara', 'code' => 'NIS-NAV', 'brand' => 'Nissan'],
        ];

        foreach ($models as $model) {
            DB::table('brandmodels')->updateOrInsert(
                ['name' => $model['name'], 'brand_id' => $brandIds[$model['brand']]],
                [
                    'code' => $model['code'],
                    'description' => 'Modelo de ejemplo para ' . $model['brand'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $colors = [
            ['name' => 'Negro', 'code' => '#000000', 'description' => 'Color negro estándar'],
            ['name' => 'Blanco', 'code' => '#FFFFFF', 'description' => 'Color blanco puro'],
            ['name' => 'Rojo', 'code' => '#FF0000', 'description' => 'Rojo intenso'],
            ['name' => 'Azul', 'code' => '#0000FF', 'description' => 'Azul rey'],
            ['name' => 'Verde', 'code' => '#008000', 'description' => 'Verde bosque'],
            ['name' => 'Amarillo', 'code' => '#FFFF00', 'description' => 'Amarillo brillante'],
            ['name' => 'Gris', 'code' => '#808080', 'description' => 'Gris neutro'],
            ['name' => 'Naranja', 'code' => '#FFA500', 'description' => 'Naranja vibrante'],
            ['name' => 'Plata', 'code' => '#C0C0C0', 'description' => 'Tono plateado'],
            ['name' => 'Marrón', 'code' => '#8B4513', 'description' => 'Tono marrón'],
            ['name' => 'Celeste', 'code' => '#87CEEB', 'description' => 'Azul celeste'],
            ['name' => 'Violeta', 'code' => '#800080', 'description' => 'Tono violeta'],
        ];

        foreach ($colors as $color) {
            DB::table('color')->updateOrInsert(
                ['name' => $color['name']],
                [...$color, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $vehicleTypes = [
            ['name' => 'Compactadora de Basura', 'description' => 'Camión recolector con sistema de compactación'],
            ['name' => 'Camión Cisterna', 'description' => 'Vehículo para transporte de agua o combustibles'],
            ['name' => 'Volquete', 'description' => 'Camión de carga pesada con tolva basculante'],
            ['name' => 'Montacargas', 'description' => 'Maquinaria industrial para elevación y movimiento de palés'],
            ['name' => 'Retroexcavadora', 'description' => 'Maquinaria de construcción para excavación y carga'],
            ['name' => 'Camión Grúa', 'description' => 'Vehículo equipado con pluma para izaje de cargas'],
            ['name' => 'Furgón', 'description' => 'Vehículo cerrado para transporte de mercancías generales'],
        ];

        foreach ($vehicleTypes as $vehicleType) {
            DB::table('vehicletypes')->updateOrInsert(
                ['name' => $vehicleType['name']],
                [...$vehicleType, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $modelIds = DB::table('brandmodels')->pluck('id', 'code')->all();
        $colorIds = DB::table('color')->pluck('id', 'name')->all();
        $typeIds = DB::table('vehicletypes')->pluck('id', 'name')->all();

        $vehicles = [
            [
                'code' => 'VEH-001', 'name' => 'Camioneta de servicio', 'plate' => 'ABC-123',
                'year' => 2024, 'load_capacity' => 1000, 'occupats_capacity' => 5,
                'fuel_capacity' => 80, 'compact_capacity' => 0, 'type' => 'Furgón',
                'brand' => 'Toyota', 'model' => 'TOY-HIL', 'color' => 'Blanco',
            ],
            [
                'code' => 'VEH-002', 'name' => 'Camión de carga', 'plate' => 'DEF-456',
                'year' => 2023, 'load_capacity' => 12000, 'occupats_capacity' => 3,
                'fuel_capacity' => 300, 'compact_capacity' => 0, 'type' => 'Volquete',
                'brand' => 'Volvo', 'model' => 'VOL-FMX', 'color' => 'Amarillo',
            ],
            [
                'code' => 'VEH-003', 'name' => 'Excavadora de obra', 'plate' => 'GHI-789',
                'year' => 2022, 'load_capacity' => 5000, 'occupats_capacity' => 2,
                'fuel_capacity' => 250, 'compact_capacity' => 0, 'type' => 'Retroexcavadora',
                'brand' => 'Caterpillar', 'model' => 'CAT-320', 'color' => 'Amarillo',
            ],
            [
                'code' => 'VEH-004', 'name' => 'Camión compactador', 'plate' => 'JKL-104',
                'year' => 2024, 'load_capacity' => 8000, 'occupats_capacity' => 3,
                'fuel_capacity' => 280, 'compact_capacity' => 18, 'type' => 'Compactadora de Basura',
                'brand' => 'Volvo', 'model' => 'VOL-FMX', 'color' => 'Blanco',
            ],
            [
                'code' => 'VEH-005', 'name' => 'Camión grúa de mantenimiento', 'plate' => 'MNO-205',
                'year' => 2023, 'load_capacity' => 5000, 'occupats_capacity' => 3,
                'fuel_capacity' => 160, 'compact_capacity' => 0, 'type' => 'Camión Grúa',
                'brand' => 'Ford', 'model' => 'FOR-F550', 'color' => 'Rojo',
            ],
            [
                'code' => 'VEH-006', 'name' => 'Camión cisterna de agua', 'plate' => 'PQR-306',
                'year' => 2021, 'load_capacity' => 10000, 'occupats_capacity' => 3,
                'fuel_capacity' => 250, 'compact_capacity' => 0, 'type' => 'Camión Cisterna',
                'brand' => 'Isuzu', 'model' => 'ISU-NPR', 'color' => 'Azul',
            ],
            [
                'code' => 'VEH-007', 'name' => 'Camión volquete pesado', 'plate' => 'STU-407',
                'year' => 2025, 'load_capacity' => 18000, 'occupats_capacity' => 3,
                'fuel_capacity' => 350, 'compact_capacity' => 0, 'type' => 'Volquete',
                'brand' => 'Mercedes-Benz', 'model' => 'MB-AROCS', 'color' => 'Amarillo',
            ],
            [
                'code' => 'VEH-008', 'name' => 'Vehículo utilitario de campo', 'plate' => 'VWX-508',
                'year' => 2022, 'load_capacity' => 900, 'occupats_capacity' => 5,
                'fuel_capacity' => 80, 'compact_capacity' => 0, 'type' => 'Furgón',
                'brand' => 'Nissan', 'model' => 'NIS-FRONT', 'color' => 'Plata',
            ],
            [
                'code' => 'VEH-009', 'name' => 'Camión de reparto', 'plate' => 'YZA-609',
                'year' => 2023, 'load_capacity' => 4500, 'occupats_capacity' => 3,
                'fuel_capacity' => 120, 'compact_capacity' => 0, 'type' => 'Furgón',
                'brand' => 'Hyundai', 'model' => 'HYU-EX8', 'color' => 'Blanco',
            ],
            [
                'code' => 'VEH-010', 'name' => 'Excavadora de cadena', 'plate' => 'BCD-710',
                'year' => 2020, 'load_capacity' => 7000, 'occupats_capacity' => 1,
                'fuel_capacity' => 400, 'compact_capacity' => 0, 'type' => 'Retroexcavadora',
                'brand' => 'Komatsu', 'model' => 'KOM-PC200', 'color' => 'Amarillo',
            ],
            [
                'code' => 'VEH-011', 'name' => 'Camión de carga municipal', 'plate' => 'EFG-811',
                'year' => 2021, 'load_capacity' => 9000, 'occupats_capacity' => 3,
                'fuel_capacity' => 200, 'compact_capacity' => 0, 'type' => 'Volquete',
                'brand' => 'Hino', 'model' => 'HIN-500', 'color' => 'Verde',
            ],
            [
                'code' => 'VEH-012', 'name' => 'Sedán administrativo', 'plate' => 'HIJ-912',
                'year' => 2024, 'load_capacity' => 450, 'occupats_capacity' => 5,
                'fuel_capacity' => 50, 'compact_capacity' => 0, 'type' => 'Furgón',
                'brand' => 'Toyota', 'model' => 'TOY-COR', 'color' => 'Gris',
            ],
            [
                'code' => 'VEH-013', 'name' => 'Camión de larga distancia', 'plate' => 'KLM-013',
                'year' => 2025, 'load_capacity' => 22000, 'occupats_capacity' => 2,
                'fuel_capacity' => 500, 'compact_capacity' => 0, 'type' => 'Volquete',
                'brand' => 'Volvo', 'model' => 'VOL-FH', 'color' => 'Azul',
            ],
            [
                'code' => 'VEH-014', 'name' => 'Camioneta de inspección', 'plate' => 'NOP-114',
                'year' => 2023, 'load_capacity' => 1000, 'occupats_capacity' => 5,
                'fuel_capacity' => 76, 'compact_capacity' => 0, 'type' => 'Furgón',
                'brand' => 'Isuzu', 'model' => 'ISU-DMAX', 'color' => 'Rojo',
            ],
            [
                'code' => 'VEH-015', 'name' => 'Camioneta de operaciones', 'plate' => 'QRS-215',
                'year' => 2022, 'load_capacity' => 950, 'occupats_capacity' => 5,
                'fuel_capacity' => 80, 'compact_capacity' => 0, 'type' => 'Furgón',
                'brand' => 'Nissan', 'model' => 'NIS-NAV', 'color' => 'Negro',
            ],
        ];

        foreach ($vehicles as $vehicle) {
            DB::table('vehicles')->updateOrInsert(
                ['code' => $vehicle['code']],
                [
                    'name' => $vehicle['name'],
                    'plate' => $vehicle['plate'],
                    'year' => $vehicle['year'],
                    'load_capacity' => $vehicle['load_capacity'],
                    'occupats_capacity' => $vehicle['occupats_capacity'],
                    'fuel_capacity' => $vehicle['fuel_capacity'],
                    'compact_capacity' => $vehicle['compact_capacity'],
                    'type_id' => $typeIds[$vehicle['type']],
                    'brand_id' => $brandIds[$vehicle['brand']],
                    'model_id' => $modelIds[$vehicle['model']],
                    'color_id' => $colorIds[$vehicle['color']],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $vehicleIds = DB::table('vehicles')->pluck('id', 'code')->all();

        foreach ($vehicles as $vehicle) {
            DB::table('vehicle_images')->updateOrInsert(
                ['vehicle_id' => $vehicleIds[$vehicle['code']], 'profile' => 1],
                ['imagen' => '', 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}