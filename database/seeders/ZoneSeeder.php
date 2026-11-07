<?php

namespace Database\Seeders;

use App\Models\admin\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ZoneSeeder extends Seeder
{
    private const SECTORS = [
        [
            'name' => 'JLO - Sector 01',
            'coordinates' => [[-79.850, -6.750], [-79.835, -6.750], [-79.835, -6.745], [-79.850, -6.745], [-79.850, -6.750]],
        ],
        [
            'name' => 'JLO - Sector 02',
            'coordinates' => [[-79.835, -6.750], [-79.820, -6.750], [-79.820, -6.745], [-79.835, -6.745], [-79.835, -6.750]],
        ],
        [
            'name' => 'JLO - Sector 03',
            'coordinates' => [[-79.850, -6.745], [-79.835, -6.745], [-79.835, -6.740], [-79.850, -6.740], [-79.850, -6.745]],
        ],
        [
            'name' => 'JLO - Sector 04',
            'coordinates' => [[-79.835, -6.745], [-79.820, -6.745], [-79.820, -6.740], [-79.835, -6.740], [-79.835, -6.745]],
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
            throw new RuntimeException('No se encontro el UBIGEO 140105 (Jose Leonardo Ortiz, Chiclayo, Lambayeque). Ejecute primero PeruGeographySeeder.');
        }

        DB::transaction(function () use ($district): void {
            foreach (self::SECTORS as $sector) {
                $zone = Zone::query()->firstOrNew([
                    'district_id' => $district->id,
                    'name' => $sector['name'],
                ]);
                $perimeter = ['type' => 'Polygon', 'coordinates' => [$sector['coordinates']]];

                $this->assertNoOverlap($perimeter, $zone->exists ? $zone->id : null);

                $zone->description = 'Sector operativo de Jose Leonardo Ortiz.';
                $zone->perimeter = $perimeter;
                $zone->save();
            }
        });

        $this->command?->info('Se crearon o actualizaron 4 sectores operativos de Jose Leonardo Ortiz.');
    }

    private function assertNoOverlap(array $perimeter, ?int $zoneId): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $geojson = json_encode($perimeter, JSON_UNESCAPED_SLASHES);
        $valid = DB::selectOne('SELECT ST_IsValid(ST_GeomFromGeoJSON(?)) AS valid', [$geojson]);
        if (! $valid || ! $valid->valid) {
            throw new RuntimeException('El perimetro de un sector JLO no es un poligono valido.');
        }

        $conflict = Zone::query()->whereNotNull('perimeter')
            ->whereRaw(
                'ST_Intersects(ST_GeomFromGeoJSON(perimeter), ST_GeomFromGeoJSON(?)) = 1 AND ST_Touches(ST_GeomFromGeoJSON(perimeter), ST_GeomFromGeoJSON(?)) = 0',
                [$geojson, $geojson]
            )
            ->when($zoneId, fn ($query) => $query->whereKeyNot($zoneId))
            ->first(['id', 'name']);

        if ($conflict) {
            throw new RuntimeException('El sector JLO se superpone con la zona existente "'.$conflict->name.'".');
        }
    }
}