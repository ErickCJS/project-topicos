<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\CitizenZoneAssignment;
use App\Models\User;
use App\Models\admin\Zone;
use App\Services\CitizenZoneAssignmentService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CitizenZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinate_inside_polygon_is_detected_without_writing(): void
    {
        $zone = $this->zone('Centro', -1, -1, 1, 1);
        $citizen = $this->citizen();

        $response = $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
        ]);

        $response->assertOk()->assertJsonPath('data.zone.id', $zone->id)
            ->assertJsonPath('requires_confirmation', true);
        $this->assertNull($citizen->fresh()->zone_id);
        $this->assertDatabaseCount('citizen_zone_assignments', 0);
    }

    public function test_coordinate_outside_polygons_returns_not_found_without_changes(): void
    {
        $this->zone('Centro', -1, -1, 1, 1);
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 2, 'longitude' => 2, 'accuracy' => 10,
        ])->assertNotFound();

        $this->assertNull($citizen->fresh()->zone_id);
        $this->assertDatabaseCount('citizen_zone_assignments', 0);
    }

    public function test_coordinate_on_polygon_boundary_is_inside(): void
    {
        $zone = $this->zone('Centro', -1, -1, 1, 1);
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 0, 'longitude' => 1, 'accuracy' => 10,
        ])->assertOk()->assertJsonPath('data.zone.id', $zone->id);
    }

    public function test_confirmation_requires_explicit_confirmation_and_then_assigns_zone(): void
    {
        $zone = $this->zone('Centro', -1, -1, 1, 1);
        $citizen = $this->citizen();
        $token = $this->token($citizen);

        $this->withToken($token)->postJson('/api/v1/ciudadano/zonas/confirmar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
        ])->assertUnprocessable();
        $this->assertNull($citizen->fresh()->zone_id);

        $this->withToken($token)->postJson('/api/v1/ciudadano/zonas/confirmar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10, 'confirmed' => true,
        ])->assertOk()->assertJsonPath('changed', true)->assertJsonPath('data.zone.id', $zone->id);

        $this->assertSame($zone->id, $citizen->fresh()->zone_id);
        $this->assertDatabaseCount('citizen_zone_assignments', 1);
        $this->withToken($token)->getJson('/api/v1/ciudadano/zonas/actual')
            ->assertOk()->assertJsonPath('data.zone.id', $zone->id);
    }

    public function test_repeated_confirmation_in_same_zone_does_not_duplicate_assignment(): void
    {
        $zone = $this->zone('Centro', -1, -1, 1, 1);
        $citizen = $this->citizen();
        $payload = ['latitude' => 0, 'longitude' => 0, 'accuracy' => 10, 'confirmed' => true];

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/confirmar', $payload)->assertOk();
        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/confirmar', $payload)
            ->assertOk()->assertJsonPath('changed', false)->assertJsonPath('data.zone.id', $zone->id);

        $this->assertDatabaseCount('citizen_zone_assignments', 1);
    }

    public function test_multiple_matching_polygons_return_conflict_without_changes(): void
    {
        $this->zone('Centro', -1, -1, 1, 1);
        $this->zone('Superpuesta', -0.5, -0.5, 1.5, 1.5);
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
        ])->assertStatus(409);

        $this->assertNull($citizen->fresh()->zone_id);
        $this->assertDatabaseCount('citizen_zone_assignments', 0);
    }

    public function test_insufficient_gps_accuracy_is_rejected_without_writing(): void
    {
        $this->zone('Centro', -1, -1, 1, 1);
        $citizen = $this->citizen();

        config(['citizen.gps.max_accuracy_meters' => 25]);

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 25.01,
        ])->assertUnprocessable()->assertJsonValidationErrors('accuracy');

        $this->assertNull($citizen->fresh()->zone_id);
        $this->assertDatabaseCount('citizen_zone_assignments', 0);
    }

    public function test_invalid_persisted_polygon_is_rejected(): void
    {
        $zone = $this->zone('Invalida', -1, -1, 1, 1);
        $zone->update(['perimeter' => [
            'type' => 'Polygon',
            'coordinates' => [[[-1, -1], [1, 1], [-1, 1]]],
        ]]);
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
        ])->assertUnprocessable()->assertJsonValidationErrors('zone');
    }

    public function test_bow_tie_polygon_is_rejected_by_motor_independent_validation(): void
    {
        $zone = $this->zone('Auto cruzada', -1, -1, 1, 1);
        $zone->update(['perimeter' => [
            'type' => 'Polygon',
            'coordinates' => [[[-1, -1], [1, 1], [-1, 1], [1, -1], [-1, -1]]],
        ]]);
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
        ])->assertUnprocessable()->assertJsonValidationErrors('zone');
    }

    public function test_valid_concave_polygon_is_detected(): void
    {
        $zone = $this->zone('Concava', 0, 0, 2, 2);
        $zone->update(['perimeter' => [
            'type' => 'Polygon',
            'coordinates' => [[[0, 0], [2, 0], [2, 1], [1, 1], [1, 2], [0, 2], [0, 0]]],
        ]]);
        $citizen = $this->citizen();

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 1.5, 'longitude' => 0.5, 'accuracy' => 10,
        ])->assertOk()->assertJsonPath('data.zone.id', $zone->id);

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/detectar', [
            'latitude' => 1.5, 'longitude' => 1.5, 'accuracy' => 10,
        ])->assertNotFound();
    }

    public function test_existing_multiple_active_assignments_are_reconciled_deterministically(): void
    {
        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('La prueba de datos históricos duplicados usa el índice parcial SQLite.');
        }

        $zone = $this->zone('Historica', -1, -1, 1, 1);
        $citizen = $this->citizen();
        DB::statement('DROP INDEX IF EXISTS citizen_zone_assignments_one_active_index');

        try {
            $older = CitizenZoneAssignment::create([
                'citizen_id' => $citizen->id, 'zone_id' => $zone->id,
                'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
                'assigned_at' => now()->subMinutes(2),
            ]);
            $newer = CitizenZoneAssignment::create([
                'citizen_id' => $citizen->id, 'zone_id' => $zone->id,
                'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
                'assigned_at' => now()->subMinute(),
            ]);
            $citizen->update(['zone_id' => $zone->id]);

            $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/confirmar', [
                'latitude' => 0, 'longitude' => 0, 'accuracy' => 10, 'confirmed' => true,
            ])->assertOk()->assertJsonPath('changed', false);

            $this->assertNotNull($older->fresh()->ended_at);
            $this->assertNull($newer->fresh()->ended_at);
            $this->assertSame(1, CitizenZoneAssignment::where('citizen_id', $citizen->id)->whereNull('ended_at')->count());
        } finally {
            DB::table('citizen_zone_assignments')
                ->where('citizen_id', $citizen->id)
                ->whereNull('ended_at')
                ->orderBy('id')
                ->limit(1)
                ->update(['ended_at' => now()]);
            DB::statement('CREATE UNIQUE INDEX citizen_zone_assignments_one_active_index ON citizen_zone_assignments (citizen_id) WHERE ended_at IS NULL');
        }
    }

    public function test_zone_id_without_history_is_repaired_on_confirmation(): void
    {
        $zone = $this->zone('Sin historial', -1, -1, 1, 1);
        $citizen = $this->citizen();
        $citizen->update(['zone_id' => $zone->id]);

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/confirmar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10, 'confirmed' => true,
        ])->assertOk()->assertJsonPath('changed', true);

        $this->assertDatabaseCount('citizen_zone_assignments', 1);
    }

    public function test_zone_id_and_active_assignment_mismatch_returns_conflict_without_mutation(): void
    {
        $citizenZone = $this->zone('Zona ciudadano', -1, -1, 1, 1);
        $assignmentZone = $this->zone('Zona historial', 2, 2, 3, 3);
        $citizen = $this->citizen();
        $citizen->update(['zone_id' => $citizenZone->id]);
        $assignment = CitizenZoneAssignment::create([
            'citizen_id' => $citizen->id, 'zone_id' => $assignmentZone->id,
            'latitude' => 2.5, 'longitude' => 2.5, 'accuracy' => 10,
            'assigned_at' => now(),
        ]);

        $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/confirmar', [
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10, 'confirmed' => true,
        ])->assertStatus(409);

        $this->assertSame($citizenZone->id, $citizen->fresh()->zone_id);
        $this->assertNull($assignment->fresh()->ended_at);
    }

    public function test_ambiguous_active_assignments_without_current_zone_return_conflict(): void
    {
        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('La prueba de datos históricos duplicados usa el índice parcial SQLite.');
        }

        $firstZone = $this->zone('Ambigua uno', -1, -1, 1, 1);
        $secondZone = $this->zone('Ambigua dos', 2, 2, 3, 3);
        $citizen = $this->citizen();
        DB::statement('DROP INDEX IF EXISTS citizen_zone_assignments_one_active_index');

        try {
            CitizenZoneAssignment::create([
                'citizen_id' => $citizen->id, 'zone_id' => $firstZone->id,
                'latitude' => 0, 'longitude' => 0, 'accuracy' => 10, 'assigned_at' => now()->subMinute(),
            ]);
            CitizenZoneAssignment::create([
                'citizen_id' => $citizen->id, 'zone_id' => $secondZone->id,
                'latitude' => 2.5, 'longitude' => 2.5, 'accuracy' => 10, 'assigned_at' => now(),
            ]);

            $this->withToken($this->token($citizen))->postJson('/api/v1/ciudadano/zonas/confirmar', [
                'latitude' => 0, 'longitude' => 0, 'accuracy' => 10, 'confirmed' => true,
            ])->assertStatus(409);

            $this->assertSame(2, CitizenZoneAssignment::where('citizen_id', $citizen->id)->whereNull('ended_at')->count());
        } finally {
            DB::table('citizen_zone_assignments')
                ->where('citizen_id', $citizen->id)
                ->whereNull('ended_at')
                ->orderBy('id')
                ->limit(1)
                ->update(['ended_at' => now()]);
            DB::statement('CREATE UNIQUE INDEX citizen_zone_assignments_one_active_index ON citizen_zone_assignments (citizen_id) WHERE ended_at IS NULL');
        }
    }

    public function test_database_rejects_a_second_active_assignment(): void
    {
        $zone = $this->zone('Unica', -1, -1, 1, 1);
        $citizen = $this->citizen();
        $attributes = [
            'citizen_id' => $citizen->id, 'zone_id' => $zone->id,
            'latitude' => 0, 'longitude' => 0, 'accuracy' => 10,
            'assigned_at' => now(),
        ];
        CitizenZoneAssignment::create($attributes);

        try {
            CitizenZoneAssignment::create($attributes);
            $this->fail('The active assignment uniqueness constraint was not enforced.');
        } catch (QueryException $exception) {
            $this->assertTrue(true);
        }
    }

    public function test_non_citizen_token_cannot_use_zone_endpoints(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test-user')->plainTextToken)
            ->getJson('/api/v1/ciudadano/zonas/actual')->assertForbidden();
    }

    public function test_failed_assignment_transaction_preserves_previous_assignment(): void
    {
        $oldZone = $this->zone('Anterior', -2, -2, -1, -1);
        $newZone = $this->zone('Nueva', 1, 1, 2, 2);
        $citizen = $this->citizen();
        $citizen->update(['zone_id' => $oldZone->id]);
        $oldAssignment = CitizenZoneAssignment::create([
            'citizen_id' => $citizen->id,
            'zone_id' => $oldZone->id,
            'latitude' => -1.5,
            'longitude' => -1.5,
            'accuracy' => 10,
            'assigned_at' => now()->subDay(),
        ]);

        $failInsert = true;
        DB::listen(function (QueryExecuted $query) use (&$failInsert): void {
            if ($failInsert && str_contains(strtolower($query->sql), 'citizen_zone_assignments')
                && str_starts_with(strtolower(ltrim($query->sql)), 'insert')) {
                $failInsert = false;
                throw new \RuntimeException('Simulated assignment failure.');
            }
        });

        try {
            app(CitizenZoneAssignmentService::class)->confirm($citizen, 1.5, 1.5, 10);
            $this->fail('The simulated assignment failure was not raised.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated assignment failure.', $exception->getMessage());
        }

        $this->assertSame($oldZone->id, $citizen->fresh()->zone_id);
        $this->assertNull($oldAssignment->fresh()->ended_at);
        $this->assertDatabaseCount('citizen_zone_assignments', 1);
        $this->assertNotSame($oldZone->id, $newZone->id);
    }

    private function citizen(): Citizen
    {
        return Citizen::create([
            'dni' => (string) random_int(10000000, 99999999),
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
        ]);
    }

    private function token(Citizen $citizen): string
    {
        return $citizen->createToken('flutter-citizen')->plainTextToken;
    }

    private function zone(string $name, float $minLongitude, float $minLatitude, float $maxLongitude, float $maxLatitude): Zone
    {
        $department = DB::table('departments')->insertGetId(['code' => random_int(10, 99), 'name' => 'Departamento '.$name]);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => random_int(1000, 9999), 'name' => 'Provincia '.$name]);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => random_int(100000, 999999), 'name' => 'Distrito '.$name]);

        return Zone::create([
            'name' => $name,
            'district_id' => $district,
            'perimeter' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [$minLongitude, $minLatitude],
                    [$maxLongitude, $minLatitude],
                    [$maxLongitude, $maxLatitude],
                    [$minLongitude, $maxLatitude],
                    [$minLongitude, $minLatitude],
                ]],
            ],
        ]);
    }
}
