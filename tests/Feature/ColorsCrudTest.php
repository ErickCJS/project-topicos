<?php

namespace Tests\Feature;

use App\Models\admin\Color;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ColorsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('ColorsCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_authenticated_user_can_list_colors_with_datatables(): void
    {
        $color = $this->createColor();

        $this->get(route('vehiclescolor.index'))
            ->assertOk()
            ->assertSee('Listado de Colores')
            ->assertSee('id="modalVerColor"', false)
            ->assertSee('Información de solo lectura');

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vehiclescolor.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $color->id)
            ->assertJsonPath('data.0.name', 'Azul de prueba')
            ->assertJsonPath('data.0.code', '#104CAD');

        $this->assertStringContainsString('background-color:#104CAD', (string) $response->json('data.0.preview'));
    }

    public function test_authenticated_user_can_store_a_color(): void
    {
        $this->postJson(route('vehiclescolor.store'), $this->validData())
            ->assertCreated()
            ->assertJson(['mensaje' => 'Color creado correctamente.']);

        $this->assertDatabaseHas('color', $this->validData());
    }

    public function test_hexadecimal_codes_are_normalized_with_prefix_and_uppercase(): void
    {
        $this->postJson(route('vehiclescolor.store'), [
            'name' => 'Color corto',
            'code' => 'a1b',
            'description' => 'Formato corto',
        ])->assertCreated();

        $this->postJson(route('vehiclescolor.store'), [
            'name' => 'Color largo',
            'code' => '12abef',
            'description' => 'Formato largo',
        ])->assertCreated();

        $this->assertDatabaseHas('color', ['name' => 'Color corto', 'code' => '#A1B']);
        $this->assertDatabaseHas('color', ['name' => 'Color largo', 'code' => '#12ABEF']);
    }

    public function test_invalid_color_data_returns_422_with_spanish_messages(): void
    {
        $response = $this->postJson(route('vehiclescolor.store'), [
            'name' => '',
            'code' => '#12XZ',
            'description' => '<strong>no permitido</strong>',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'code', 'description']);

        $this->assertStringContainsString(
            'formato #RGB o #RRGGBB',
            implode(' ', $response->json('errors.code'))
        );
        $this->assertStringContainsString(
            'descripción',
            implode(' ', $response->json('errors.description'))
        );
        $this->assertDatabaseCount('color', 0);
    }

    public function test_duplicate_names_and_normalized_codes_are_rejected(): void
    {
        $this->createColor();

        $this->postJson(route('vehiclescolor.store'), [
            'name' => 'Azul de prueba',
            'code' => '#ABCDEF',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->postJson(route('vehiclescolor.store'), [
            'name' => 'Otro azul',
            'code' => '104cad',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseCount('color', 1);
    }

    public function test_authenticated_user_can_view_color_details(): void
    {
        $color = $this->createColor();

        $this->getJson(route('vehiclescolor.show', $color))
            ->assertOk()
            ->assertJsonPath('id', $color->id)
            ->assertJsonPath('name', 'Azul de prueba')
            ->assertJsonPath('code', '#104CAD')
            ->assertJsonPath('description', 'Descripción del color')
            ->assertJsonStructure(['created_at', 'updated_at']);
    }

    public function test_authenticated_user_can_edit_and_update_a_color(): void
    {
        $color = $this->createColor();

        $this->getJson(route('vehiclescolor.edit', $color))
            ->assertOk()
            ->assertJsonPath('name', 'Azul de prueba')
            ->assertJsonPath('code', '#104CAD');

        $this->putJson(route('vehiclescolor.update', $color), [
            'name' => 'Verde actualizado',
            'code' => '0f8',
            'description' => 'Descripción actualizada',
        ])->assertOk()
            ->assertJson(['mensaje' => 'Color actualizado correctamente.'])
            ->assertJsonPath('data.code', '#0F8');

        $this->assertDatabaseHas('color', [
            'id' => $color->id,
            'name' => 'Verde actualizado',
            'code' => '#0F8',
            'description' => 'Descripción actualizada',
        ]);
    }

    public function test_color_without_related_vehicles_can_be_deleted(): void
    {
        $color = $this->createColor();

        $this->deleteJson(route('vehiclescolor.destroy', $color))
            ->assertOk()
            ->assertJson(['mensaje' => 'Color eliminado correctamente.']);

        $this->assertDatabaseMissing('color', ['id' => $color->id]);
    }

    public function test_color_with_a_related_vehicle_cannot_be_deleted(): void
    {
        $color = $this->createColor();
        $brandId = DB::table('brands')->insertGetId([
            'name' => 'Marca para color',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $modelId = DB::table('brandmodels')->insertGetId([
            'name' => 'Modelo para color',
            'code' => 'MOD-COLOR',
            'brand_id' => $brandId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $typeId = DB::table('vehicletypes')->insertGetId([
            'name' => 'Tipo para color',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehicles')->insert([
            'code' => 'VEH-COLOR',
            'name' => 'Vehículo relacionado',
            'plate' => 'COL-001',
            'year' => 2026,
            'load_capacity' => 1000,
            'occupats_capacity' => 4,
            'fuel_capacity' => 80,
            'compact_capacity' => 0,
            'type_id' => $typeId,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'color_id' => $color->id,
            'description' => 'Vehículo para bloqueo del color',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson(route('vehiclescolor.destroy', $color))
            ->assertStatus(409)
            ->assertJson([
                'mensaje' => 'No se puede eliminar el color porque tiene vehículos relacionados.',
            ]);

        $this->assertDatabaseHas('color', ['id' => $color->id]);
        $this->assertDatabaseHas('vehicles', ['color_id' => $color->id]);
    }

    public function test_missing_colors_return_404_for_resource_operations(): void
    {
        $this->getJson(route('vehiclescolor.show', 999999))->assertNotFound();
        $this->getJson(route('vehiclescolor.edit', 999999))->assertNotFound();
        $this->putJson(route('vehiclescolor.update', 999999), $this->validData())->assertNotFound();
        $this->deleteJson(route('vehiclescolor.destroy', 999999))->assertNotFound();
    }

    public function test_color_actions_have_the_required_order_icons_colors_and_routes(): void
    {
        $color = $this->createColor();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vehiclescolor.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $actions = (string) $response->json('data.0.actions');

        $this->assertStringContainsString('btn btn-outline-primary btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver color"', $actions);
        $this->assertStringContainsString(route('vehiclescolor.show', $color), $actions);
        $this->assertStringContainsString('btn btn-outline-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar color"', $actions);
        $this->assertStringContainsString(route('vehiclescolor.edit', $color), $actions);
        $this->assertStringContainsString('btn btn-outline-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('title="Eliminar"', $actions);
        $this->assertStringContainsString('aria-label="Eliminar color"', $actions);
        $this->assertStringContainsString(route('vehiclescolor.destroy', $color), $actions);
        $this->assertLessThan(strpos($actions, 'btnEditar'), strpos($actions, 'btnVer'));
        $this->assertLessThan(strpos($actions, 'btnEliminar'), strpos($actions, 'btnEditar'));
    }

    public function test_unauthenticated_users_cannot_access_color_routes(): void
    {
        $color = $this->createColor();
        auth()->logout();

        foreach ([
            route('vehiclescolor.index'),
            route('vehiclescolor.show', $color),
            route('vehiclescolor.edit', $color),
        ] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }

        $this->postJson(route('vehiclescolor.store'), $this->validData())->assertUnauthorized();
        $this->putJson(route('vehiclescolor.update', $color), $this->validData())->assertUnauthorized();
        $this->deleteJson(route('vehiclescolor.destroy', $color))->assertUnauthorized();
    }

    private function createColor(array $overrides = []): Color
    {
        return Color::create(array_merge([
            'name' => 'Azul de prueba',
            'code' => '#104CAD',
            'description' => 'Descripción del color',
        ], $overrides));
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Azul de prueba',
            'code' => '#104CAD',
            'description' => 'Descripción del color',
        ], $overrides);
    }
}
