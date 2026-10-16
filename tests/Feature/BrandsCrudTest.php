<?php

namespace Tests\Feature;

use App\Models\admin\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BrandsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('BrandsCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create());
    }

    public function test_authenticated_user_can_list_brands(): void
    {
        $brand = $this->createBrand();

        $this->get(route('brands.index'))
            ->assertOk()
            ->assertSee('Listado de Marcas');

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('brands.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $brand->id)
            ->assertJsonPath('data.0.name', 'Marca de prueba')
            ->assertJsonPath('data.0.description', 'Descripción original');
    }

    public function test_brand_can_be_stored_without_a_logo_and_description_is_persisted(): void
    {
        $this->postJson(route('brands.store'), [
            'name' => 'Marca sin logo',
            'description' => 'Descripción almacenada correctamente',
        ])->assertCreated()
            ->assertJson(['mensaje' => 'Marca registrada correctamente.']);

        $this->assertDatabaseHas('brands', [
            'name' => 'Marca sin logo',
            'description' => 'Descripción almacenada correctamente',
            'logo' => null,
        ]);
    }

    public function test_brand_can_be_stored_with_a_logo_on_the_public_disk(): void
    {
        $this->postJson(route('brands.store'), [
            'name' => 'Marca con logo',
            'description' => null,
            'logo' => UploadedFile::fake()->image('marca.png'),
        ])->assertCreated();

        $brand = Brand::where('name', 'Marca con logo')->firstOrFail();
        $this->assertNotNull($brand->logo);
        $this->assertStringStartsWith('/storage/brand_logo/', $brand->logo);
        Storage::disk('public')->assertExists($this->storagePath($brand->logo));
    }

    public function test_invalid_brand_data_returns_422(): void
    {
        $existing = $this->createBrand();

        $this->postJson(route('brands.store'), [
            'name' => $existing->name,
            'description' => ['no es texto'],
            'logo' => UploadedFile::fake()->create('logo.txt', 2, 'text/plain'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'logo']);

        $this->postJson(route('brands.store'), [
            'name' => str_repeat('a', 101),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->postJson(route('brands.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('brands', 1);
    }

    public function test_authenticated_user_can_view_brand_details_in_read_only_mode(): void
    {
        $brand = $this->createBrand([
            'logo' => '/storage/brand_logo/existing.png',
        ]);

        $this->get(route('brands.show', $brand))
            ->assertOk()
            ->assertSee('Detalle de marca')
            ->assertSee('Información de solo lectura')
            ->assertSee('Marca de prueba')
            ->assertSee('Descripción original')
            ->assertSee(asset('/storage/brand_logo/existing.png'), false)
            ->assertDontSee('<input', false)
            ->assertDontSee('<textarea', false)
            ->assertDontSee('<form', false);
    }

    public function test_create_and_edit_forms_keep_brand_fields_logo_preview_and_accessible_controls(): void
    {
        $this->get(route('brands.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="logo"', false)
            ->assertSee('for="brandLogoInput"', false)
            ->assertSee('btn btn-secondary', false)
            ->assertSee('Registrar');

        $brand = $this->createBrand([
            'logo' => '/storage/brand_logo/existing.png',
        ]);

        $this->get(route('brands.edit', $brand))
            ->assertOk()
            ->assertSee(route('brands.update', $brand), false)
            ->assertSee('Marca de prueba')
            ->assertSee('Descripción original')
            ->assertSee(asset('/storage/brand_logo/existing.png'), false)
            ->assertSee('Actualizar');
    }

    public function test_brand_can_be_updated_without_replacing_its_logo(): void
    {
        Storage::disk('public')->put('brand_logo/original.png', 'original');
        $brand = $this->createBrand([
            'logo' => '/storage/brand_logo/original.png',
        ]);

        $this->putJson(route('brands.update', $brand), [
            'name' => 'Marca actualizada',
            'description' => 'Nueva descripción',
        ])->assertOk()
            ->assertJson(['mensaje' => 'Marca actualizada correctamente.']);

        $brand->refresh();
        $this->assertSame('Marca actualizada', $brand->name);
        $this->assertSame('Nueva descripción', $brand->description);
        $this->assertSame('/storage/brand_logo/original.png', $brand->logo);
        Storage::disk('public')->assertExists('brand_logo/original.png');
    }

    public function test_brand_can_be_updated_with_a_new_logo_without_deleting_the_previous_file(): void
    {
        Storage::disk('public')->put('brand_logo/original.png', 'original');
        $brand = $this->createBrand([
            'logo' => '/storage/brand_logo/original.png',
        ]);

        $this->putJson(route('brands.update', $brand), [
            'name' => $brand->name,
            'description' => $brand->description,
            'logo' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertOk();

        $brand->refresh();
        $this->assertNotSame('/storage/brand_logo/original.png', $brand->logo);
        Storage::disk('public')->assertExists($this->storagePath($brand->logo));
        Storage::disk('public')->assertExists('brand_logo/original.png');
    }

    public function test_update_allows_the_current_name_but_rejects_another_brand_name(): void
    {
        $brand = $this->createBrand();
        $other = $this->createBrand(['name' => 'Otra marca']);

        $this->putJson(route('brands.update', $brand), [
            'name' => $brand->name,
            'description' => 'Actualización con el mismo nombre',
        ])->assertOk();

        $this->putJson(route('brands.update', $brand), [
            'name' => $other->name,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_brand_without_relations_can_be_deleted_without_removing_its_logo_file(): void
    {
        Storage::disk('public')->put('brand_logo/conservar.png', 'logo');
        $brand = $this->createBrand([
            'logo' => '/storage/brand_logo/conservar.png',
        ]);

        $this->deleteJson(route('brands.destroy', $brand))
            ->assertOk()
            ->assertJson(['mensaje' => 'Marca eliminada correctamente.']);

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        Storage::disk('public')->assertExists('brand_logo/conservar.png');
    }

    public function test_brand_with_a_related_model_cannot_be_deleted(): void
    {
        $brand = $this->createBrand();
        DB::table('brandmodels')->insert([
            'name' => 'Modelo relacionado',
            'code' => 'MODEL-01',
            'brand_id' => $brand->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson(route('brands.destroy', $brand))
            ->assertStatus(409)
            ->assertJson(['mensaje' => 'No se puede eliminar esta marca porque tiene relación con otras tablas.']);

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_brand_with_a_related_vehicle_cannot_be_deleted(): void
    {
        $brand = $this->createBrand();
        $supportBrand = $this->createBrand(['name' => 'Marca de soporte']);
        $modelId = DB::table('brandmodels')->insertGetId([
            'name' => 'Modelo de soporte',
            'code' => 'MODEL-02',
            'brand_id' => $supportBrand->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
            'code' => 'VEH-001',
            'name' => 'Vehículo relacionado',
            'plate' => 'ABC-123',
            'year' => 2026,
            'load_capacity' => 1000,
            'occupats_capacity' => 4,
            'fuel_capacity' => 80,
            'compact_capacity' => 0,
            'type_id' => $typeId,
            'brand_id' => $brand->id,
            'model_id' => $modelId,
            'color_id' => $colorId,
            'description' => 'Vehículo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson(route('brands.destroy', $brand))
            ->assertStatus(409);

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_missing_brands_return_404_for_resource_operations(): void
    {
        $this->getJson(route('brands.show', 999999))->assertNotFound();
        $this->getJson(route('brands.edit', 999999))->assertNotFound();
        $this->putJson(route('brands.update', 999999), [
            'name' => 'Marca inexistente',
        ])->assertNotFound();
        $this->deleteJson(route('brands.destroy', 999999))->assertNotFound();
    }

    public function test_brand_actions_have_the_required_order_icons_colors_and_routes(): void
    {
        $brand = $this->createBrand();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('brands.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $actions = (string) $response->json('data.0.actions');

        $this->assertStringContainsString('btn btn-info btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver marca"', $actions);
        $this->assertStringContainsString(route('brands.show', $brand), $actions);
        $this->assertStringContainsString('btn btn-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar marca"', $actions);
        $this->assertStringContainsString(route('brands.edit', $brand), $actions);
        $this->assertStringContainsString('btn btn-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('title="Eliminar"', $actions);
        $this->assertStringContainsString('aria-label="Eliminar marca"', $actions);
        $this->assertStringContainsString(route('brands.destroy', $brand), $actions);
        $this->assertLessThan(strpos($actions, 'btnEditar'), strpos($actions, 'btnVer'));
        $this->assertLessThan(strpos($actions, 'btnEliminar'), strpos($actions, 'btnEditar'));
    }

    public function test_unauthenticated_users_cannot_access_brand_routes(): void
    {
        $brand = $this->createBrand();
        auth()->logout();

        foreach ([
            route('brands.index'),
            route('brands.create'),
            route('brands.show', $brand),
            route('brands.edit', $brand),
        ] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }

        $this->postJson(route('brands.store'), ['name' => 'No autorizada'])->assertUnauthorized();
        $this->putJson(route('brands.update', $brand), ['name' => 'No autorizada'])->assertUnauthorized();
        $this->deleteJson(route('brands.destroy', $brand))->assertUnauthorized();
    }

    private function createBrand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'Marca de prueba',
            'description' => 'Descripción original',
            'logo' => null,
        ], $overrides));
    }

    private function storagePath(string $publicUrl): string
    {
        return preg_replace('#^/storage/#', '', $publicUrl);
    }
}
