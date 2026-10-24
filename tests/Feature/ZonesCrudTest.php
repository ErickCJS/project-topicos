<?php

namespace Tests\Feature;

use App\Models\admin\Department;
use App\Models\admin\District;
use App\Models\admin\Province;
use App\Models\admin\Zone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ZonesCrudTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Province $province;
    private District $district;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('ZonesCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->department = Department::create(['code' => '15', 'name' => 'Lima']);
        $this->province = Province::create([
            'department_id' => $this->department->id,
            'code' => '1501',
            'name' => 'Lima',
        ]);
        $this->district = District::create([
            'province_id' => $this->province->id,
            'code' => '150101',
            'name' => 'Lima',
        ]);
    }

    public function test_zone_crud_and_datatables_response(): void
    {
        $payload = $this->data();
        $created = $this->postJson(route('zones.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('message', 'Zona registrada correctamente.');
        $zone = Zone::firstOrFail();

        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
            'name' => 'Zona Centro',
            'district_id' => $this->district->id,
        ]);
        $this->assertSame('Polygon', $zone->perimeter['type']);

        $listing = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('zones.index', ['draw' => 1, 'start' => 0, 'length' => 10]));
        $listing->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.name', 'Zona Centro')
            ->assertJsonPath('data.0.department', 'Lima')
            ->assertJsonPath('data.0.province', 'Lima')
            ->assertJsonPath('data.0.district', 'Lima')
            ->assertJsonPath('data.0.perimeter_status', 'Definido');
        $this->assertStringContainsString('btnVer', (string) $listing->json('data.0.actions'));
        $this->assertStringContainsString('btnEditar', (string) $listing->json('data.0.actions'));
        $this->assertStringContainsString('btnEliminar', (string) $listing->json('data.0.actions'));

        $this->get(route('zones.create'))->assertOk()->assertSee('Nueva zona');
        $this->get(route('zones.show', $zone))->assertOk()->assertSee('Detalle de zona')->assertSee('Zona Centro');
        $this->get(route('zones.edit', $zone))->assertOk()->assertSee('Editar zona');

        $this->putJson(route('zones.update', $zone), $this->data([
            'name' => 'Zona Norte',
            'description' => 'Descripción actualizada',
        ]))->assertOk()->assertJsonPath('message', 'Zona actualizada correctamente.');
        $this->assertDatabaseHas('zones', ['id' => $zone->id, 'name' => 'Zona Norte']);

        $this->deleteJson(route('zones.destroy', $zone))->assertOk();
        $this->assertDatabaseMissing('zones', ['id' => $zone->id]);
    }

    public function test_required_fields_and_perimeter_geometry_are_validated_on_server(): void
    {
        $this->postJson(route('zones.store'), [])->assertUnprocessable()->assertJsonValidationErrors([
            'name', 'department_id', 'province_id', 'district_id', 'perimeter',
        ]);

        $this->postJson(route('zones.store'), $this->data([
            'perimeter' => json_encode(['type' => 'Polygon', 'coordinates' => [[[-79, -12], [-79, -12], [-79, -12], [-79, -12]]]]),
        ]))->assertUnprocessable()->assertJsonValidationErrors('perimeter');

        $this->postJson(route('zones.store'), $this->data([
            'perimeter' => json_encode(['type' => 'Polygon', 'coordinates' => [[[-181, -12], [-79, -12], [-79, -11], [-181, -12]]]]),
        ]))->assertUnprocessable()->assertJsonValidationErrors('perimeter');

        $this->postJson(route('zones.store'), $this->data([
            'perimeter' => json_encode(['type' => 'Polygon', 'coordinates' => [[[-79, -12], [-79, -11], [-78, -11], [-78, -10]]]]),
        ]))->assertUnprocessable()->assertJsonValidationErrors('perimeter');
    }

    public function test_district_must_belong_to_selected_province_and_department(): void
    {
        $otherDepartment = Department::create(['code' => '04', 'name' => 'Arequipa']);
        $otherProvince = Province::create([
            'department_id' => $otherDepartment->id,
            'code' => '0401',
            'name' => 'Arequipa',
        ]);
        $otherDistrict = District::create([
            'province_id' => $otherProvince->id,
            'code' => '040101',
            'name' => 'Arequipa',
        ]);

        $this->postJson(route('zones.store'), $this->data([
            'province_id' => $this->province->id,
            'district_id' => $otherDistrict->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('province_id');

        $this->postJson(route('zones.store'), $this->data([
            'department_id' => $this->department->id,
            'province_id' => $otherProvince->id,
            'district_id' => $otherDistrict->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('department_id');
    }

    public function test_dependent_geography_endpoints_return_related_records(): void
    {
        $this->getJson(route('zones.provinces', $this->department))
            ->assertOk()->assertJsonPath('0.id', $this->province->id)->assertJsonPath('0.code', '1501');
        $this->getJson(route('zones.districts', $this->province))
            ->assertOk()->assertJsonPath('0.id', $this->district->id)->assertJsonPath('0.code', '150101');
    }

    public function test_zone_routes_require_authentication(): void
    {
        $zone = Zone::create(array_merge($this->data(), ['perimeter' => $this->geometry()]));
        auth()->logout();

        $this->getJson(route('zones.index'))->assertUnauthorized();
        $this->postJson(route('zones.store'), $this->data())->assertUnauthorized();
        $this->getJson(route('zones.provinces', $this->department))->assertUnauthorized();
        $this->getJson(route('zones.districts', $this->province))->assertUnauthorized();
        $this->getJson(route('zones.show', $zone))->assertUnauthorized();
        $this->putJson(route('zones.update', $zone), $this->data())->assertUnauthorized();
        $this->deleteJson(route('zones.destroy', $zone))->assertUnauthorized();
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Zona Centro',
            'description' => 'Zona de prueba',
            'department_id' => $this->department->id,
            'province_id' => $this->province->id,
            'district_id' => $this->district->id,
            'perimeter' => $this->geometry(),
        ], $overrides);
    }

    private function geometry(): string
    {
        return json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [-77.04, -12.05],
                [-77.03, -12.05],
                [-77.03, -12.04],
                [-77.04, -12.05],
            ]],
        ]);
    }
}
