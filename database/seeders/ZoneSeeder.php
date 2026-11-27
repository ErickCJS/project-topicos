<?php

namespace Database\Seeders;

use App\Models\admin\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ZoneSeeder extends Seeder
{
    /**
     * Sectores operativos referenciales de JLO.
     *
     * Los polígonos se construyeron como particiones irregulares alrededor
     * de puntos de referencia de localidades del distrito. No son límites
     * catastrales ni sustituyen una cartografía municipal oficial.
     */
    private const SECTORS = [
        [
            'name' => 'JLO - Sector 01',
            'reference' => 'Chilape',
            'coordinates' => [
                [-79.833216, -6.737191], [-79.842426, -6.737954], [-79.844092, -6.732494],
                [-79.838693, -6.730859], [-79.837143, -6.731002], [-79.836266, -6.731572],
                [-79.831886, -6.735820], [-79.833216, -6.737191],
            ],
        ],
        [
            'name' => 'JLO - Sector 02',
            'reference' => 'Culpón',
            'coordinates' => [
                [-79.842426, -6.737954], [-79.842796, -6.738771], [-79.846287, -6.741054],
                [-79.852442, -6.740770], [-79.850966, -6.735658], [-79.850380, -6.734759],
                [-79.849236, -6.734065], [-79.844092, -6.732494], [-79.842426, -6.737954],
            ],
        ],
        [
            'name' => 'JLO - Sector 03',
            'reference' => 'El Paraíso',
            'coordinates' => [
                [-79.842426, -6.737954], [-79.833216, -6.737191], [-79.833598, -6.745090],
                [-79.838817, -6.745863], [-79.842796, -6.738771], [-79.842426, -6.737954],
            ],
        ],
        [
            'name' => 'JLO - Sector 04',
            'reference' => 'San José',
            'coordinates' => [
                [-79.829294, -6.749053], [-79.833598, -6.745090], [-79.833216, -6.737191],
                [-79.831886, -6.735820], [-79.827327, -6.740260], [-79.823837, -6.745255],
                [-79.829294, -6.749053],
            ],
        ],
        [
            'name' => 'JLO - UPIS San Miguel',
            'reference' => 'San Miguel',
            'coordinates' => [
                [-79.846287, -6.741054], [-79.842796, -6.738771], [-79.838817, -6.745863],
                [-79.841613, -6.749883], [-79.845977, -6.745099], [-79.846287, -6.741054],
            ],
        ],
        [
            'name' => 'JLO - Miraflores',
            'reference' => 'Miraflores',
            'coordinates' => [
                [-79.846287, -6.741054], [-79.845977, -6.745099], [-79.849813, -6.748387],
                [-79.854110, -6.746601], [-79.852442, -6.740770], [-79.846287, -6.741054],
            ],
        ],
        [
            'name' => 'JLO - Ramiro Prialé',
            'reference' => 'Ramiro Prialé',
            'coordinates' => [
                [-79.841613, -6.749883], [-79.844945, -6.755302], [-79.849813, -6.748387],
                [-79.845977, -6.745099], [-79.841613, -6.749883],
            ],
        ],
        [
            'name' => 'JLO - UPIS 1 de Mayo',
            'reference' => '1 de Mayo',
            'coordinates' => [
                [-79.838817, -6.745863], [-79.833598, -6.745090], [-79.829294, -6.749053],
                [-79.829706, -6.751764], [-79.831703, -6.753196], [-79.841647, -6.750093],
                [-79.838817, -6.745863],
            ],
        ],
        [
            'name' => 'JLO - A.H. Nuevo San Lorenzo',
            'reference' => 'Nuevo San Lorenzo',
            'coordinates' => [
                [-79.849813, -6.748387], [-79.844945, -6.755302], [-79.845328, -6.757612],
                [-79.845647, -6.758066], [-79.855971, -6.753108], [-79.854110, -6.746601],
                [-79.849813, -6.748387],
            ],
        ],
        [
            'name' => 'JLO - Urb. San Carlos',
            'reference' => 'San Carlos',
            'coordinates' => [
                [-79.845647, -6.758066], [-79.845328, -6.757612], [-79.837063, -6.757364],
                [-79.835225, -6.764008], [-79.837870, -6.763829], [-79.846443, -6.762162],
                [-79.845647, -6.758066],
            ],
        ],
        [
            'name' => 'JLO - Villa Hermosa',
            'reference' => 'Villa Hermosa',
            'coordinates' => [
                [-79.829706, -6.751764], [-79.829294, -6.749053], [-79.823837, -6.745255],
                [-79.820360, -6.750277], [-79.820019, -6.751595], [-79.820361, -6.752913],
                [-79.823663, -6.757670], [-79.829706, -6.751764],
            ],
        ],
        [
            'name' => 'JLO - Quinta Santa María',
            'reference' => 'Quinta Santa María',
            'coordinates' => [
                [-79.845647, -6.758066], [-79.846443, -6.762162], [-79.855524, -6.760345],
                [-79.856741, -6.759361], [-79.857213, -6.757869], [-79.855971, -6.753108],
                [-79.845647, -6.758066],
            ],
        ],
        [
            'name' => 'JLO - Santa Isabel',
            'reference' => 'Santa Isabel',
            'coordinates' => [
                [-79.831703, -6.753196], [-79.829706, -6.751764], [-79.823663, -6.757670],
                [-79.825417, -6.760184], [-79.832099, -6.760171], [-79.833518, -6.755966],
                [-79.831703, -6.753196],
            ],
        ],
        [
            'name' => 'JLO - Santa Rosa',
            'reference' => 'Santa Rosa',
            'coordinates' => [
                [-79.832099, -6.760171], [-79.825417, -6.760184], [-79.827857, -6.763583],
                [-79.828793, -6.764161], [-79.829604, -6.764323], [-79.832976, -6.764140],
                [-79.832099, -6.760171],
            ],
        ],
        [
            'name' => 'JLO - Urrunaga',
            'reference' => 'Urrunaga',
            'coordinates' => [
                [-79.841647, -6.750093], [-79.831703, -6.753196], [-79.833518, -6.755966],
                [-79.837063, -6.757364], [-79.845328, -6.757612], [-79.844945, -6.755302],
                [-79.841647, -6.750093],
            ],
        ],
        [
            'name' => 'JLO - Zona Central',
            'reference' => 'José Leonardo Ortiz',
            'coordinates' => [
                [-79.832099, -6.760171], [-79.832976, -6.764140], [-79.835225, -6.764008],
                [-79.837063, -6.757364], [-79.833518, -6.755966], [-79.832099, -6.760171],
            ],
        ],
    ];

    public function run(): void
    {
        $district = DB::table('districts')
            ->join('provinces', 'districts.province_id', '=', 'provinces.id')
            ->join('departments', 'provinces.department_id', '=', 'departments.id')
            ->where('departments.code', '14')
            ->where('provinces.code', '1401')
            ->where('districts.code', '140105')
            ->select('districts.id')
            ->first();

        if (! $district) {
            throw new RuntimeException('No se encontró el UBIGEO 140105 (José Leonardo Ortiz, Chiclayo, Lambayeque). Ejecute primero PeruGeographySeeder.');
        }

        DB::transaction(function () use ($district): void {
            // Evita que los perímetros antiguos de estos mismos sectores
            // choquen temporalmente mientras se actualiza la geometría completa.
            $targetNames = array_column(self::SECTORS, 'name');
            Zone::query()
                ->where('district_id', $district->id)
                ->whereIn('name', $targetNames)
                ->update(['perimeter' => null]);

            foreach (self::SECTORS as $sector) {
                $zone = Zone::query()->firstOrNew([
                    'district_id' => $district->id,
                    'name' => $sector['name'],
                ]);

                $perimeter = [
                    'type' => 'Polygon',
                    'coordinates' => [$sector['coordinates']],
                ];

                $this->assertNoOverlap($perimeter, $zone->exists ? $zone->id : null, (int) $district->id);

                $zone->description = 'Zona operativa referencial de José Leonardo Ortiz, organizada alrededor de '
                    . $sector['reference']
                    . '. El contorno es aproximado para planificar recorridos y debe validarse en campo; no representa un límite catastral oficial.';
                $zone->perimeter = $perimeter;
                $zone->save();
            }
        });

        $this->command?->info('Se crearon o actualizaron 16 sectores operativos referenciales de José Leonardo Ortiz.');
    }

    private function assertNoOverlap(array $perimeter, ?int $zoneId, int $districtId): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $geojson = json_encode($perimeter, JSON_UNESCAPED_SLASHES);
        $valid = DB::selectOne('SELECT ST_IsValid(ST_GeomFromGeoJSON(?)) AS valid', [$geojson]);
        if (! $valid || ! $valid->valid) {
            throw new RuntimeException('El perímetro de un sector JLO no es un polígono válido.');
        }

        $conflict = Zone::query()
            ->where('district_id', $districtId)
            ->whereNotNull('perimeter')
            ->whereRaw(
                'ST_Intersects(ST_GeomFromGeoJSON(perimeter), ST_GeomFromGeoJSON(?)) = 1 AND ST_Touches(ST_GeomFromGeoJSON(perimeter), ST_GeomFromGeoJSON(?)) = 0',
                [$geojson, $geojson]
            )
            ->when($zoneId, fn ($query) => $query->whereKeyNot($zoneId))
            ->first(['id', 'name']);

        if ($conflict) {
            throw new RuntimeException('El sector JLO se superpone con la zona existente "' . $conflict->name . '".');
        }
    }
}