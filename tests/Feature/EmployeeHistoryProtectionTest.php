<?php

namespace Tests\Feature;

use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Models\admin\EmployeeType;
use App\Models\admin\Shift;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class EmployeeHistoryProtectionTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeType $employeeType;

    protected function beforeRefreshingDatabase(): void
    {
        if (
            config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new RuntimeException(
                'EmployeeHistoryProtectionTest requiere SQLite en memoria.'
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->employeeType = EmployeeType::create([
            'name' => 'Operador',
            'description' => 'Personal operativo',
        ]);
    }

    public function test_employee_with_attendance_is_deactivated_and_history_preserved(): void
    {
        $employee = $this->createEmployee();
        $shift = $this->createShift();
        $attendance = $this->createAttendance($employee, $shift);

        $this->deleteJson(route('personal.employees.destroy', $employee))
            ->assertOk()
            ->assertJson([
                'mensaje' => 'El empleado fue marcado como inactivo porque tiene asistencias o vacaciones registradas.',
            ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'Inactivo',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_employee_with_vacations_is_deactivated_and_history_preserved(): void
    {
        $employee = $this->createEmployee();
        $this->createVacation($employee);

        $this->deleteJson(route('personal.employees.destroy', $employee))
            ->assertOk();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'Inactivo',
        ]);

        $this->assertDatabaseHas('vacations', [
            'employee_id' => $employee->id,
            'year' => 2026,
        ]);
    }

    public function test_shift_with_attendance_cannot_be_deleted(): void
    {
        $employee = $this->createEmployee();
        $shift = $this->createShift();
        $attendance = $this->createAttendance($employee, $shift);

        $this->deleteJson(route('shifts.destroy', $shift))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shift');

        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_database_restricts_employee_deletion_with_attendance(): void
    {
        $employee = $this->createEmployee();
        $this->createAttendance($employee, $this->createShift());

        $this->assertDeleteRestricted(fn () => $employee->delete());

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('attendances', ['employee_id' => $employee->id]);
    }

    public function test_database_restricts_employee_deletion_with_contracts(): void
    {
        $employee = $this->createEmployee();

        DB::table('contracts')->insert([
            'employee_id' => $employee->id,
            'type' => 'Temporal',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
            'salary' => 1500,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDeleteRestricted(fn () => $employee->delete());

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('contracts', ['employee_id' => $employee->id]);
    }

    public function test_database_restricts_employee_deletion_with_vacations(): void
    {
        $employee = $this->createEmployee();
        $this->createVacation($employee);

        $this->assertDeleteRestricted(fn () => $employee->delete());

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('vacations', ['employee_id' => $employee->id]);
    }

    public function test_database_restricts_shift_deletion_with_attendance(): void
    {
        $employee = $this->createEmployee();
        $shift = $this->createShift();
        $this->createAttendance($employee, $shift);

        $this->assertDeleteRestricted(fn () => $shift->delete());

        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
    }

    public function test_database_restricts_employee_type_deletion_with_employees(): void
    {
        $employee = $this->createEmployee();

        $this->assertDeleteRestricted(fn () => $this->employeeType->delete());

        $this->assertDatabaseHas('employee_types', [
            'id' => $this->employeeType->id,
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
    }

    private function createEmployee(): Employee
    {
        return Employee::create([
            'employee_type_id' => $this->employeeType->id,
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'birthdate' => '1990-01-01',
            'email' => 'ana@example.test',
            'password' => Hash::make('password'),
            'status' => 'Activo',
        ]);
    }

    private function createShift(): Shift
    {
        return Shift::create([
            'name' => 'Turno mañana',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);
    }

    private function createAttendance(Employee $employee, Shift $shift): Attendance
    {
        return Attendance::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'date' => '2026-10-30',
            'time' => '08:00:00',
            'type' => 'Entrada',
            'status' => 'Presente',
        ]);
    }

    private function createVacation(Employee $employee): void
    {
        DB::table('vacations')->insert([
            'employee_id' => $employee->id,
            'year' => 2026,
            'scheduled_days' => 30,
            'pending_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertDeleteRestricted(callable $operation): void
    {
        try {
            $operation();
            $this->fail('La base de datos permitió una eliminación que debía restringir.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
    }
}