<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeId;
    private int $morningShiftId;
    private int $afternoonShiftId;
    private int $nightShiftId;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-30 08:00:00');

        $typeId = DB::table('employee_types')->insertGetId([
            'name' => 'Operario',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->employeeId = $this->createEmployee(
            $typeId, '87654321', 'Ana', 'Pérez', 'employee@test.local', 'secret123'
        );

        $this->createContract($this->employeeId);

        $this->morningShiftId = $this->createShift('Mañana', '07:00:00', '12:00:00');
        $this->afternoonShiftId = $this->createShift('Tarde', '12:00:00', '17:00:00');
        $this->nightShiftId = $this->createShift('Noche', '20:00:00', '02:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_kiosk_remains_a_single_form_without_personal_navigation(): void
    {
        $response = $this->get(route('employee.home'))
            ->assertOk()
            ->assertSee('Control de asistencia')
            ->assertSee('Registrar asistencia')
            ->assertSee('name="dni"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('X-CSRF-TOKEN', false)
            ->assertDontSee('employee.dashboard');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertNull(app('router')->getRoutes()->getByName('employee.dashboard'));
    }

    public function test_active_worker_can_mark_without_any_programming(): void
    {
        Carbon::setTestNow('2026-10-30 07:00:00');

        $response = $this->postJson(route('employee.authenticate'), $this->credentials());

        $response->assertOk()
            ->assertJsonPath('type', 'Entrada')
            ->assertJsonPath('shift_id', $this->morningShiftId)
            ->assertJsonPath('status', 'Presente')
            ->assertJsonPath('origin', 'No programado');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'date' => '2026-10-30',
            'time' => '07:00:00',
            'type' => 'Entrada',
            'status' => 'Presente',
            'origin' => 'No programado',
        ]);

        $this->assertDatabaseCount('programming_employees', 0);
        $this->assertGuest('employee');
    }

    public function test_mark_at_1143_belongs_to_morning_and_is_late(): void
    {
        Carbon::setTestNow('2026-10-30 11:43:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Entrada')
            ->assertJsonPath('shift_id', $this->morningShiftId)
            ->assertJsonPath('status', 'Tardanza');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'type' => 'Entrada',
            'status' => 'Tardanza',
        ]);
    }

    public function test_mark_at_1145_belongs_to_afternoon_and_is_early(): void
    {
        Carbon::setTestNow('2026-10-30 11:45:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Entrada')
            ->assertJsonPath('shift_id', $this->afternoonShiftId)
            ->assertJsonPath('status', 'Presente');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->afternoonShiftId,
            'type' => 'Entrada',
        ]);
    }

    public function test_pending_morning_entry_is_closed_as_morning_exit_at_afternoon_cutoff(): void
    {
        Carbon::setTestNow('2026-10-30 07:00:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();

        Carbon::setTestNow('2026-10-30 11:45:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Salida')
            ->assertJsonPath('shift_id', $this->morningShiftId);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->morningShiftId,
            'date' => '2026-10-30',
            'type' => 'Salida',
            'status' => 'Presente',
        ]);

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->afternoonShiftId,
            'type' => 'Entrada',
        ]);
    }

    public function test_worker_can_complete_morning_then_start_afternoon_on_same_date(): void
    {
        Carbon::setTestNow('2026-10-30 07:00:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');

        Carbon::setTestNow('2026-10-30 11:45:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Salida')
            ->assertJsonPath('shift_id', $this->morningShiftId);

        Carbon::setTestNow('2026-10-30 11:46:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Entrada')
            ->assertJsonPath('shift_id', $this->afternoonShiftId);

        Carbon::setTestNow('2026-10-30 17:00:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Salida')
            ->assertJsonPath('shift_id', $this->afternoonShiftId);

        $this->assertDatabaseCount('attendances', 4);
    }

    public function test_overnight_entry_is_closed_on_the_following_calendar_date(): void
    {
        Carbon::setTestNow('2026-10-30 20:00:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Entrada')
            ->assertJsonPath('shift_id', $this->nightShiftId);

        Carbon::setTestNow('2026-10-31 01:30:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Salida')
            ->assertJsonPath('shift_id', $this->nightShiftId);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->nightShiftId,
            'date' => '2026-10-30',
            'type' => 'Entrada',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->nightShiftId,
            'date' => '2026-10-31',
            'type' => 'Salida',
        ]);
    }

    public function test_entry_is_blocked_before_the_fifteen_minute_advance_window(): void
    {
        Carbon::setTestNow('2026-10-30 06:44:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attendance');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_inactive_employee_cannot_mark(): void
    {
        DB::table('employees')->where('id', $this->employeeId)->update(['status' => 'Inactivo']);
        Carbon::setTestNow('2026-10-30 07:00:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('credentials');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_employee_on_approved_vacation_cannot_mark(): void
    {
        DB::table('vacations')->insert([
            'employee_id' => $this->employeeId,
            'year' => 2026,
            'start_date' => '2026-10-30',
            'end_date' => '2026-11-02',
            'scheduled_days' => 4,
            'pending_days' => 20,
            'status' => 'Aprobada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow('2026-10-30 07:00:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attendance');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_operational_worker_without_programming_is_classified_as_retainer(): void
    {
        $conductorTypeId = DB::table('employee_types')->insertGetId([
            'name' => 'Conductor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $retainerId = $this->createEmployee(
            $conductorTypeId, '22334455', 'Luis', 'Retén', 'retainer@test.local', 'retainer123'
        );
        $this->createContract($retainerId);

        Carbon::setTestNow('2026-10-30 07:00:00');
        $this->postJson(route('employee.authenticate'), [
            'dni' => '22334455',
            'password' => 'retainer123',
        ])->assertOk()
            ->assertJsonPath('origin', 'Retén');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $retainerId,
            'origin' => 'Retén',
            'type' => 'Entrada',
        ]);
    }

    public function test_invalid_credentials_do_not_create_attendance(): void
    {
        $this->postJson(route('employee.authenticate'), [
            'dni' => '87654321',
            'password' => 'incorrecta',
        ])->assertUnprocessable()->assertJsonValidationErrors('credentials');

        $this->assertDatabaseCount('attendances', 0);
        $this->assertGuest('employee');
    }

    public function test_administrative_web_session_is_preserved_after_kiosk_marking(): void
    {
        Carbon::setTestNow('2026-10-30 07:00:00');
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();

        $this->assertAuthenticated('web');
        $this->assertGuest('employee');
    }

    public function test_ajax_registration_accepts_the_current_session_csrf_token(): void
    {
        Carbon::setTestNow('2026-10-30 07:00:00');
        $this->get(route('employee.home'));
        $this->app->instance('env', 'production');

        $response = $this->withMiddleware()
            ->withHeader('X-CSRF-TOKEN', session()->token())
            ->postJson(route('employee.authenticate'), $this->credentials());

        $response->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertGuest('employee');
    }

    public function test_ajax_registration_without_a_valid_csrf_token_is_rejected(): void
    {
        $this->get(route('employee.home'));
        $this->app->instance('env', 'production');

        $this->withMiddleware()
            ->withHeader('X-CSRF-TOKEN', 'invalid-token')
            ->postJson(route('employee.authenticate'), $this->credentials())
            ->assertStatus(419);

        $this->assertDatabaseCount('attendances', 0);
        $this->assertGuest('employee');
    }

    public function test_two_workers_can_use_the_same_csrf_token_consecutively(): void
    {
        Carbon::setTestNow('2026-10-30 07:00:00');
        $this->get(route('employee.home'));
        $token = session()->token();
        $secondId = $this->createEmployee(
            DB::table('employee_types')->value('id'),
            '11223344',
            'Luis',
            'Segundo',
            'second@test.local',
            'secret456'
        );
        $this->createContract($secondId);
        $this->app->instance('env', 'production');

        $this->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertGuest('employee');

        $this->withSession(['_token' => $token])
            ->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), ['dni' => '11223344', 'password' => 'secret456'])
            ->assertOk()->assertJsonPath('type', 'Entrada');

        $this->assertGuest('employee');
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_failed_then_successful_registration_can_reuse_the_same_csrf_token(): void
    {
        Carbon::setTestNow('2026-10-30 07:00:00');
        $this->get(route('employee.home'));
        $token = session()->token();
        $this->app->instance('env', 'production');

        $this->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), ['dni' => '87654321', 'password' => 'incorrecta'])
            ->assertUnprocessable()->assertJsonValidationErrors('credentials');

        $this->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');

        $this->assertGuest('employee');
    }

    private function credentials(): array
    {
        return ['dni' => '87654321', 'password' => 'secret123'];
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

    private function createEmployee(int $typeId, string $dni, string $firstName, string $lastName, string $email, string $password): int
    {
        return DB::table('employees')->insertGetId([
            'employee_type_id' => $typeId,
            'dni' => $dni,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birthdate' => '1990-01-01',
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'Activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createContract(int $employeeId): void
    {
        DB::table('contracts')->insert([
            'employee_id' => $employeeId,
            'type' => 'Permanente',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'salary' => 1500,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
