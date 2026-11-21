<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Models\admin\Programming;
use App\Models\admin\ProgrammingLocation;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CitizenTrackingTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 10;

    public function test_citizen_receives_only_the_latest_valid_location_and_public_vehicle_data(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $programming = $this->programming($zone, $vehicle, $driver, Programming::STATUS_STARTED, now()->subMinute(), null);
        $this->location($programming, $driver, -12.0500000, -75.9500000, 8.5, now()->subMinutes(2));
        $latest = $this->location($programming, $driver, -12.0510000, -75.9510000, 8.5, now()->subSeconds(18));

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id.'/ubicacion')
            ->assertOk()
            ->assertJsonPath('data.programming_id', $programming->id)
            ->assertJsonPath('data.vehicle.id', $vehicle->id)
            ->assertJsonPath('data.vehicle.name', $vehicle->name)
            ->assertJsonPath('data.vehicle.plate', $vehicle->plate)
            ->assertJsonPath('data.location.latitude', (float) $latest->latitude)
            ->assertJsonPath('data.location.longitude', (float) $latest->longitude)
            ->assertJsonPath('data.location.accuracy', 8.5)
            ->assertJsonPath('data.is_stale', false)
            ->assertJsonPath('data.route_started', true)
            ->assertJsonMissingPath('data.employee_id')
            ->assertJsonMissingPath('data.driver')
            ->assertJsonMissingPath('data.locations');
    }

    public function test_citizen_without_zone_receives_conflict(): void
    {
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/1/ubicacion')
            ->assertStatus(409);
    }

    public function test_user_and_employee_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('user')->plainTextToken)
            ->getJson('/api/v1/ciudadano/programaciones/1/ubicacion')
            ->assertForbidden();

        [, $driver] = $this->vehicleAndDriver();
        $this->withToken($driver->createToken('driver')->plainTextToken)
            ->getJson('/api/v1/ciudadano/programaciones/1/ubicacion')
            ->assertForbidden();
    }

    public function test_programming_from_another_zone_or_unknown_id_returns_not_found(): void
    {
        $zone = $this->zone('Centro');
        $otherZone = $this->zone('Norte');
        $citizen = $this->citizen($zone);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $foreign = $this->programming($otherZone, $vehicle, $driver, Programming::STATUS_STARTED, now()->subMinute(), null);
        $this->location($foreign, $driver, -12.05, -75.95, 5, now()->subSeconds(10));

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$foreign->id.'/ubicacion')
            ->assertNotFound();

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/999999/ubicacion')
            ->assertNotFound();
    }

    public function test_not_started_finished_cancelled_and_incompatible_programmings_are_rejected(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $pending = $this->programming($zone, $vehicle, $driver, Programming::STATUS_PROGRAMMED, null, null);
        $finished = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, now()->subHour(), now()->subMinute());
        $cancelled = $this->programming($zone, $vehicle, $driver, Programming::STATUS_CANCELLED, null, null);
        $incompatible = $this->programming($zone, $vehicle, $driver, Programming::STATUS_RESCHEDULED, now()->subMinute(), null);

        foreach ([$pending, $finished, $incompatible] as $programming) {
            $this->withToken($this->token($citizen))
                ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id.'/ubicacion')
                ->assertStatus(409);
        }

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$cancelled->id.'/ubicacion')
            ->assertNotFound();
    }

    public function test_active_programming_without_locations_returns_null_location(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $programming = $this->programming($zone, $vehicle, $driver, Programming::STATUS_STARTED, now()->subMinute(), null);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id.'/ubicacion')
            ->assertOk()
            ->assertJsonPath('data.location', null)
            ->assertJsonPath('data.last_seen_at', null)
            ->assertJsonPath('data.age_seconds', null)
            ->assertJsonPath('data.is_stale', null)
            ->assertJsonPath('data.is_low_accuracy', null);
    }

    public function test_invalid_coordinates_and_foreign_driver_points_are_not_exposed(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        [, $otherDriver] = $this->vehicleAndDriver();
        $programming = $this->programming($zone, $vehicle, $driver, Programming::STATUS_STARTED, now()->subMinute(), null);
        $this->location($programming, $otherDriver, -12.05, -75.95, 5, now()->subSeconds(5));
        DB::table('programming_locations')->insert([
            'programming_id' => $programming->id,
            'employee_id' => $driver->id,
            'latitude' => 100,
            'longitude' => -75.95,
            'accuracy' => 5,
            'recorded_at' => now()->subSeconds(5)->utc(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id.'/ubicacion')
            ->assertOk()
            ->assertJsonPath('data.location', null)
            ->assertJsonMissingPath('data.employee_id');
    }

    public function test_accuracy_null_low_accuracy_and_staleness_policy(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-20 15:00:00', 'UTC'));

        try {
            $zone = $this->zone('Centro');
            $citizen = $this->citizen($zone);
            [$vehicle, $driver] = $this->vehicleAndDriver();
            $programming = $this->programming($zone, $vehicle, $driver, Programming::STATUS_STARTED, now()->subMinute(), null);
            $location = $this->location($programming, $driver, -12.05, -75.95, null, now('UTC')->subSeconds(120));

            $this->withToken($this->token($citizen))
                ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id.'/ubicacion')
                ->assertOk()
                ->assertJsonPath('data.location.accuracy', null)
                ->assertJsonPath('data.is_low_accuracy', null)
                ->assertJsonPath('data.is_stale', false);

            $location->update(['accuracy' => 51, 'recorded_at' => now('UTC')->subSeconds(121)]);
            $this->withToken($this->token($citizen))
                ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id.'/ubicacion')
                ->assertOk()
                ->assertJsonPath('data.is_low_accuracy', true)
                ->assertJsonPath('data.is_stale', true);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_tracking_route_has_independent_throttle(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/ciudadano/programaciones/{id}/ubicacion'
        );

        $this->assertNotNull($route);
        $this->assertContains('throttle:citizen-tracking', $route->middleware());
    }

    private function citizen(?Zone $zone = null): Citizen
    {
        return Citizen::create([
            'dni' => (string) random_int(10000000, 99999999),
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'zone_id' => $zone?->id,
        ]);
    }

    private function token(Citizen $citizen): string
    {
        return $citizen->createToken('flutter-citizen')->plainTextToken;
    }

    private function zone(string $name): Zone
    {
        $sequence = self::$sequence++;
        $department = DB::table('departments')->insertGetId(['code' => str_pad((string) $sequence, 2, '0', STR_PAD_LEFT), 'name' => 'Departamento '.$name]);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT), 'name' => 'Provincia '.$name]);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'name' => 'Distrito '.$name]);

        return Zone::create([
            'name' => $name,
            'district_id' => $district,
            'perimeter' => ['type' => 'Polygon', 'coordinates' => [[[-76, -12], [-76, -12.1], [-75.9, -12.1], [-75.9, -12], [-76, -12]]]],
        ]);
    }

    private function vehicleAndDriver(): array
    {
        $type = DB::table('vehicletypes')->insertGetId(['name' => 'Bus '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $brand = DB::table('brands')->insertGetId(['name' => 'Marca '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $model = DB::table('brandmodels')->insertGetId(['name' => 'Modelo '.random_int(1, 999999), 'brand_id' => $brand, 'created_at' => now(), 'updated_at' => now()]);
        $color = DB::table('color')->insertGetId(['name' => 'Color '.random_int(1, 999999), 'code' => '#000000', 'created_at' => now(), 'updated_at' => now()]);
        $vehicle = Vehicle::create([
            'code' => 'V'.random_int(1000, 9999), 'name' => 'Vehículo de prueba', 'plate' => 'ABC-'.random_int(100, 999),
            'year' => 2024, 'load_capacity' => 100, 'occupats_capacity' => 30,
            'fuel_capacity' => 100, 'compact_capacity' => 10, 'type_id' => $type,
            'brand_id' => $brand, 'model_id' => $model, 'color_id' => $color,
        ]);
        $employeeType = DB::table('employee_types')->insertGetId(['name' => 'Conductor '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $driver = \App\Models\admin\Employee::create([
            'employee_type_id' => $employeeType, 'dni' => (string) random_int(10000000, 99999999),
            'first_name' => 'Conductor', 'last_name' => 'Prueba', 'birthdate' => '1990-01-01',
            'email' => fake()->unique()->safeEmail(), 'password' => Hash::make('password123'), 'status' => 'Activo',
        ]);

        return [$vehicle, $driver];
    }

    private function programming(Zone $zone, Vehicle $vehicle, object $driver, string $status, $startedAt, $finishedAt): Programming
    {
        return Programming::create([
            'zone_id' => $zone->id, 'shift_id' => $this->shift(), 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            'date' => '2026-12-10', 'status' => $status, 'started_at' => $startedAt, 'finished_at' => $finishedAt,
            'snapshot' => ['zone' => $zone->name, 'shift' => 'Mañana', 'hours' => '08:00 - 12:00', 'vehicle' => $vehicle->name, 'plate' => $vehicle->plate],
        ]);
    }

    private function shift(): int
    {
        return DB::table('shifts')->insertGetId(['name' => 'Mañana '.random_int(1, 999999), 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function location(Programming $programming, object $driver, float $latitude, float $longitude, ?float $accuracy, $recordedAt): ProgrammingLocation
    {
        return ProgrammingLocation::create([
            'programming_id' => $programming->id, 'employee_id' => $driver->id,
            'latitude' => $latitude, 'longitude' => $longitude, 'accuracy' => $accuracy,
            'recorded_at' => $recordedAt->utc(),
        ]);
    }
}
