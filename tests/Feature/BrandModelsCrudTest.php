<?php

namespace Tests\Feature;

use App\Models\admin\Brand;
use App\Models\admin\Brandmodel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BrandModelsCrudTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('BrandModelsCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->brand = Brand::create([
            'name' => 'Marca principal',
            'description' => 'Marca para pruebas',
        ]);
    }

    public function test_authenticated_user_can_list_models_with_their_brand(): void
    {
        $model = $this->createBrandModel();

        $this->get(route('brandsmodel.index'))
            ->assertOk()
            ->assertSee('Listado de Modelos');

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('brandsmodel.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $model->id)
            ->assertJsonPath('data.0.name', 'Modelo de prueba')
            ->assertJsonPath('data.0.brand_name', 'Marca principal');
    }

    public function test_authenticated_user_can_store_a_model(): void
    {
        $this->postJson(route('brandsmodel.store'), $this->validData())
            ->assertOk()
            ->assertJson(['message' => 'Modelo registrado correctamente']);

        $this->assertDatabaseHas('brandmodels', $this->validData());
    }

    public function test_model_validation_returns_422_for_all_invalid_fields(): void
    {
        $this->postJson(route('brandsmodel.store'), [
            'brand_id' => 999999,
            'name' => str_repeat('n', 101),
            'code' => str_repeat('c', 51),
            'description' => ['no es texto'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['brand_id', 'name', 'code', 'description']);

        $this->postJson(route('brandsmodel.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brand_id', 'name']);

        $this->assertDatabaseCount('brandmodels', 0);
    }

    public function test_duplicate_model_names_are_allowed(): void
    {
        $this->createBrandModel();

        $this->postJson(route('brandsmodel.store'), $this->validData([
            'code' => 'MOD-002',
        ]))->assertOk();

        $this->assertDatabaseCount('brandmodels', 2);
    }

    public function test_authenticated_user_can_view_model_details_in_read_only_mode(): void
    {
        $model = $this->createBrandModel();

        $this->get(route('brandsmodel.show', $model))
            ->assertOk()
            ->assertSee('Detalle de modelo')
            ->assertSee('Información de solo lectura')
            ->assertSee('Marca principal')
            ->assertSee('Modelo de prueba')
            ->assertSee('MOD-001')
            ->assertSee('Descripción del modelo')
            ->assertSee($model->created_at->format('d/m/Y H:i:s'))
            ->assertSee($model->updated_at->format('d/m/Y H:i:s'))
            ->assertDontSee('<input', false)
            ->assertDontSee('<textarea', false)
            ->assertDontSee('<select', false)
            ->assertDontSee('<form', false);
    }

    public function test_create_and_edit_forms_have_expected_fields_and_selected_brand(): void
    {
        $this->get(route('brandsmodel.create'))
            ->assertOk()
            ->assertSee('name="brand_id"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="code"', false)
            ->assertSee('name="description"', false)
            ->assertSee('maxlength="100"', false)
            ->assertSee('maxlength="50"', false)
            ->assertSee('btn btn-secondary', false)
            ->assertSee('Registrar');

        $model = $this->createBrandModel();

        $this->get(route('brandsmodel.edit', $model))
            ->assertOk()
            ->assertSee(route('brandsmodel.update', $model), false)
            ->assertSee('value="'.$this->brand->id.'" selected="selected"', false)
            ->assertSee('Modelo de prueba')
            ->assertSee('MOD-001')
            ->assertSee('Descripción del modelo')
            ->assertSee('Actualizar');
    }

    public function test_authenticated_user_can_update_a_model_and_change_its_brand(): void
    {
        $model = $this->createBrandModel();
        $otherBrand = Brand::create(['name' => 'Marca secundaria']);

        $this->putJson(route('brandsmodel.update', $model), [
            'brand_id' => $otherBrand->id,
            'name' => 'Modelo actualizado',
            'code' => 'MOD-NEW',
            'description' => 'Descripción actualizada',
        ])->assertOk()
            ->assertJson(['message' => 'Modelo actualizado correctamente']);

        $this->assertDatabaseHas('brandmodels', [
            'id' => $model->id,
            'brand_id' => $otherBrand->id,
            'name' => 'Modelo actualizado',
            'code' => 'MOD-NEW',
            'description' => 'Descripción actualizada',
        ]);
    }

    public function test_model_without_related_vehicles_can_be_deleted(): void
    {
        $model = $this->createBrandModel();

        $this->deleteJson(route('brandsmodel.destroy', $model))
            ->assertOk()
            ->assertJson(['message' => 'Modelo eliminado correctamente']);

        $this->assertDatabaseMissing('brandmodels', ['id' => $model->id]);
    }

    public function test_model_with_a_related_vehicle_cannot_be_deleted(): void
    {
        $model = $this->createBrandModel();
        $typeId = DB::table('vehicletypes')->insertGetId([
            'name' => 'Tipo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $colorId = DB::table('color')->insertGetId([
            'name' => 'Color de prueba',
            'code' => '#123456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehicles')->insert([
            'code' => 'VEH-MOD-01',
            'name' => 'Vehículo relacionado',
            'plate' => 'MOD-001',
            'year' => 2026,
            'load_capacity' => 1000,
            'occupats_capacity' => 4,
            'fuel_capacity' => 80,
            'compact_capacity' => 0,
            'type_id' => $typeId,
            'brand_id' => $this->brand->id,
            'model_id' => $model->id,
            'color_id' => $colorId,
            'description' => 'Vehículo para bloqueo de eliminación',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson(route('brandsmodel.destroy', $model))
            ->assertStatus(409)
            ->assertJson([
                'message' => 'No se puede eliminar el modelo porque tiene vehículos relacionados',
            ]);

        $this->assertDatabaseHas('brandmodels', ['id' => $model->id]);
    }

    public function test_missing_models_return_404_for_resource_operations(): void
    {
        $this->getJson(route('brandsmodel.show', 999999))->assertNotFound();
        $this->getJson(route('brandsmodel.edit', 999999))->assertNotFound();
        $this->putJson(route('brandsmodel.update', 999999), $this->validData())->assertNotFound();
        $this->deleteJson(route('brandsmodel.destroy', 999999))->assertNotFound();
    }

    public function test_model_actions_have_the_required_order_icons_colors_and_routes(): void
    {
        $model = $this->createBrandModel();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('brandsmodel.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $actions = (string) $response->json('data.0.actions');

        $this->assertStringContainsString('btn btn-outline-primary btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver modelo"', $actions);
        $this->assertStringContainsString(route('brandsmodel.show', $model), $actions);
        $this->assertStringContainsString('btn btn-outline-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar modelo"', $actions);
        $this->assertStringContainsString(route('brandsmodel.edit', $model), $actions);
        $this->assertStringContainsString('btn btn-outline-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('title="Eliminar"', $actions);
        $this->assertStringContainsString('aria-label="Eliminar modelo"', $actions);
        $this->assertStringContainsString(route('brandsmodel.destroy', $model), $actions);
        $this->assertLessThan(strpos($actions, 'btnEditar'), strpos($actions, 'btnVer'));
        $this->assertLessThan(strpos($actions, 'btnEliminar'), strpos($actions, 'btnEditar'));
    }

    public function test_unauthenticated_users_cannot_access_model_routes(): void
    {
        $model = $this->createBrandModel();
        auth()->logout();

        foreach ([
            route('brandsmodel.index'),
            route('brandsmodel.create'),
            route('brandsmodel.show', $model),
            route('brandsmodel.edit', $model),
        ] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }

        $this->postJson(route('brandsmodel.store'), $this->validData())->assertUnauthorized();
        $this->putJson(route('brandsmodel.update', $model), $this->validData())->assertUnauthorized();
        $this->deleteJson(route('brandsmodel.destroy', $model))->assertUnauthorized();
    }

    private function createBrandModel(array $overrides = []): Brandmodel
    {
        return Brandmodel::create($this->validData($overrides));
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'brand_id' => $this->brand->id,
            'name' => 'Modelo de prueba',
            'code' => 'MOD-001',
            'description' => 'Descripción del modelo',
        ], $overrides);
    }
}
