<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehiclesCrudTest extends TestCase
{
    use RefreshDatabase;

    private int $brandId;

    private int $modelId;

    private int $typeId;

    private int $colorId;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_vehicle_can_be_created_updated_and_deleted_with_all_images(): void
    {
        Storage::fake('public');

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