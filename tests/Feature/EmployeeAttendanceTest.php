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
    private int $shiftId;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-30 08:00:00');
        $type = DB::table('employee_types')->insertGetId(['name' => 'Operario', 'created_at' => now(), 'updated_at' => now()]);
        $this->employeeId = $this->createEmployee($type, '87654321', 'Ana', 'Pérez', 'employee@test.local', 'secret123');
        DB::table('contracts')->insert([
            'employee_id' => $this->employeeId, 'type' => 'Permanente', 'start_date' => '2026-01-01',
            'end_date' => null, 'salary' => 1500, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->shiftId = DB::table('shifts')->insertGetId([
            'name' => 'Mañana', 'start_time' => '05:00:00', 'end_time' => '13:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_kiosk_is_a_single_form_without_personal_navigation(): void
    {
        $this->get(route('employee.home'))->assertOk()
            ->assertSee('Control de asistencia')
            ->assertSee('Registrar asistencia')
            ->assertSee('X-CSRF-TOKEN')
            ->assertSee("credentials: 'same-origin'", false)
            ->assertDontSee('Registrar entrada')
            ->assertDontSee('Registrar salida')
            ->assertDontSee('dashboard');
        $this->assertNull(app('router')->getRoutes()->getByName('employee.dashboard'));
        $this->assertNull(app('router')->getRoutes()->getByName('employee.kiosk'));
    }

    public function test_ajax_registration_accepts_the_current_session_csrf_token(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->get(route('employee.home'));
        $this->schedule();
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
        $this->schedule();
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
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->get(route('employee.home'));
        $token = session()->token();
        $this->schedule();
        $type = DB::table('employee_types')->value('id');
        $secondId = $this->createEmployee($type, '11223344', 'Luis', 'Segundo', 'second@test.local', 'secret456');
        $this->schedule(null, $this->shiftId, $secondId);
        $this->app->instance('env', 'production');

        $this->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertGuest('employee');
        $this->assertSame($token, session()->token());
        $this->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), ['dni' => '11223344', 'password' => 'secret456'])
            ->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertGuest('employee');
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_failed_then_successful_registration_can_reuse_the_same_csrf_token(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->get(route('employee.home'));
        $token = session()->token();
        $this->schedule();
        $this->app->instance('env', 'production');

        $this->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), ['dni' => '87654321', 'password' => 'incorrecta'])
            ->assertUnprocessable()->assertJsonValidationErrors('credentials');

        $this->withMiddleware()->withHeader('X-CSRF-TOKEN', $token)
            ->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertGuest('employee');
    }

    public function test_valid_credentials_register_automatic_entry_without_navigation(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();

        $response = $this->postJson(route('employee.authenticate'), $this->credentials());

        $response->assertOk()->assertJson([
            'message' => 'Entrada registrada correctamente',
            'type' => 'Entrada',
        ]);
        $this->assertGuest('employee');
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId, 'shift_id' => $this->shiftId,
            'type' => 'Entrada', 'status' => 'Presente',
        ]);
    }

    public function test_entry_exactly_thirty_minutes_before_shift_is_allowed(): void
    {
        Carbon::setTestNow('2026-10-30 04:30:00');
        $this->schedule();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
    }

    public function test_entry_at_shift_start_is_allowed(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
    }

    public function test_entry_exactly_thirty_minutes_after_shift_is_allowed(): void
    {
        Carbon::setTestNow('2026-10-30 05:30:00');
        $this->schedule();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
    }

    public function test_entry_thirty_one_minutes_after_shift_is_blocked_for_worker(): void
    {
        Carbon::setTestNow('2026-10-30 05:31:00');
        $this->schedule();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()
            ->assertJsonPath('errors.attendance.0', 'Tu tiempo de tolerancia ha finalizado. Acércate a administración para registrar tu asistencia');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_exit_is_allowed_after_shift_end(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();
        Carbon::setTestNow('2026-10-30 23:00:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Salida');
    }

    public function test_administrative_exception_entry_allows_worker_exit(): void
    {
        Carbon::setTestNow('2026-10-30 23:00:00');
        $this->schedule();
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $this->postJson(route('attendances.store'), [
            'employee_id' => $this->employeeId, 'shift_id' => $this->shiftId,
            'date' => '2026-10-30', 'time' => '05:31',
            'type' => 'Entrada', 'status' => 'Presente',
        ])->assertCreated();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Salida');
    }

    public function test_without_entry_worker_cannot_create_an_exit(): void
    {
        Carbon::setTestNow('2026-10-30 23:00:00');
        $this->schedule();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()->assertJsonValidationErrors('attendance');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_multiple_assigned_shifts_use_the_only_current_tolerance_window(): void
    {
        Carbon::setTestNow('2026-10-30 10:00:00');
        $this->schedule();
        $secondShift = DB::table('shifts')->insertGetId([
            'name' => 'Mediodía', 'start_time' => '10:00:00', 'end_time' => '18:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->schedule(null, $secondShift);

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('shift_id', $secondShift);
        $this->assertDatabaseHas('attendances', ['shift_id' => $secondShift, 'type' => 'Entrada']);
        $this->assertDatabaseMissing('attendances', ['shift_id' => $this->shiftId]);
    }

    public function test_multiple_groups_with_the_same_shift_are_one_assignment(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        $this->schedule(null, $this->shiftId, $this->employeeId, false, null, false);

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada')
            ->assertJsonPath('shift_id', $this->shiftId);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_helper_assignment_from_staff_group_is_recognized(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $driver = $this->createEmployee(DB::table('employee_types')->value('id'), '11223344', 'Conductor', 'Grupo', 'driver@test.local', 'driver123');
        $this->schedule(null, $this->shiftId, $this->employeeId, true, $driver);

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertDatabaseHas('attendances', ['employee_id' => $this->employeeId, 'type' => 'Entrada']);
        $this->assertDatabaseMissing('attendances', ['employee_id' => $driver, 'type' => 'Entrada']);
    }

    public function test_second_consecutive_submission_registers_automatic_exit(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();
        Carbon::setTestNow('2026-10-30 15:00:00');

        $response = $this->postJson(route('employee.authenticate'), $this->credentials());

        $response->assertOk()->assertJson([
            'message' => 'Salida registrada correctamente',
            'type' => 'Salida',
        ]);
        $this->assertGuest('employee');
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId, 'shift_id' => $this->shiftId,
            'type' => 'Salida', 'status' => 'Presente',
        ]);
    }

    public function test_multiple_workers_can_use_the_same_kiosk_session_consecutively(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        $type = DB::table('employee_types')->value('id');
        $secondId = $this->createEmployee($type, '11223344', 'Luis', 'Segundo', 'second@test.local', 'secret456');
        $this->schedule(null, $this->shiftId, $secondId);

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertGuest('employee');

        $this->postJson(route('employee.authenticate'), ['dni' => '11223344', 'password' => 'secret456'])
            ->assertOk()->assertJsonPath('type', 'Entrada');
        $this->assertGuest('employee');
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_invalid_credentials_are_returned_to_the_same_form_and_do_not_mark(): void
    {
        $response = $this->postJson(route('employee.authenticate'), [
            'dni' => '87654321', 'password' => 'wrong',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('credentials');
        $this->assertGuest('employee');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_worker_without_programming_cannot_mark(): void
    {
        $response = $this->postJson(route('employee.authenticate'), $this->credentials());

        $response->assertUnprocessable()->assertJsonValidationErrors('attendance');
        $this->assertGuest('employee');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_staff_group_without_daily_programming_cannot_mark(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        DB::table('programming_employees')->delete();
        DB::table('programmings')->delete();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()
            ->assertJsonPath('errors.attendance.0', 'No tienes una programación asignada para esta fecha. Comunícate con administración o tu supervisor.');
    }

    public function test_annulled_deleted_and_finished_programmings_do_not_authorize_new_entries(): void
    {
        foreach (['Anulado', 'Finalizado'] as $status) {
            Carbon::setTestNow('2026-10-30 05:00:00');
            $this->schedule();
            DB::table('programmings')->update(['status' => $status]);

            $this->postJson(route('employee.authenticate'), $this->credentials())
                ->assertUnprocessable()
                ->assertJsonValidationErrors('attendance');

            DB::table('programming_employees')->delete();
            DB::table('programmings')->delete();
        }

        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        DB::table('programmings')->update(['deleted_at' => now()]);

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attendance');
    }

    public function test_daily_programming_authorizes_worker_even_without_current_staff_group_membership(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        DB::table('staff_groups')->update(['state' => false, 'driver_id' => null]);
        DB::table('staff_group_days')->delete();
        DB::table('staff_group_helpers')->delete();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
    }

    public function test_daily_programming_shift_is_used_instead_of_staff_group_shift(): void
    {
        Carbon::setTestNow('2026-10-30 10:00:00');
        $this->schedule();
        $dailyShiftId = DB::table('shifts')->insertGetId([
            'name' => 'Diario', 'start_time' => '10:00:00', 'end_time' => '18:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('programmings')->update(['shift_id' => $dailyShiftId]);
        DB::table('programming_employees')->update(['shift_id' => $dailyShiftId]);

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()
            ->assertJsonPath('type', 'Entrada')
            ->assertJsonPath('shift_id', $dailyShiftId);
        $this->assertDatabaseHas('attendances', ['shift_id' => $dailyShiftId]);
    }

    public function test_worker_removed_from_daily_programming_cannot_mark(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        DB::table('programming_employees')->where('employee_id', $this->employeeId)->delete();

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attendance');
    }

    public function test_finished_programming_after_entry_still_allows_exit(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();
        DB::table('programmings')->update(['status' => 'Finalizado']);
        Carbon::setTestNow('2026-10-30 15:00:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Salida');
    }

    public function test_overnight_exit_uses_previous_day_entry_without_current_programming(): void
    {
        $overnightShiftId = DB::table('shifts')->insertGetId([
            'name' => 'Noche', 'start_time' => '22:00:00', 'end_time' => '06:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Carbon::setTestNow('2026-10-29 21:30:00');
        $this->schedule('2026-10-29', $overnightShiftId);
        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Entrada');
        Carbon::setTestNow('2026-10-30 06:00:00');

        $this->postJson(route('employee.authenticate'), $this->credentials())
            ->assertOk()->assertJsonPath('type', 'Salida')
            ->assertJsonPath('shift_id', $overnightShiftId);
    }

    public function test_completed_attendance_cannot_be_duplicated(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();
        Carbon::setTestNow('2026-10-30 15:00:00');
        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();
        Carbon::setTestNow('2026-10-30 16:00:00');

        $response = $this->postJson(route('employee.authenticate'), $this->credentials());

        $response->assertUnprocessable()->assertJsonValidationErrors('attendance');
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_web_admin_session_is_not_affected(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $this->schedule();
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->postJson(route('employee.authenticate'), $this->credentials())->assertOk();

        $this->assertAuthenticated('web');
        $this->assertGuest('employee');
    }

    public function test_employee_cannot_query_a_personal_history_or_another_worker(): void
    {
        $other = $this->createEmployee(
            DB::table('employee_types')->value('id'), '11112222', 'Otro', 'Trabajador', 'other@test.local', 'secret789'
        );
        DB::table('attendances')->insert([
            'employee_id' => $other, 'shift_id' => $this->shiftId, 'date' => now()->toDateString(),
            'time' => '07:00:00', 'type' => 'Entrada', 'status' => 'Presente',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('employee.home'))->assertOk()->assertDontSee('Otro')->assertDontSee('Trabajador');
        $this->assertNull(app('router')->getRoutes()->getByName('employee.attendances'));
    }

    public function test_operational_worker_can_mark_retainer_attendance_without_programming(): void
    {
        Carbon::setTestNow('2026-10-30 05:00:00');
        $type = DB::table('employee_types')->insertGetId(['name' => 'Conductor', 'created_at' => now(), 'updated_at' => now()]);
        $employeeId = $this->createEmployee($type, '22334455', 'Retén', 'Disponible', 'retainer@test.local', 'retainer123');
        DB::table('contracts')->insert([
            'employee_id' => $employeeId, 'type' => 'Permanente', 'start_date' => '2026-01-01',
            'end_date' => null, 'salary' => 1500, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->postJson(route('employee.authenticate'), [
            'dni' => '22334455', 'password' => 'retainer123',
        ])->assertOk()->assertJsonPath('origin', 'Retén');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employeeId, 'shift_id' => $this->shiftId,
            'origin' => 'Retén', 'type' => 'Entrada',
        ]);
        $this->assertDatabaseMissing('programming_employees', ['employee_id' => $employeeId]);
    }

    private function credentials(): array
    {
        return ['dni' => '87654321', 'password' => 'secret123'];
    }

    private function createEmployee(int $type, string $dni, string $firstName, string $lastName, string $email, string $password): int
    {
        return DB::table('employees')->insertGetId([
            'employee_type_id' => $type, 'dni' => $dni, 'first_name' => $firstName, 'last_name' => $lastName,
            'birthdate' => '1990-01-01', 'email' => $email, 'password' => Hash::make($password),
            'status' => 'Activo', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function schedule(?string $date = null, ?int $shiftId = null, ?int $employeeId = null, bool $helper = false, ?int $driverId = null, bool $daily = true): void
    {
        $date ??= now()->toDateString();
        $shiftId ??= $this->shiftId;
        $employeeId ??= $this->employeeId;
        $driverId ??= $helper ? $employeeId : $employeeId;
        $sequence = DB::table('departments')->count() + 1;
        $department = DB::table('departments')->insertGetId(['code' => str_pad((string) $sequence, 2, '0', STR_PAD_LEFT), 'name' => 'Lima '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT), 'name' => 'Lima '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'name' => 'Lima '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $zone = DB::table('zones')->insertGetId(['district_id' => $district, 'name' => 'Centro', 'created_at' => now(), 'updated_at' => now()]);
        $type = DB::table('vehicletypes')->insertGetId(['name' => 'Camión', 'created_at' => now(), 'updated_at' => now()]);
        $brand = DB::table('brands')->insertGetId(['name' => 'Marca', 'created_at' => now(), 'updated_at' => now()]);
        $model = DB::table('brandmodels')->insertGetId(['brand_id' => $brand, 'name' => 'Modelo', 'created_at' => now(), 'updated_at' => now()]);
        $color = DB::table('color')->insertGetId(['name' => 'Azul', 'code' => '#00f', 'created_at' => now(), 'updated_at' => now()]);
        $vehicle = DB::table('vehicles')->insertGetId([
            'code' => 'V-01', 'name' => 'Unidad', 'plate' => 'ABC-123', 'year' => 2024,
            'load_capacity' => 1, 'occupats_capacity' => 2, 'fuel_capacity' => 1, 'compact_capacity' => 1,
            'type_id' => $type, 'brand_id' => $brand, 'model_id' => $model, 'color_id' => $color,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $group = DB::table('staff_groups')->insertGetId([
            'name' => 'Grupo '.$sequence, 'shift_id' => $shiftId, 'zone_id' => $zone,
            'vehicle_id' => $vehicle, 'driver_id' => $helper ? $driverId : $employeeId,
            'state' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('staff_group_days')->insert([
            'staff_group_id' => $group,
            'day' => Carbon::parse($date)->dayOfWeekIso,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($helper) {
            DB::table('staff_group_helpers')->insert([
                'staff_group_id' => $group, 'employee_id' => $employeeId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if ($daily) {
            $programming = DB::table('programmings')->insertGetId([
                'staff_group_id' => $group, 'zone_id' => $zone, 'shift_id' => $shiftId,
                'vehicle_id' => $vehicle, 'driver_id' => $helper ? $driverId : $employeeId,
                'date' => $date, 'status' => 'Programado', 'snapshot' => json_encode([]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('programming_employees')->insert([
                'programming_id' => $programming, 'employee_id' => $helper ? $driverId : $employeeId,
                'shift_id' => $shiftId, 'date' => $date, 'role' => 'driver',
            ]);
            if ($helper) {
                DB::table('programming_employees')->insert([
                    'programming_id' => $programming, 'employee_id' => $employeeId,
                    'shift_id' => $shiftId, 'date' => $date, 'role' => 'helper',
                ]);
            }
        }
    }
}
