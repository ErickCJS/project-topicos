<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\CitizenZoneAssignment;
use App\Models\User;
use App\Models\admin\Programming;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Events\QueryExecuted;
use Tests\TestCase;

class CitizenProgrammingTest extends TestCase
{
    use RefreshDatabase;

    private static int $fixtureSequence = 10;

    public function test_guest_cannot_consult_citizen_programmings(): void
    {
        $this->getJson('/api/v1/ciudadano/programaciones')->assertUnauthorized();
    }

    public function test_non_citizen_token_cannot_consult_citizen_programmings(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test-user')->plainTextToken)
            ->getJson('/api/v1/ciudadano/programaciones')
            ->assertForbidden();
    }

    public function test_citizen_without_confirmed_zone_receives_conflict(): void
    {
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones')
            ->assertStatus(409);
    }

    public function test_list_is_filtered_by_current_zone_and_month_with_pagination(): void
    {
        $zone = $this->zone('Centro');
        $otherZone = $this->zone('Norte');
        $citizen = $this->citizen($zone);
        $vehicle = $this->vehicle();
        $shift = $this->shift();
        $driver = $this->driver();

        $visible = $this->programming($zone, $vehicle, $shift, $driver, '2026-12-10', Programming::STATUS_PROGRAMMED);
        $this->programming($zone, $vehicle, $shift, $driver, '2027-01-10', Programming::STATUS_PROGRAMMED);
        $this->programming($otherZone, $vehicle, $shift, $driver, '2026-12-11', Programming::STATUS_PROGRAMMED);
        $this->programming($zone, $vehicle, $shift, $driver, '2026-12-12', Programming::STATUS_CANCELLED);

        $response = $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones?month=2026-12&per_page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.zone.id', $zone->id)
            ->assertJsonPath('data.0.shift.id', $shift)
            ->assertJsonPath('data.0.vehicle.id', $vehicle->id)
            ->assertJsonPath('data.0.status', Programming::STATUS_PROGRAMMED)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_date_range_and_invalid_filters_are_validated(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones?month=12-2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('month');

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones?from=2026-12-20&to=2026-12-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    public function test_month_and_date_range_are_applied_as_an_intersection(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $vehicle = $this->vehicle();
        $shift = $this->shift();
        $driver = $this->driver();
        $inside = $this->programming($zone, $vehicle, $shift, $driver, '2026-12-15', Programming::STATUS_PROGRAMMED);
        $this->programming($zone, $vehicle, $shift, $driver, '2026-12-05', Programming::STATUS_PROGRAMMED);
        $this->programming($zone, $vehicle, $shift, $driver, '2027-01-15', Programming::STATUS_PROGRAMMED);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones?month=2026-12&from=2026-12-10&to=2026-12-20')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inside->id);
    }

    public function test_inconsistent_citizen_zone_and_active_assignment_returns_conflict(): void
    {
        $citizenZone = $this->zone('Ciudadano');
        $assignmentZone = $this->zone('Asignacion');
        $citizen = $this->citizen($citizenZone);
        CitizenZoneAssignment::create([
            'citizen_id' => $citizen->id,
            'zone_id' => $assignmentZone->id,
            'latitude' => -12.05,
            'longitude' => -75.95,
            'accuracy' => 10,
            'assigned_at' => now(),
        ]);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones')
            ->assertStatus(409);
    }

    public function test_active_assignment_zone_is_used_when_citizen_zone_is_null(): void
    {
        $assignmentZone = $this->zone('Asignacion');
        $citizen = $this->citizen();
        CitizenZoneAssignment::create([
            'citizen_id' => $citizen->id,
            'zone_id' => $assignmentZone->id,
            'latitude' => -12.05,
            'longitude' => -75.95,
            'accuracy' => 10,
            'assigned_at' => now(),
        ]);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones')
            ->assertOk();
    }

    public function test_programming_list_eager_loads_shift_and_vehicle_once(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $vehicle = $this->vehicle();
        $shift = $this->shift();
        $driver = $this->driver();
        $this->programming($zone, $vehicle, $shift, $driver, '2026-12-10', Programming::STATUS_PROGRAMMED);
        $this->programming($zone, $vehicle, $shift, $driver, '2026-12-11', Programming::STATUS_PROGRAMMED);
        $relationQueries = ['shift' => 0, 'vehicle' => 0];

        DB::listen(function (QueryExecuted $query) use (&$relationQueries): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'shifts')) {
                $relationQueries['shift']++;
            }
            if (str_contains($sql, 'vehicles')) {
                $relationQueries['vehicle']++;
            }
        });

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(1, $relationQueries['shift']);
        $this->assertSame(1, $relationQueries['vehicle']);
    }

    public function test_detail_hides_programming_from_another_zone(): void
    {
        $zone = $this->zone('Centro');
        $otherZone = $this->zone('Norte');
        $citizen = $this->citizen($zone);
        $vehicle = $this->vehicle();
        $shift = $this->shift();
        $driver = $this->driver();
        $foreign = $this->programming($otherZone, $vehicle, $shift, $driver, '2026-12-10', Programming::STATUS_PROGRAMMED);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$foreign->id)
            ->assertNotFound();

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/999999')
            ->assertNotFound();
    }

    public function test_detail_returns_only_public_programming_fields(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $vehicle = $this->vehicle();
        $shift = $this->shift();
        $driver = $this->driver();
        $programming = $this->programming($zone, $vehicle, $shift, $driver, '2026-12-10', Programming::STATUS_RESCHEDULED);
        $programming->update(['snapshot' => [
            'group' => 'Grupo histórico',
            'zone' => $zone->name,
            'shift' => 'Turno histórico',
            'hours' => '19:30 - 23:45',
            'vehicle' => 'Vehículo histórico',
            'plate' => 'HIS-999',
            'driver' => 'Conductor histórico',
            'helpers' => [],
        ]]);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id)
            ->assertOk()
            ->assertJsonPath('data.id', $programming->id)
            ->assertJsonPath('data.date', '2026-12-10')
            ->assertJsonPath('data.zone.name', 'Centro')
            ->assertJsonPath('data.shift.name', 'Turno histórico')
            ->assertJsonPath('data.shift.schedule.start', '19:30')
            ->assertJsonPath('data.shift.schedule.end', '23:45')
            ->assertJsonPath('data.vehicle.name', 'Vehículo histórico')
            ->assertJsonPath('data.vehicle.plate', 'HIS-999')
            ->assertJsonPath('data.vehicle.code', $vehicle->code)
            ->assertJsonPath('data.status', Programming::STATUS_RESCHEDULED)
            ->assertJsonMissingPath('data.driver_id')
            ->assertJsonMissingPath('data.employees');
    }

    public function test_invalid_snapshot_fields_fall_back_to_current_relations(): void
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $vehicle = $this->vehicle();
        $shift = $this->shift();
        $driver = $this->driver();
        $programming = $this->programming($zone, $vehicle, $shift, $driver, '2026-12-10', Programming::STATUS_PROGRAMMED);
        $programming->update(['snapshot' => [
            'shift' => '',
            'hours' => 'horario inválido',
            'vehicle' => '',
            'plate' => null,
        ]]);
        $currentShift = DB::table('shifts')->find($shift);

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/programaciones/'.$programming->id)
            ->assertOk()
            ->assertJsonPath('data.shift.name', $currentShift->name)
            ->assertJsonPath('data.shift.schedule.start', '08:00')
            ->assertJsonPath('data.shift.schedule.end', '12:00')
            ->assertJsonPath('data.vehicle.name', $vehicle->name)
            ->assertJsonPath('data.vehicle.plate', $vehicle->plate);
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

    private function driver()
    {
        $type = DB::table('employee_types')->insertGetId(['name' => 'Conductor '.random_int(1, 9999), 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('employees')->insertGetId([
            'employee_type_id' => $type,
            'dni' => (string) random_int(10000000, 99999999),
            'first_name' => 'Conductor',
            'last_name' => 'Prueba',
            'birthdate' => '1990-01-01',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'status' => 'Activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function zone(string $name): Zone
    {
        $sequence = self::$fixtureSequence++;
        $department = DB::table('departments')->insertGetId(['code' => str_pad((string) $sequence, 2, '0', STR_PAD_LEFT), 'name' => 'Departamento '.$name]);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT), 'name' => 'Provincia '.$name]);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'name' => 'Distrito '.$name]);

        return Zone::create([
            'name' => $name,
            'district_id' => $district,
            'perimeter' => ['type' => 'Polygon', 'coordinates' => [[[-76, -12], [-76, -12.1], [-75.9, -12.1], [-75.9, -12], [-76, -12]]]],
        ]);
    }

    private function shift()
    {
        return DB::table('shifts')->insertGetId([
            'name' => 'Mañana '.random_int(1, 9999),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vehicle(): Vehicle
    {
        $type = DB::table('vehicletypes')->insertGetId(['name' => 'Bus '.random_int(1, 9999), 'created_at' => now(), 'updated_at' => now()]);
        $brand = DB::table('brands')->insertGetId(['name' => 'Marca '.random_int(1, 9999), 'created_at' => now(), 'updated_at' => now()]);
        $model = DB::table('brandmodels')->insertGetId(['name' => 'Modelo '.random_int(1, 9999), 'brand_id' => $brand, 'created_at' => now(), 'updated_at' => now()]);
        $color = DB::table('color')->insertGetId(['name' => 'Color '.random_int(1, 9999), 'code' => '#000000', 'created_at' => now(), 'updated_at' => now()]);

        return Vehicle::create([
            'code' => 'V'.random_int(1000, 9999), 'name' => 'Vehículo de prueba', 'plate' => 'ABC-123',
            'year' => 2024, 'load_capacity' => 100, 'occupats_capacity' => 30,
            'fuel_capacity' => 100, 'compact_capacity' => 10, 'type_id' => $type,
            'brand_id' => $brand, 'model_id' => $model, 'color_id' => $color,
        ]);
    }

    private function programming(Zone $zone, Vehicle $vehicle, int $shift, int $driver, string $date, string $status): Programming
    {
        return Programming::create([
            'zone_id' => $zone->id, 'shift_id' => $shift, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver,
            'date' => $date, 'status' => $status, 'snapshot' => [
                'zone' => $zone->name, 'shift' => 'Mañana', 'hours' => '08:00 - 12:00',
                'vehicle' => $vehicle->name, 'plate' => $vehicle->plate,
            ],
        ]);
    }
}
