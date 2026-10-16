<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class VehiclesCrudTest extends TestCase
{
    use RefreshDatabase;

    private int $brandId;

    private int $modelId;

    private int $typeId;

    private int $colorId;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('VehiclesCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->brandId = DB::table('brands')->insertGetId([
            'name' => 'Marca prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->modelId = DB::table('brandmodels')->insertGetId([
            'name' => 'Modelo prueba',
            'code' => 'MOD-TEST',
            'brand_id' => $this->brandId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = DB::table('vehicletypes')->insertGetId([
            'name' => 'Tipo prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->colorId = DB::table('color')->insertGetId([
            'name' => 'Color prueba',
            'code' => '#123456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_vehicle_form_selects_first_brand_and_model_and_shows_color_code(): void
    {
        $createResponse = $this->get(route('vehicles.create'))->assertOk();

        $createResponse->assertSee('value="'.$this->brandId.'" selected="selected"', false)
            ->assertSee('value="'.$this->modelId.'" selected="selected"', false)
            ->assertSee('data-color="#123456"', false)
            ->assertDontSee('Seleccione una marca');

        $this->post(route('vehicles.store'), $this->vehicleData())->assertCreated();
        $vehicleId = DB::table('vehicles')->where('code', 'TEST-001')->value('id');

        $editResponse = $this->get(route('vehicles.edit', $vehicleId))->assertOk();
        $editResponse->assertSee('value="'.$this->brandId.'" selected="selected"', false)
            ->assertSee('value="'.$this->modelId.'" selected="selected"', false)
            ->assertSee('data-color="#123456"', false)
            ->assertSee('selected>', false);
    }

    public function test_vehicle_can_be_created_updated_and_deleted_with_all_images(): void
    {
        $this->post(route('vehicles.store'), $this->vehicleData())
            ->assertCreated()
            ->assertJson(['mensaje' => 'Vehículo registrado correctamente']);

        $vehicle = DB::table('vehicles')->where('code', 'TEST-001')->first();
        $this->assertNotNull($vehicle);

        $tableResponse = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vehicles.index', ['draw' => 1, 'start' => 0, 'length' => 10]));
        $tableResponse->assertOk();

        $tableVehicle = collect($tableResponse->json('data'))->firstWhere('code', 'TEST-001');
        $this->assertNotNull($tableVehicle);
        $this->assertSame('Marca prueba', $tableVehicle['brand']);
        $this->assertStringContainsString('vehicle-logo-preview', $tableVehicle['logo']);
        $this->assertStringContainsString('btnImagen', $tableVehicle['actions']);
        $this->assertStringContainsString('btnEditar', $tableVehicle['actions']);
        $this->assertStringContainsString('btnEliminar', $tableVehicle['actions']);

        $this->put(route('vehicles.update', $vehicle->id), array_merge($this->vehicleData(), [
            'name' => 'Vehículo actualizado',
        ]))->assertOk()
            ->assertJson(['mensaje' => 'Vehículo actualizado correctamente']);

        $imagePaths = [
            'vehiclesphotos/test-front.jpg',
            'vehiclesphotos/test-side.jpg',
        ];

        foreach ($imagePaths as $imagePath) {
            Storage::disk('public')->put($imagePath, 'test image');
        }

        foreach ($imagePaths as $index => $imagePath) {
            DB::table('vehicle_images')->insert([
                'imagen' => $imagePath,
                'profile' => $index === 0 ? 1 : 0,
                'vehicle_id' => $vehicle->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->delete(route('vehicles.destroy', $vehicle->id))
            ->assertOk()
            ->assertJson(['mensaje' => 'Vehículo y todas sus imágenes eliminados correctamente']);

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('vehicle_images', ['vehicle_id' => $vehicle->id]);

        foreach ($imagePaths as $imagePath) {
            $this->assertFalse(Storage::disk('public')->exists($imagePath));
        }
    }

    public function test_vehicle_cannot_use_a_model_from_another_brand(): void
    {
        $otherBrandId = DB::table('brands')->insertGetId([
            'name' => 'Otra marca',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherModelId = DB::table('brandmodels')->insertGetId([
            'name' => 'Modelo de otra marca',
            'code' => 'MOD-OTHER',
            'brand_id' => $otherBrandId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson(route('vehicles.store'), array_merge($this->vehicleData(), [
            'model_id' => $otherModelId,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('model_id');

        $this->assertDatabaseMissing('vehicles', ['code' => 'TEST-001']);
    }

    public function test_image_upload_validation_returns_422_without_exposing_internal_errors(): void
    {
        $vehicleId = $this->createVehicle();

        $response = $this->postJson(route('vehicles.photo.subirimagenes', $vehicleId), [
            'photos' => [UploadedFile::fake()->create('documento.txt', 2, 'text/plain')],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('photos.0')
            ->assertJsonMissingPath('error');

        $this->assertDatabaseCount('vehicle_images', 0);
    }

    public function test_multiple_image_upload_assigns_exactly_one_principal_photo(): void
    {
        $vehicleId = $this->createVehicle();

        $this->post(route('vehicles.photo.subirimagenes', $vehicleId), [
            'photos' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('side.png'),
            ],
        ])->assertOk()
            ->assertJson(['codigo' => 1]);

        $photos = DB::table('vehicle_images')->where('vehicle_id', $vehicleId)->orderBy('id')->get();
        $this->assertCount(2, $photos);
        $this->assertSame(1, $photos->where('profile', 1)->count());
        $this->assertSame(1, (int) $photos->first()->profile);

        foreach ($photos as $photo) {
            Storage::disk('public')->assertExists($photo->imagen);
        }
    }

    public function test_individual_image_deletion_uses_imagen_path_and_preserves_principal_photo(): void
    {
        $vehicleId = $this->createVehicle();
        [$principalId, $secondaryId] = $this->createVehiclePhotos($vehicleId);

        $this->deleteJson(route('vehicles.photo.eliminar_imagen', $secondaryId))
            ->assertOk()
            ->assertJson(['mensaje' => 'Imagen eliminada correctamente']);

        $this->assertDatabaseHas('vehicle_images', [
            'id' => $principalId,
            'profile' => 1,
        ]);
        $this->assertDatabaseMissing('vehicle_images', ['id' => $secondaryId]);
        Storage::disk('public')->assertExists('vehiclesphotos/principal.jpg');
        Storage::disk('public')->assertMissing('vehiclesphotos/secondary.jpg');
    }

    public function test_deleting_principal_photo_promotes_the_next_photo(): void
    {
        $vehicleId = $this->createVehicle();
        [$principalId, $secondaryId] = $this->createVehiclePhotos($vehicleId);

        $this->deleteJson(route('vehicles.photo.eliminar_imagen', $principalId))
            ->assertOk();

        $this->assertDatabaseMissing('vehicle_images', ['id' => $principalId]);
        $this->assertDatabaseHas('vehicle_images', [
            'id' => $secondaryId,
            'vehicle_id' => $vehicleId,
            'profile' => 1,
        ]);
        $this->assertSame(1, DB::table('vehicle_images')->where('vehicle_id', $vehicleId)->where('profile', 1)->count());
        Storage::disk('public')->assertMissing('vehiclesphotos/principal.jpg');
        Storage::disk('public')->assertExists('vehiclesphotos/secondary.jpg');
    }

    public function test_selecting_principal_photo_leaves_exactly_one_profile(): void
    {
        $vehicleId = $this->createVehicle();
        [$firstId, $secondId] = $this->createVehiclePhotos($vehicleId);

        DB::table('vehicle_images')->where('id', $secondId)->update(['profile' => 1]);

        $this->patchJson(route('vehicles.photo.seleccionar', $secondId))
            ->assertOk()
            ->assertJson(['codigo' => 1]);

        $this->assertDatabaseHas('vehicle_images', ['id' => $firstId, 'profile' => 0]);
        $this->assertDatabaseHas('vehicle_images', ['id' => $secondId, 'profile' => 1]);
        $this->assertSame(1, DB::table('vehicle_images')->where('vehicle_id', $vehicleId)->where('profile', 1)->count());
    }

    private function createVehicle(): int
    {
        return DB::table('vehicles')->insertGetId(array_merge($this->vehicleData(), [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function createVehiclePhotos(int $vehicleId): array
    {
        Storage::disk('public')->put('vehiclesphotos/principal.jpg', 'principal');
        Storage::disk('public')->put('vehiclesphotos/secondary.jpg', 'secondary');

        $principalId = DB::table('vehicle_images')->insertGetId([
            'imagen' => 'vehiclesphotos/principal.jpg',
            'profile' => 1,
            'vehicle_id' => $vehicleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondaryId = DB::table('vehicle_images')->insertGetId([
            'imagen' => 'vehiclesphotos/secondary.jpg',
            'profile' => 0,
            'vehicle_id' => $vehicleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$principalId, $secondaryId];
    }

    private function vehicleData(): array
    {
        return [
            'code' => 'TEST-001',
            'name' => 'Vehículo de prueba',
            'plate' => 'TST-001',
            'year' => now()->year,
            'load_capacity' => 1000,
            'occupats_capacity' => 4,
            'fuel_capacity' => 80,
            'compact_capacity' => 0,
            'type_id' => $this->typeId,
            'brand_id' => $this->brandId,
            'model_id' => $this->modelId,
            'color_id' => $this->colorId,
            'description' => 'Vehículo de prueba',
        ];
    }
}
