<?php

namespace Tests\Feature;

use App\Models\admin\Contract;
use App\Models\admin\Vacation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class VacationProgrammingConflictTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeId;

    private int $shiftId;

    private int $staffGroupId;

    private int $vehicleId;

    private int $zoneId;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('VacationProgrammingConflictTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 6, 1)->startOfDay());
        $this->actingAs(User::factory()->create());

        $employeeTypeId = DB::table('employee_types')->insertGetId([
            'name' => 'Conductor',
            'description' => 'Tipo de prueba',
        ]);
        $this->employeeId = DB::table('employees')->insertGetId([
            'employee_type_id' => $employeeTypeId,
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'birthdate' => '1990-01-01',
            'email' => 'ana.programming@example.test',
            'password' => bcrypt('test-password'),
            'status' => 'Activo',
        ]);
        Contract::create([
            'employee_id' => $this->employeeId,
            'type' => 'Permanente',
            'start_date' => '2025-01-01',
            'salary' => 1500,
            'is_active' => true,
        ]);

        $departmentId = DB::table('departments')->insertGetId(['code' => '14', 'name' => 'Lambayeque']);
        $provinceId = DB::table('provinces')->insertGetId(['department_id' => $departmentId, 'code' => '1401', 'name' => 'Chiclayo']);
        $districtId = DB::table('districts')->insertGetId(['province_id' => $provinceId, 'code' => '140105', 'name' => 'José Leonardo Ortiz']);
        $this->zoneId = DB::table('zones')->insertGetId(['name' => 'Zona de prueba', 'district_id' => $districtId]);
        $this->shiftId = DB::table('shifts')->insertGetId(['name' => 'Turno de prueba', 'start_time' => '06:00', 'end_time' => '14:00']);
        $brandId = DB::table('brands')->insertGetId(['name' => 'Marca de prueba']);
        $modelId = DB::table('brandmodels')->insertGetId(['name' => 'Modelo de prueba', 'brand_id' => $brandId]);
        $typeId = DB::table('vehicletypes')->insertGetId(['name' => 'Tipo de prueba']);
        $colorId = DB::table('color')->insertGetId(['name' => 'Verde', 'code' => '#176542']);
        $this->vehicleId = DB::table('vehicles')->insertGetId([
            'code' => 'TEST-1',
            'name' => 'Vehículo de prueba',
            'plate' => 'TST-001',
            'year' => 2026,
            'load_capacity' => 5,
            'occupats_capacity' => 3,
            'fuel_capacity' => 10,
            'compact_capacity' => 5,
            'type_id' => $typeId,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'color_id' => $colorId,
        ]);
        $this->staffGroupId = DB::table('staff_groups')->insertGetId([
            'name' => 'Grupo de prueba',
            'shift_id' => $this->shiftId,
            'zone_id' => $this->zoneId,
            'vehicle_id' => $this->vehicleId,
            'driver_id' => $this->employeeId,
            'state' => true,
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_creation_rejects_endpoint_and_partial_overlaps_for_driver_and_helper_assignments(): void
    {
        $this->programming('2026-07-05', 'driver');
        $this->programming('2026-07-15', 'helper');

        foreach ([
            ['2026-07-01', '2026-07-05'],
            ['2026-07-05', '2026-07-10'],
            ['2026-07-10', '2026-07-16'],
            ['2026-07-12', '2026-07-15'],
        ] as [$start, $end]) {
            $this->postJson(route('vacations.store'), $this->data([
                'start_date' => $start,
                'end_date' => $end,
            ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');
        }

        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-10',
        ]))->assertCreated();

        $this->assertDatabaseCount('vacations', 1);
    }

    public function test_creation_allows_anulled_and_soft_deleted_programmings(): void
    {
        $anulledId = $this->programming('2026-07-05', 'driver', 'Anulado');
        $deletedId = $this->programming('2026-07-06', 'helper');
        DB::table('programmings')->where('id', $deletedId)->update(['deleted_at' => now()]);

        $this->assertSame(0, DB::table('programmings')
            ->join('programming_employees', 'programming_employees.programming_id', '=', 'programmings.id')
            ->where('programming_employees.employee_id', $this->employeeId)
            ->whereBetween('programming_employees.date', ['2026-07-05', '2026-07-06'])
            ->where('programmings.status', '!=', 'Anulado')
            ->whereNull('programmings.deleted_at')
            ->count());

        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-06',
        ]))->assertCreated();

        $this->assertDatabaseHas('programmings', ['id' => $anulledId, 'status' => 'Anulado']);
        $this->assertDatabaseHas('programmings', ['id' => $deletedId]);
    }

    public function test_edit_rejects_conflict_and_keeps_the_pending_vacation_unchanged(): void
    {
        $response = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-22',
        ]))->assertCreated();
        $vacation = Vacation::findOrFail($response->json('data.id'));
        $this->programming('2026-07-25', 'helper');

        $this->putJson(route('vacations.update', $vacation), $this->data([
            'start_date' => '2026-07-24',
            'end_date' => '2026-07-26',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $unchanged = $vacation->fresh();
        $this->assertSame('2026-07-20', $unchanged->start_date->toDateString());
        $this->assertSame('2026-07-22', $unchanged->end_date->toDateString());
        $this->assertTrue($unchanged->isPending());
    }

    public function test_approval_rejects_a_newly_created_conflict_and_keeps_the_request_pending(): void
    {
        $response = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-22',
        ]))->assertCreated();
        $vacation = Vacation::findOrFail($response->json('data.id'));
        $this->programming('2026-07-22', 'driver');

        $this->postJson(route('vacations.approve', $vacation))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_date');

        $this->assertDatabaseHas('vacations', [
            'id' => $vacation->id,
            'status' => 'Pendiente',
        ]);
    }

    private function programming(string $date, string $role, string $status = 'Programado'): int
    {
        $programmingId = DB::table('programmings')->insertGetId([
            'staff_group_id' => $this->staffGroupId,
            'zone_id' => $this->zoneId,
            'shift_id' => $this->shiftId,
            'vehicle_id' => $this->vehicleId,
            'driver_id' => $this->employeeId,
            'date' => $date,
            'status' => $status,
            'snapshot' => json_encode(['group' => 'Grupo de prueba']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('programming_employees')->insert([
            'programming_id' => $programmingId,
            'employee_id' => $this->employeeId,
            'shift_id' => $this->shiftId,
            'date' => $date,
            'role' => $role,
        ]);

        return $programmingId;
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->employeeId,
            'year' => 2026,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ], $overrides);
    }
}
