<?php

namespace Tests\Feature;

use App\Models\admin\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AttendancesCrudTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeId;
    private int $morningShiftId;
    private int $afternoonShiftId;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('AttendancesCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $employeeTypeId = DB::table('employee_types')->insertGetId([
            'name' => 'Operario',
            'description' => 'Tipo para pruebas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->employeeId = DB::table('employees')->insertGetId([
            'employee_type_id' => $employeeTypeId,
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'birthdate' => '1990-01-01',
            'phone' => '999999999',
            'address' => 'Dirección reservada',
            'email' => 'ana@example.test',
            'password' => 'password-hash-no-exponer',
            'status' => 'Activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contracts')->insert([
            'employee_id' => $this->employeeId,
            'type' => 'Permanente',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'salary' => 1500,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->morningShiftId = $this->createShift('Mañana', '07:00:00', '12:00:00');
        $this->afternoonShiftId = $this->createShift('Tarde', '12:00:00', '17:00:00');
    }

    public function test_create_and_edit_forms_do_not_require_programming_or_manual_status_selection(): void
    {
        $this->get(route('attendances.create'))
            ->assertOk()
            ->assertSee('12345678 - Ana Pérez')
            ->assertSee('Mañana (07:00 - 12:00)')
            ->assertSee('No es necesario que el empleado tenga una programación asignada.')
            ->assertSee('name="employee_id"', false)
            ->assertSee('name="shift_id"', false)
            ->assertSee('name="date"', false)
            ->assertSee('name="time"', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="notes"', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="origin"', false)
            ->assertDontSee('name="password"', false);

        $attendance = $this->createAttendance();

        $this->get(route('attendances.edit', $attendance))
            ->assertOk()
            ->assertSee(route('attendances.update', $attendance), false)
            ->assertSee('2026-10-16')
            ->assertSee('08:30');
    }

    public function test_admin_can_register_an_unprogrammed_entry_and_status_is_calculated(): void
    {
        $this->postJson(route('attendances.store'), $this->validData([
            'time' => '07:15',
            'type' => 'Entrada',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'Tardanza')
            ->assertJsonPath('data.origin', 'No programado');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'date' => '2026-10-16',
            'time' => '07:15',
            'type' => 'Entrada',
            'status' => 'Tardanza',
            'origin' => 'No programado',
        ]);

        $this->assertDatabaseCount('programming_employees', 0);
    }

    public function test_admin_can_add_exit_only_when_there_is_a_pending_entry(): void
    {
        $this->postJson(route('attendances.store'), $this->validData([
            'time' => '07:00',
            'type' => 'Entrada',
        ]))->assertCreated();

        $this->postJson(route('attendances.store'), $this->validData([
            'time' => '12:00',
            'type' => 'Salida',
        ]))->assertCreated()
            ->assertJsonPath('data.status', 'Presente');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'date' => '2026-10-16',
            'time' => '12:00',
            'type' => 'Salida',
            'status' => 'Presente',
            'origin' => 'No programado',
        ]);
    }

    public function test_admin_cannot_register_a_second_entry_for_same_worker_shift_and_date(): void
    {
        $this->postJson(route('attendances.store'), $this->validData([
            'time' => '07:00',
            'type' => 'Entrada',
        ]))->assertCreated();

        $this->postJson(route('attendances.store'), $this->validData([
            'time' => '07:30',
            'type' => 'Entrada',
        ]))->assertUnprocessable()->assertJsonValidationErrors('type');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_admin_cannot_register_an_isolated_exit(): void
    {
        $this->postJson(route('attendances.store'), $this->validData([
            'type' => 'Salida',
        ]))->assertUnprocessable()->assertJsonValidationErrors('type');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_admin_can_register_afternoon_entry_for_worker_without_programming(): void
    {
        $this->postJson(route('attendances.store'), $this->validData([
            'shift_id' => $this->afternoonShiftId,
            'time' => '11:45',
            'type' => 'Entrada',
        ]))->assertCreated()
            ->assertJsonPath('data.status', 'Presente')
            ->assertJsonPath('data.origin', 'No programado');
    }

    public function test_admin_update_recalculates_entry_status_from_shift_start(): void
    {
        $attendance = $this->createAttendance([
            'time' => '07:00',
            'status' => 'Presente',
        ]);

        $this->putJson(route('attendances.update', $attendance), $this->validData([
            'time' => '07:25',
            'type' => 'Entrada',
        ]))->assertOk();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'time' => '07:25',
            'status' => 'Tardanza',
            'origin' => 'No programado',
        ]);
    }

    public function test_admin_can_list_and_view_attendance_with_status_and_origin(): void
    {
        $attendance = $this->createAttendance([
            'status' => 'Tardanza',
            'origin' => 'No programado',
        ]);

        Attendance::create([
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'date' => '2026-10-16',
            'time' => '12:05',
            'type' => 'Salida',
            'status' => 'Presente',
            'origin' => 'No programado',
        ]);

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Listado de Asistencias')
            ->assertSee('attendance-date-from', false)
            ->assertSee('attendance-date-to', false)
            ->assertSee('Últimos 7 días')
            ->assertSee('Este mes');

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('attendances.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'date_from' => '2026-10-16',
                'date_to' => '2026-10-16',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $attendance->id)
            ->assertJsonPath('data.0.employee_name', 'Ana Pérez')
            ->assertJsonPath('data.0.shift_name', 'Mañana')
            ->assertJsonPath('data.0.date_display', '16/10/2026')
            ->assertJsonPath('data.0.entry_time', '08:30')
            ->assertJsonPath('data.0.exit_time', '12:05')
            ->assertJsonPath('data.0.origin', 'No programado');

        $this->get(route('attendances.show', $attendance))
            ->assertOk()
            ->assertSee('Detalle de asistencia')
            ->assertSee('Tardanza')
            ->assertSee('No programado')
            ->assertSee('Hora de entrada')
            ->assertSee('Hora de salida')
            ->assertSee('12:05')
            ->assertDontSee('password-hash-no-exponer')
            ->assertDontSee('ana@example.test');
    }

    public function test_attendance_listing_defaults_to_today_and_applies_date_range_filters(): void
    {
        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();

        $todayAttendance = $this->createAttendance(['date' => $today]);
        $this->createAttendance(['date' => $yesterday]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('attendances.index', [
                'draw' => 2,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $todayAttendance->id);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('attendances.index', [
                'draw' => 3,
                'start' => 0,
                'length' => 10,
                'date_from' => $yesterday,
                'date_to' => $today,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);
    }

    public function test_attendance_listing_pairs_overnight_exit_with_previous_day_entry(): void
    {
        $nightShiftId = $this->createShift('Noche', '20:00:00', '02:00:00');

        $entry = Attendance::create([
            'employee_id' => $this->employeeId,
            'shift_id' => $nightShiftId,
            'date' => '2026-10-16',
            'time' => '21:00',
            'type' => 'Entrada',
            'status' => 'Presente',
            'origin' => 'No programado',
        ]);

        Attendance::create([
            'employee_id' => $this->employeeId,
            'shift_id' => $nightShiftId,
            'date' => '2026-10-17',
            'time' => '01:00',
            'type' => 'Salida',
            'status' => 'Presente',
            'origin' => 'No programado',
        ]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('attendances.index', [
                'draw' => 4,
                'start' => 0,
                'length' => 10,
                'date_from' => '2026-10-16',
                'date_to' => '2026-10-16',
                'shift_id' => $nightShiftId,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $entry->id)
            ->assertJsonPath('data.0.entry_time', '21:00')
            ->assertJsonPath('data.0.exit_time', '01:00');
    }

    public function test_daily_attendance_history_returns_shift_status_and_origin(): void
    {
        $this->createAttendance([
            'status' => 'Tardanza',
            'origin' => 'No programado',
        ]);

        $this->getJson(route('attendances.history', [
            'employee_id' => $this->employeeId,
            'date' => '2026-10-16',
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.time', '08:30')
            ->assertJsonPath('0.type', 'Entrada')
            ->assertJsonPath('0.status', 'Tardanza')
            ->assertJsonPath('0.shift', 'Mañana')
            ->assertJsonPath('0.origin', 'No programado');
    }

    public function test_invalid_attendance_data_is_rejected(): void
    {
        $this->postJson(route('attendances.store'), [
            'employee_id' => 999999,
            'shift_id' => 999999,
            'date' => '16/10/2026',
            'time' => '25:61',
            'type' => 'Ingreso',
            'notes' => ['valor no válido'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'employee_id',
                'shift_id',
                'date',
                'time',
                'type',
                'notes',
            ]);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_admin_can_delete_an_attendance(): void
    {
        $attendance = $this->createAttendance();

        $this->deleteJson(route('attendances.destroy', $attendance))
            ->assertOk()
            ->assertJson(['mensaje' => 'Asistencia eliminada correctamente.']);

        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_unauthenticated_users_cannot_access_attendance_routes(): void
    {
        $attendance = $this->createAttendance();
        auth()->logout();

        $this->getJson(route('attendances.index'))->assertUnauthorized();
        $this->getJson(route('attendances.create'))->assertUnauthorized();
        $this->getJson(route('attendances.show', $attendance))->assertUnauthorized();
        $this->getJson(route('attendances.edit', $attendance))->assertUnauthorized();
        $this->postJson(route('attendances.store'), $this->validData())->assertUnauthorized();
        $this->putJson(route('attendances.update', $attendance), $this->validData())->assertUnauthorized();
        $this->deleteJson(route('attendances.destroy', $attendance))->assertUnauthorized();
    }

    private function createAttendance(array $overrides = []): Attendance
    {
        return Attendance::create(array_merge([
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'date' => '2026-10-16',
            'time' => '08:30',
            'type' => 'Entrada',
            'status' => 'Presente',
            'origin' => 'No programado',
            'notes' => 'Observación de prueba',
        ], $overrides));
    }

    private function createShift(string $name, string $start, string $end): int
    {
        return DB::table('shifts')->insertGetId([
            'name' => $name,
            'start_time' => $start,
            'end_time' => $end,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'date' => '2026-10-16',
            'time' => '08:30',
            'type' => 'Entrada',
            'notes' => 'Observación de prueba',
        ], $overrides);
    }
}
