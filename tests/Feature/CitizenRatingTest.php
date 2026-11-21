<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\CitizenRating;
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

class CitizenRatingTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 10;

    public function test_authentication_and_identity_separation_are_enforced(): void
    {
        $this->postJson('/api/v1/ciudadano/calificaciones', [])->assertUnauthorized();

        $user = User::factory()->create();
        $this->withToken($user->createToken('user')->plainTextToken)
            ->postJson('/api/v1/ciudadano/calificaciones', [])->assertForbidden();

        $employee = $this->employee();
        $this->withToken($employee->createToken('employee')->plainTextToken)
            ->postJson('/api/v1/ciudadano/calificaciones', [])->assertForbidden();
    }

    public function test_completed_service_can_be_rated_once_and_client_cannot_set_citizen_id(): void
    {
        [$citizen, $programming] = $this->authorizedCompletedService();

        $response = $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/calificaciones', [
            'programming_id' => $programming->id,
            'stars' => 5,
            'comment' => 'Servicio correcto',
            'citizen_id' => 999999,
            'zone_id' => 999999,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.programming.id', $programming->id)
            ->assertJsonPath('data.stars', 5)
            ->assertJsonMissingPath('data.citizen_id')
            ->assertJsonMissingPath('data.programming.parent_programming_id');

        $this->assertDatabaseHas('citizen_ratings', [
            'citizen_id' => $citizen->id,
            'programming_id' => $programming->id,
            'stars' => 5,
        ]);

        $this->withToken($this->token($citizen))
            ->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $programming->id, 'stars' => 4])
            ->assertStatus(409);
    }

    public function test_validation_and_immutability_are_explicit(): void
    {
        [$citizen, $programming] = $this->authorizedCompletedService();
        $request = $this->withToken($this->token($citizen));

        $request->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $programming->id, 'stars' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('stars');
        $request->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $programming->id, 'stars' => 6])
            ->assertUnprocessable()->assertJsonValidationErrors('stars');
        $request->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $programming->id, 'stars' => 5, 'comment' => str_repeat('x', 501)])
            ->assertUnprocessable()->assertJsonValidationErrors('comment');

        $this->assertFalse((bool) collect(app('router')->getRoutes()->getRoutes())->contains(
            fn ($route) => in_array('DELETE', (array) $route->methods, true) && str_contains($route->uri, 'calificaciones')
        ));
    }

    public function test_only_historically_authorized_completed_final_programming_can_be_rated(): void
    {
        $zone = $this->zone('Histórica');
        $citizen = $this->citizen();
        $this->assignment($citizen, $zone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();

        $cases = [
            [Programming::STATUS_PROGRAMMED, true, true, 'programmed'],
            [Programming::STATUS_STARTED, true, true, 'started'],
            [Programming::STATUS_CANCELLED, false, false, 'cancelled'],
            [Programming::STATUS_FINISHED, false, false, 'unfinished'],
            [Programming::STATUS_FINISHED, true, true, 'future'],
        ];
        foreach ($cases as [$status, $started, $finished, $label]) {
            $date = $label === 'future' ? '2037-01-01' : '2026-11-10';
            $programming = $this->programming($zone, $vehicle, $driver, $status, $date, $started, $finished);
            $this->withToken($this->token($citizen))
                ->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $programming->id, 'stars' => 3])
                ->assertStatus(409);
        }

        $foreign = $this->zone('Ajena');
        $notAuthorized = $this->programming($foreign, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-10', true, true);
        $this->withToken($this->token($citizen))
            ->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $notAuthorized->id, 'stars' => 3])
            ->assertNotFound();
    }

    public function test_reprogrammed_chain_allows_only_final_destination(): void
    {
        [$citizen, $original] = $this->authorizedCompletedService(Programming::STATUS_RESCHEDULED);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $zone = Zone::findOrFail($original->zone_id);
        $intermediate = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-11', true, true, $original->id);
        $final = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-12', true, true, $intermediate->id);

        $token = $this->token($citizen);
        $this->withToken($token)->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $intermediate->id, 'stars' => 4])->assertStatus(409);
        $this->withToken($token)->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $final->id, 'stars' => 4])->assertCreated();
    }

    public function test_list_and_detail_are_private_paginated_and_filtered_by_intersection(): void
    {
        [$citizen, $included] = $this->authorizedCompletedService();
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $zone = Zone::findOrFail($included->zone_id);
        $this->assignment($citizen, $zone, '2026-10-01', '2026-10-31');
        $other = $this->programming($zone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-10-10', true, true);
        $foreignZone = $this->zone('Otra');
        $this->programming($foreignZone, $vehicle, $driver, Programming::STATUS_FINISHED, '2026-11-15', true, true);
        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $included->id, 'stars' => 5])->assertCreated();
        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/calificaciones', ['programming_id' => $other->id, 'stars' => 3])->assertCreated();

        $this->withToken($this->token($citizen))
            ->getJson('/api/v1/ciudadano/calificaciones?month=2026-11&from=2026-11-01&to=2026-11-20&per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.programming.parent_programming_id');

        $rating = CitizenRating::where('programming_id', $included->id)->firstOrFail();
        $this->withToken($this->token($citizen))->getJson('/api/v1/ciudadano/calificaciones/'.$rating->id)->assertOk();
        $otherCitizen = $this->citizen($zone);
        $this->assertNotSame($citizen->id, $otherCitizen->id);
        $this->assertDatabaseHas('citizen_ratings', ['id' => $rating->id, 'citizen_id' => $citizen->id]);
        $this->assertTrue(CitizenRating::where('citizen_id', $otherCitizen->id)->whereKey($rating->id)->doesntExist());
        app('auth')->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->token($otherCitizen)])
            ->getJson('/api/v1/ciudadano/calificaciones/'.$rating->id)->assertNotFound();
        $this->withToken($this->token($citizen))->getJson('/api/v1/ciudadano/calificaciones?from=2099-01-01')->assertUnprocessable();
    }

    public function test_rating_list_eager_loads_public_relations_without_locations_or_n_plus_one(): void
    {
        [$citizen, $programming] = $this->authorizedCompletedService();
        $rating = new CitizenRating(['programming_id' => $programming->id, 'stars' => 4]);
        $rating->citizen_id = $citizen->id;
        $rating->save();
        $counts = ['shift' => 0, 'vehicle' => 0, 'locations' => 0];
        DB::listen(function (QueryExecuted $query) use (&$counts): void {
            $sql = strtolower($query->sql);
            foreach (array_keys($counts) as $key) if (str_contains($sql, $key === 'locations' ? 'programming_locations' : $key.'s')) $counts[$key]++;
        });

        $this->withToken($this->token($citizen))->getJson('/api/v1/ciudadano/calificaciones')->assertOk();
        $this->assertSame(1, $counts['shift']);
        $this->assertSame(1, $counts['vehicle']);
        $this->assertSame(0, $counts['locations']);
    }

    private function authorizedCompletedService(string $status = Programming::STATUS_FINISHED): array
    {
        $zone = $this->zone('Centro');
        $citizen = $this->citizen($zone);
        $this->assignment($citizen, $zone, '2026-11-01', null);
        [$vehicle, $driver] = $this->vehicleAndDriver();
        $programming = $this->programming($zone, $vehicle, $driver, $status, '2026-11-10', true, $status === Programming::STATUS_FINISHED);
        return [$citizen, $programming];
    }

    private function citizen(?Zone $zone = null): Citizen
    {
        return Citizen::create(['dni' => (string) random_int(10000000, 99999999), 'first_name' => 'Ana', 'last_name' => 'Perez', 'email' => fake()->unique()->safeEmail(), 'password' => Hash::make('password123'), 'zone_id' => $zone?->id]);
    }

    private function token(Citizen $citizen): string { return $citizen->createToken('flutter-citizen')->plainTextToken; }

    private function assignment(Citizen $citizen, Zone $zone, string $assignedAt, ?string $endedAt): CitizenZoneAssignment
    {
        return CitizenZoneAssignment::create(['citizen_id' => $citizen->id, 'zone_id' => $zone->id, 'latitude' => -12.05, 'longitude' => -75.95, 'accuracy' => 10, 'assigned_at' => $assignedAt.' 08:00:00', 'ended_at' => $endedAt ? $endedAt.' 23:59:59' : null]);
    }

    private function zone(string $name): Zone
    {
        $n = self::$sequence++;
        $department = DB::table('departments')->insertGetId(['code' => str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'name' => 'Departamento '.$name]);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'name' => 'Provincia '.$name]);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => str_pad((string) $n, 6, '0', STR_PAD_LEFT), 'name' => 'Distrito '.$name]);
        return Zone::create(['name' => $name, 'district_id' => $district, 'perimeter' => ['type' => 'Polygon', 'coordinates' => [[[-76, -12], [-76, -12.1], [-75.9, -12.1], [-75.9, -12], [-76, -12]]]]]);
    }

    private function vehicleAndDriver(): array
    {
        $type = DB::table('vehicletypes')->insertGetId(['name' => 'Bus '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $brand = DB::table('brands')->insertGetId(['name' => 'Marca '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        $model = DB::table('brandmodels')->insertGetId(['name' => 'Modelo '.random_int(1, 999999), 'brand_id' => $brand, 'created_at' => now(), 'updated_at' => now()]);
        $color = DB::table('color')->insertGetId(['name' => 'Color '.random_int(1, 999999), 'code' => '#000000', 'created_at' => now(), 'updated_at' => now()]);
        $vehicle = Vehicle::create(['code' => 'V'.random_int(1000, 9999), 'name' => 'Vehiculo prueba', 'plate' => 'ABC-'.random_int(100, 999), 'year' => 2024, 'load_capacity' => 100, 'occupats_capacity' => 30, 'fuel_capacity' => 100, 'compact_capacity' => 10, 'type_id' => $type, 'brand_id' => $brand, 'model_id' => $model, 'color_id' => $color]);
        $driver = $this->employee('Conductor');
        return [$vehicle, $driver];
    }

    private function employee(string $prefix = 'Empleado'): Employee
    {
        $type = DB::table('employee_types')->insertGetId(['name' => $prefix.' '.random_int(1, 999999), 'created_at' => now(), 'updated_at' => now()]);
        return Employee::create(['employee_type_id' => $type, 'dni' => (string) random_int(10000000, 99999999), 'first_name' => $prefix, 'last_name' => 'Prueba', 'birthdate' => '1990-01-01', 'email' => fake()->unique()->safeEmail(), 'password' => Hash::make('password123'), 'status' => 'Activo']);
    }

    private function programming(Zone $zone, Vehicle $vehicle, Employee $driver, string $status, string $date, bool $started, bool $finished, ?int $parentId = null): Programming
    {
        return Programming::create(['parent_programming_id' => $parentId, 'zone_id' => $zone->id, 'shift_id' => $this->shift(), 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'date' => $date, 'status' => $status, 'started_at' => $started ? $date.' 08:00:00' : null, 'finished_at' => $finished ? $date.' 11:00:00' : null, 'snapshot' => ['shift' => 'Manana', 'hours' => '08:00 - 12:00', 'vehicle' => $vehicle->name, 'plate' => $vehicle->plate]]);
    }

    private function shift(): int
    {
        return DB::table('shifts')->insertGetId(['name' => 'Manana '.random_int(1, 999999), 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'created_at' => now(), 'updated_at' => now()]);
    }
}
