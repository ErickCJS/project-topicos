<?php

namespace Tests\Feature;

use Database\Seeders\ZoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZoneSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_idempotent_non_overlapping_zones_only_in_jlo(): void
    {
        $departmentId = DB::table('departments')->insertGetId(['code' => '14', 'name' => 'LAMBAYEQUE']);
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

        $zones = DB::table('zones')->where('district_id', $districtId)->orderBy('name')->get();
        $this->assertCount(4, $zones);
        $this->assertDatabaseCount('zones', 4);

        $bounds = $zones->map(function ($zone) {
            $perimeter = json_decode($zone->perimeter, true);
            $coordinates = $perimeter['coordinates'][0];

            return [
                min(array_column($coordinates, 0)),
                min(array_column($coordinates, 1)),
                max(array_column($coordinates, 0)),
                max(array_column($coordinates, 1)),
            ];
        })->all();

        foreach ($bounds as $index => $first) {
            foreach (array_slice($bounds, $index + 1) as $second) {
                $overlapWidth = min($first[2], $second[2]) - max($first[0], $second[0]);
                $overlapHeight = min($first[3], $second[3]) - max($first[1], $second[1]);
                $this->assertFalse($overlapWidth > 0 && $overlapHeight > 0);
            }
        }
    }
}