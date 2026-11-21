<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\CitizenZoneAssignment;
use App\Models\User;
use App\Models\admin\Employee;
use App\Models\admin\Programming;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CitizenHistoryTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 10;

    public function test_citizen_without_history_receives_empty_paginated_result(): void
    {
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_user_and_employee_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('user')->plainTextToken)
            ->getJson('/api/v1/ciudadano/historial')
            ->assertForbidden();

        $employee = $this->employee();
        $this->withToken($employee->createToken('employee')->plainTextToken)
            ->getJson('/api/v1/ciudadano/historial')
            ->assertForbidden();
    }

    public function test_only_strictly_completed_non_future_services_are_listed(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $this->assignment($citizen, $zone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();

        $completed = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-10', true, true);
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-11', true, false);
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_PROGRAMMED, '2026-11-12', true, true);
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_STARTED, '2026-11-13', true, true);
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_CANCELLED, '2026-11-14', false, false);
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-12-01', true, true);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $completed->id);
    }

    public function test_historical_zones_are_used_and_unassigned_zone_is_excluded(): void
    {
        $oldZone = $this->zone('Anterior');
        $currentZone = $this->zone('Actual');
        $foreignZone = $this->zone('Ajena');
        $citizen = $this->citizen($currentZone);
        $this->assignment($citizen, $oldZone, '2026-10-01', '2026-10-31');
        $this->assignment($citizen, $currentZone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $oldService = $this->programming($oldZone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-10-20', true, true);
        $currentService = $this->programming($currentZone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-10', true, true);
        $this->programming($foreignZone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-11', true, true);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $currentService->id)
            ->assertJsonPath('data.1.id', $oldService->id);
    }

    public function test_no_assignment_excludes_service_and_multiple_assignments_are_excluded(): void
    {
        $zone = $this->zone('Centro');
        $overlapZone = $this->zone('Solapada');
        $citizen = $this->citizen();
        $this->assignment($citizen, $overlapZone, '2026-11-01', '2026-11-30');
        $this->assignment($citizen, $overlapZone, '2026-11-10', '2026-11-20');
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-15', true, true);
        $this->programming($overlapZone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-15', true, true);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_reprogrammed_original_and_intermediate_destinations_are_not_duplicated(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $this->assignment($citizen, $zone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $original = $this->programming($zone, $vehicle, $driver, Programming::STATUS_RESCHEDULED, '2026-11-10', false, false);
        $intermediate = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-11', true, true, $original->id);
        $final = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-12', true, true, $intermediate->id);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $final->id)
            ->assertJsonMissingPath('data.0.parent_programming_id');
    }

    public function test_filters_zone_and_date_intersection_and_reject_future_ranges(): void
    {
        $zone = $this->zone('Centro');
        $otherZone = $this->zone('Otra');
        $citizen = $this->citizen($zone);
        $this->assignment($citizen, $zone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $included = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-15', true, true);
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-05', true, true);
        $this->programming($otherZone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-16', true, true);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial?month=2026-11&from=2026-11-10&to=2026-11-20&zone_id='.$zone->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $included->id);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial?zone_id='.$otherZone->id)
            ->assertNotFound();

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial?to=2099-01-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    public function test_detail_applies_the_same_historical_authorization(): void
    {
        $zone = $this->zone('Centro');
        $otherZone = $this->zone('Otra');
        $citizen = $this->citizen($zone);
        $this->assignment($citizen, $zone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $foreign = $this->programming($otherZone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-10', true, true);
        $future = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-12-01', true, true);

        $this->withToken($this->token($citizen))->getJson('/api/v1/ciudadano/historial/'.$foreign->id)->assertNotFound();
        $this->withToken($this->token($citizen))->getJson('/api/v1/ciudadano/historial/'.$future->id)->assertNotFound();
    }

    public function test_history_eager_loads_public_relations_without_loading_locations(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $this->assignment($citizen, $zone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-10', true, true);
        $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-11', true, true);
        $counts = ['shift' => 0, 'vehicle' => 0, 'locations' => 0];
        DB::listen(function (QueryExecuted $query) use (&$counts): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'shifts')) $counts['shift']++;
            if (str_contains($sql, 'vehicles')) $counts['vehicle']++;
            if (str_contains($sql, 'programming_locations')) $counts['locations']++;
        });

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/historial?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(1, $counts['shift']);
        $this->assertSame(1, $counts['vehicle']);
        $this->assertSame(0, $counts['locations']);
    }

    private function citizen(?Zone $zone = null): Citizen
    {
        return Citizen::create(['dni' => (string) random_int(10000000, 99999999), 'first_name' => 'Ana', 'last_name' => 'Pérez', 'email' => fake()->unique()->safeEmail(), 'password' => Hash::make('password123'), 'zone_id' => $zone?->id]);
    }

    private function token(Citizen $citizen): string
    {
        return $citizen->createToken('flutter-citizen')->plainTextToken;
    }

    private function employee(): Employee
    {
        $type = DB::table('employee_types')->insertGetId(['name' => 'Empleado '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        return Employee::create(['employee_type_id' => $type, 'dni' => (string) random_int(10000000, 99999999), 'first_name' => 'Empleado', 'last_name' => 'Prueba', 'birthdate' => '1990-01-01', 'email' => fake()->unique()->safeEmail(), 'password' => Hash::make('password123'), 'status' => 'Activo']);
    }

    private function assignment(Citizen $citizen, Zone $zone, string $assignedAt, ?string $endedAt): CitizenZoneAssignment
    {
        return CitizenZoneAssignment::create(['citizen_id' => $citizen->id, 'zone_id' => $zone->id, 'latitude' => -12.05, 'longitude' => -75.95, 'accuracy' => 10, 'assigned_at' => $assignedAt.' 08:00:00', 'ended_at' => $endedAt ? $endedAt.' 23:59:59' : null]);
    }

    private function zone(string $name): Zone
    {
        $sequence = self::$sequence++;
        $department = DB::table('departments')->insertGetId(['code' => str_pad((string) $sequence, 2, '0', STR_PAD_LEFT), 'name' => 'Departamento '.$name]);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT), 'name' => 'Provincia '.$name]);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'name' => 'Distrito '.$name]);
        return Zone::create(['name' => $name, 'district_id' => $district, 'perimeter' => ['type' => 'Polygon', 'coordinates' => [[[-76, -12], [-76, -12.1], [-75.9, -12.1], [-75.9, -12], [-76, -12]]]]]);
    }

    private function vehicleAndDriver(): array
    {
        $type = DB::table('vehicletypes')->insertGetId(['name' => 'Bus '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $brand = DB::table('brands')->insertGetId(['name' => 'Marca '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $model = DB::table('brandmodels')->insertGetId(['name' => 'Modelo '.random_int(1, 999999), 'brand_id' => $brand, 'created_at' => now(), 'updated_at' => now()]);
        $color = DB::table('color')->insertGetId(['name' => 'Color '.random_int(1, 999999), 'code' => '#000000', 'created_at' => now(), 'updated_at' => now()]);
        $vehicle = Vehicle::create(['code' => 'V'.random_int(1000, 9999), 'name' => 'Vehículo de prueba', 'plate' => 'ABC-'.random_int(100, 999), 'year' => 2024, 'load_capacity' => 100, 'occupats_capacity' => 30, 'fuel_capacity' => 100, 'compact_capacity' => 10, 'type_id' => $type, 'brand_id' => $brand, 'model_id' => $model, 'color_id' => $color]);
        $employeeType = DB::table('employee_types')->insertGetId(['name' => 'Conductor '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $driver = Employee::create(['employee_type_id' => $employeeType, 'dni' => (string) random_int(10000000, 99999999), 'first_name' => 'Conductor', 'last_name' => 'Prueba', 'birthdate' => '1990-01-01', 'email' => fake()->unique()->safeEmail(), 'password' => Hash::make('password123'), 'status' => 'Activo']);
        return [$vehicle, $driver];
    }

    private function programming(Zone $zone, Vehicle $vehicle, Employee $driver, string $status, string $date, bool $started, bool $finished, ?int $parentId = null): Programming
    {
        return Programming::create(['parent_programming_id' => $parentId, 'zone_id' => $zone->id, 'shift_id' => $this->shift(), 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'date' => $date, 'status' => $status, 'started_at' => $started ? $date.' 08:00:00' : null, 'finished_at' => $finished ? $date.' 11:00:00' : null, 'snapshot' => ['zone' => $zone->name, 'shift' => 'Mañana', 'hours' => '08:00 - 12:00', 'vehicle' => $vehicle->name, 'plate' => $vehicle->plate]]);
    }

    private function shift(): int
    {
        return DB::table('shifts')->insertGetId(['name' => 'Mañana '.random_int(1, 999999), 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'created_at' => now(), 'updated_at' => now()]);
    }
}
