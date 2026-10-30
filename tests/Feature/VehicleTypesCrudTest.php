<?php

namespace Tests\Feature;

use App\Models\admin\Vehicletype;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class VehicleTypesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('VehicleTypesCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_authenticated_user_can_list_vehicle_types_with_datatables(): void
    {
        $type = $this->createVehicleType();

        $this->get(route('vehiclestype.index'))
            ->assertOk()
            ->assertSee('Listado de Tipos de Vehículo')
            ->assertSee('Información de solo lectura')
            ->assertSee('id="vehicleTypeShowModal"', false);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vehiclestype.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $type->id)
            ->assertJsonPath('data.0.name', 'Camión')
            ->assertJsonPath('data.0.description', 'Vehículo de carga');

        $this->assertArrayHasKey('actions', $response->json('data.0'));
        $this->assertArrayNotHasKey('edit', $response->json('data.0'));
        $this->assertArrayNotHasKey('delete', $response->json('data.0'));
    }

    public function test_authenticated_user_can_store_a_vehicle_type(): void
    {
        $this->postJson(route('vehiclestype.store'), $this->validData())
            ->assertOk()
            ->assertJson(['mensaje' => 'Tipo de vehículo registrado correctamente']);

        $this->assertDatabaseHas('vehicletypes', $this->validData());
    }

    public function test_vehicle_type_validation_returns_422(): void
    {
        $this->postJson(route('vehiclestype.store'), [
            'name' => str_repeat('n', 101),
            'description' => str_repeat('d', 101),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description']);

        $this->postJson(route('vehiclestype.store'), [
            'name' => ['no es texto'],
            'description' => ['no es texto'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description']);

        $this->postJson(route('vehiclestype.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('vehicletypes', 0);
    }

    public function test_duplicate_vehicle_type_names_are_rejected(): void
    {
        $this->createVehicleType();

        $this->postJson(route('vehiclestype.store'), $this->validData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('vehicletypes', 1);
    }

    public function test_authenticated_user_can_view_vehicle_type_details(): void
    {
        $type = $this->createVehicleType();

        $this->getJson(route('vehiclestype.show', $type))
            ->assertOk()
            ->assertJsonPath('id', $type->id)
            ->assertJsonPath('name', 'Camión')
            ->assertJsonPath('description', 'Vehículo de carga')
            ->assertJsonStructure(['created_at', 'updated_at']);
    }

    public function test_create_and_edit_forms_keep_fields_limits_and_existing_values(): void
    {
        $this->get(route('vehiclestype.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false)
            ->assertSee('maxlength="100"', false)
            ->assertSee('btn btn-secondary', false)
            ->assertSee('Registrar');

        $type = $this->createVehicleType();

        $this->get(route('vehiclestype.edit', $type))
            ->assertOk()
            ->assertSee(route('vehiclestype.update', $type), false)
            ->assertSee('Camión')
            ->assertSee('Vehículo de carga')
            ->assertSee('Actualizar');
    }

    public function test_authenticated_user_can_update_a_vehicle_type(): void
    {
        $type = $this->createVehicleType();

        $this->putJson(route('vehiclestype.update', $type), [
            'name' => 'Camión pesado',
            'description' => 'Tipo actualizado',
        ])->assertOk()
            ->assertJson(['mensaje' => 'Tipo de vehículo actualizado correctamente']);

        $this->assertDatabaseHas('vehicletypes', [
            'id' => $type->id,
            'name' => 'Camión pesado',
            'description' => 'Tipo actualizado',
        ]);

        $this->putJson(route('vehiclestype.update', $type), [
            'name' => 'Camión pesado',
            'description' => 'Conserva su propio nombre',
        ])->assertOk();
    }

    public function test_vehicle_type_without_related_vehicles_can_be_deleted(): void
    {
        $type = $this->createVehicleType();

        $this->deleteJson(route('vehiclestype.destroy', $type))
            ->assertOk()
            ->assertJson(['mensaje' => 'Tipo de vehículo eliminado correctamente']);

        $this->assertDatabaseMissing('vehicletypes', ['id' => $type->id]);
    }

    public function test_vehicle_type_with_a_related_vehicle_cannot_be_deleted(): void
    {
        $type = $this->createVehicleType();
        $brandId = DB::table('brands')->insertGetId([
            'name' => 'Marca para tipo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $modelId = DB::table('brandmodels')->insertGetId([
            'name' => 'Modelo para tipo',
            'code' => 'MOD-TYPE',
            'brand_id' => $brandId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $colorId = DB::table('color')->insertGetId([
            'name' => 'Color para tipo',
            'code' => '#123456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehicles')->insert([
            'code' => 'VEH-TYPE',
            'name' => 'Vehículo relacionado',
            'plate' => 'TIP-001',
            'year' => 2026,
            'load_capacity' => 1000,
            'occupats_capacity' => 4,
            'fuel_capacity' => 80,
            'compact_capacity' => 0,
            'type_id' => $type->id,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'color_id' => $colorId,
            'description' => 'Vehículo para bloqueo del tipo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson(route('vehiclestype.destroy', $type))
            ->assertStatus(409)
            ->assertJson([
                'mensaje' => 'No se puede eliminar este tipo porque tiene vehículos asociados.',
            ]);

        $this->assertDatabaseHas('vehicletypes', ['id' => $type->id]);
        $this->assertDatabaseHas('vehicles', ['type_id' => $type->id]);
    }

    public function test_missing_vehicle_types_return_404_for_resource_operations(): void
    {
        $this->getJson(route('vehiclestype.show', 999999))->assertNotFound();
        $this->getJson(route('vehiclestype.edit', 999999))->assertNotFound();
        $this->putJson(route('vehiclestype.update', 999999), $this->validData())->assertNotFound();
        $this->deleteJson(route('vehiclestype.destroy', 999999))->assertNotFound();
    }

    public function test_vehicle_type_actions_have_required_order_icons_colors_and_routes(): void
    {
        $type = $this->createVehicleType();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vehiclestype.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $actions = (string) $response->json('data.0.actions');

        $this->assertStringContainsString('btn btn-outline-primary btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver tipo de vehículo"', $actions);
        $this->assertStringContainsString(route('vehiclestype.show', $type), $actions);
        $this->assertStringContainsString('btn btn-outline-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar tipo de vehículo"', $actions);
        $this->assertStringContainsString(route('vehiclestype.edit', $type), $actions);
        $this->assertStringContainsString('btn btn-outline-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('title="Eliminar"', $actions);
        $this->assertStringContainsString('aria-label="Eliminar tipo de vehículo"', $actions);
        $this->assertStringContainsString(route('vehiclestype.destroy', $type), $actions);
        $this->assertLessThan(strpos($actions, 'btnEditar'), strpos($actions, 'btnVer'));
        $this->assertLessThan(strpos($actions, 'btnEliminar'), strpos($actions, 'btnEditar'));
    }

    public function test_unauthenticated_users_cannot_access_vehicle_type_routes(): void
    {
        $type = $this->createVehicleType();
        auth()->logout();

        foreach ([
            route('vehiclestype.index'),
            route('vehiclestype.create'),
            route('vehiclestype.show', $type),
            route('vehiclestype.edit', $type),
        ] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }

        $this->postJson(route('vehiclestype.store'), $this->validData())->assertUnauthorized();
        $this->putJson(route('vehiclestype.update', $type), $this->validData())->assertUnauthorized();
        $this->deleteJson(route('vehiclestype.destroy', $type))->assertUnauthorized();
    }

    public function test_all_existing_vehicle_type_routes_are_preserved(): void
    {
        $routes = app('router')->getRoutes();
        $expectedRoutes = [
            'vehiclestype.index',
            'vehiclestype.create',
            'vehiclestype.store',
            'vehiclestype.show',
            'vehiclestype.edit',
            'vehiclestype.update',
            'vehiclestype.destroy',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertNotNull($routes->getByName($routeName), "Falta la ruta {$routeName}.");
        }
    }

    private function createVehicleType(array $overrides = []): Vehicletype
    {
        return Vehicletype::create($this->validData($overrides));
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Camión',
            'description' => 'Vehículo de carga',
        ], $overrides);
    }
}
