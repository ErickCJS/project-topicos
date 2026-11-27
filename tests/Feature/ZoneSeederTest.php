<?php

namespace Tests\Feature;

use Database\Seeders\ZoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZoneSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_sixteen_idempotent_referential_zones_only_in_jlo(): void
    {
        $departmentId = DB::table('departments')->insertGetId([
            'code' => '14',
            'name' => 'LAMBAYEQUE',
        ]);
        $provinceId = DB::table('provinces')->insertGetId([
            'department_id' => $departmentId,
            'code' => '1401',
            'name' => 'CHICLAYO',
        ]);
        $districtId = DB::table('districts')->insertGetId([
            'province_id' => $provinceId,
            'code' => '140105',
            'name' => 'JOSE LEONARDO ORTIZ',
        ]);

        $this->seed(ZoneSeeder::class);
        $this->seed(ZoneSeeder::class);

        $zones = DB::table('zones')
            ->where('district_id', $districtId)
            ->orderBy('name')
            ->get();

        $this->assertCount(16, $zones);
        $this->assertDatabaseCount('zones', 16);
        $this->assertSame(
            array_fill(0, 16, $districtId),
            $zones->pluck('district_id')->map(fn ($id) => (int) $id)->all()
        );

        foreach ($zones as $zone) {
            $perimeter = json_decode($zone->perimeter, true);

            $this->assertSame('Polygon', $perimeter['type']);
            $ring = $perimeter['coordinates'][0];
            $this->assertGreaterThanOrEqual(5, count($ring));
            $this->assertSame($ring[0], $ring[count($ring) - 1]);

            $uniqueVertices = collect(array_slice($ring, 0, -1))
                ->map(fn ($coordinate) => implode(',', $coordinate))
                ->unique()
                ->count();

            $this->assertGreaterThanOrEqual(4, $uniqueVertices);
            $this->assertStringContainsString('referencial', $zone->description);
        }
    }
}