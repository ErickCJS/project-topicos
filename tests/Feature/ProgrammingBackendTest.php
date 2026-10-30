<?php

namespace Tests\Feature;

use App\Models\admin\Contract;
use App\Models\admin\Employee;
use App\Models\admin\Holiday;
use App\Models\admin\Programming;
use App\Models\admin\Shift;
use App\Models\admin\StaffGroup;
use App\Models\admin\Vacation;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProgrammingBackendTest extends TestCase
{
    use RefreshDatabase;

    private StaffGroup $group;

    private Employee $driver;

    private Employee $helper;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Las pruebas de Programaciones requieren SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        DB::table('employee_types')->insert([['id' => 1, 'name' => 'Conductor'], ['id' => 2, 'name' => 'Ayudante']]);
        $this->driver = $this->employee(1);
        $this->helper = $this->employee(2);
        $department = DB::table('departments')->insertGetId(['code' => '14', 'name' => 'Lambayeque']);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => '1401', 'name' => 'Chiclayo']);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => '140105', 'name' => 'José Leonardo Ortiz']);
        $zone = Zone::create(['name' => 'Zona registrada', 'district_id' => $district]);
        $shift = Shift::create(['name' => 'Turno registrado', 'start_time' => '06:00', 'end_time' => '14:00']);
        $brand = DB::table('brands')->insertGetId(['name' => 'Marca']);
        $model = DB::table('brandmodels')->insertGetId(['name' => 'Modelo', 'brand_id' => $brand]);
        $type = DB::table('vehicletypes')->insertGetId(['name' => 'Recolector']);
        $color = DB::table('color')->insertGetId(['name' => 'Verde', 'code' => '#176542']);
        $vehicle = Vehicle::create(['name' => 'Recolector registrado', 'code' => 'RSU-1', 'plate' => 'ABC-123', 'year' => 2026, 'load_capacity' => 5, 'occupats_capacity' => 3, 'fuel_capacity' => 10, 'compact_capacity' => 5, 'brand_id' => $brand, 'model_id' => $model, 'type_id' => $type, 'color_id' => $color]);
        $this->group = StaffGroup::create(['name' => 'Equipo registrado', 'zone_id' => $zone->id, 'shift_id' => $shift->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id, 'state' => true]);
        foreach ([1, 3, 5, 7] as $day) {
            $this->group->days()->create(['day' => $day]);
        }
        $this->group->helpers()->attach($this->helper);
    }

    private function employee(int $role): Employee
    {
        $number = Employee::count() + 1;
        $person = Employee::create(['employee_type_id' => $role, 'dni' => str_pad((string) $number, 8, '0', STR_PAD_LEFT), 'first_name' => 'Persona '.$number, 'last_name' => 'Registrada', 'birthdate' => '1990-01-01', 'email' => 'person'.$number.'@example.test', 'password' => 'not-used', 'status' => 'Activo']);
        Contract::create(['employee_id' => $person->id, 'type' => 'Permanente', 'start_date' => '2026-01-01', 'salary' => 1500, 'is_active' => true]);

        return $person;
    }

    private function data(array $changes = []): array
    {
        return array_replace(['staff_group_id' => $this->group->id, 'driver_id' => $this->driver->id, 'helpers' => [$this->helper->id],
            'start_date' => '2026-11-02', 'end_date' => '2026-11-08', 'days' => [1, 3, 5, 7], 'notes' => 'Recorrido real', 'programming_id' => null], $changes);
    }

    private function checked(array $data): array
    {
        $check = $this->postJson(route('programming.check'), $data)->assertOk()->assertJsonPath('valid', true);

        return [...$data, 'validation_token' => $check->json('validation_token')];
    }

    private function generate(array $changes = [])
    {
        return $this->postJson(route('programming.store'), $this->checked($this->data($changes)))->assertCreated();
    }

    public function test_real_catalogs_and_daily_default_are_served(): void
    {
        $this->get(route('programming.index'))->assertOk()->assertSee('Equipo registrado')->assertSee('Zona registrada')->assertDontSee('Vista de demostración');
        $this->getJson(route('programming.group', $this->group))->assertOk()->assertJsonPath('capacity', 3)->assertJsonPath('days.3', 7);
        $this->generate();
        $this->travelTo(now()->setDate(2026, 11, 2));
        $this->getJson(route('programming.index'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.zone', 'Zona registrada');
        $this->getJson(route('programming.index', ['start_date' => '2026-11-01', 'end_date' => '2026-11-30']))->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_guests_cannot_read_or_write(): void
    {
        auth()->logout();
        $this->get(route('programming.index'))->assertRedirect(route('login'));
        $this->postJson(route('programming.check'), $this->data())->assertUnauthorized();
        $this->postJson(route('programming.store'), $this->data())->assertUnauthorized();
    }

    public function test_a_shift_used_by_a_staff_group_cannot_be_deleted(): void
    {
        $this->deleteJson(route('shifts.destroy', $this->group->shift_id))
            ->assertUnprocessable()->assertJsonValidationErrors('shift');
        $this->assertDatabaseHas('shifts', ['id' => $this->group->shift_id]);
    }

    public function test_calendar_holidays_duplicates_and_extra_weekdays(): void
    {
        Holiday::create(['name' => 'Feriado local', 'date' => '2026-11-04', 'state' => true]);
        Holiday::create(['name' => 'Inactivo', 'date' => '2026-11-06', 'state' => false]);
        $this->generate()->assertJsonPath('created', 3)->assertJsonPath('omitted_holidays.0', '2026-11-04');
        $this->assertDatabaseCount('programmings', 3);
        $this->generate()->assertJsonPath('created', 0)->assertJsonCount(3, 'omitted_duplicates');
        $this->generate(['days' => [1, 2, 3, 5, 7]])->assertJsonPath('created', 1);
        $this->assertDatabaseCount('programmings', 4);
        $this->assertDatabaseHas('programmings', ['date' => '2026-11-03']);
    }

    public function test_availability_checks_both_roles_and_suggests_real_available_people(): void
    {
        $replacement = $this->employee(1);
        $helperReplacement = $this->employee(2);
        Vacation::create(['employee_id' => $this->driver->id, 'year' => 2026, 'start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'scheduled_days' => 3, 'pending_days' => 27, 'status' => 'Aprobada']);
        $this->helper->contracts()->update(['end_date' => '2026-11-05']);
        $check = $this->postJson(route('programming.check'), $this->data())->assertOk()->assertJsonPath('valid', false);
        $this->assertStringContainsString('Vacaciones aprobadas', json_encode($check->json('issues'), JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('Sin contrato vigente', json_encode($check->json('issues')));
        $this->assertSame($replacement->id, $check->json('suggestions.'.$this->driver->id.'.0.id'));
        $this->assertSame($helperReplacement->id, $check->json('suggestions.'.$this->helper->id.'.0.id'));
        $this->assertDatabaseCount('programmings', 0);
    }

    public function test_validation_token_cannot_be_bypassed_or_reused_after_edits(): void
    {
        $this->postJson(route('programming.store'), $this->data())->assertUnprocessable();
        $checked = $this->checked($this->data());
        $this->postJson(route('programming.store'), [...$checked, 'notes' => 'Changed'])->assertUnprocessable();
        $this->assertDatabaseCount('programmings', 0);
        $this->postJson(route('programming.store'), $checked)->assertCreated();
        $this->postJson(route('programming.store'), $checked)->assertUnprocessable();
    }

    public function test_save_rechecks_database_when_availability_changes_after_validation(): void
    {
        $checked = $this->checked($this->data());
        Vacation::create(['employee_id' => $this->helper->id, 'year' => 2026, 'start_date' => '2026-11-02', 'end_date' => '2026-11-08', 'scheduled_days' => 7, 'pending_days' => 23, 'status' => 'Aprobada']);
        $this->postJson(route('programming.store'), $checked)->assertUnprocessable();
        $this->assertDatabaseCount('programmings', 0);
        $this->assertDatabaseCount('programming_employees', 0);
    }

    public function test_staff_and_vehicle_conflicts_but_different_shifts_are_allowed(): void
    {
        $this->generate();
        $other = $this->group->replicate();
        $other->name = 'Otro equipo';
        $other->save();
        $this->postJson(route('programming.check'), $this->data(['staff_group_id' => $other->id]))->assertOk()->assertJsonPath('valid', false);
        $shift = Shift::create(['name' => 'Otro turno', 'start_time' => '14:00', 'end_time' => '22:00']);
        $other->update(['shift_id' => $shift->id]);
        $this->generate(['staff_group_id' => $other->id])->assertJsonPath('created', 4);
        $this->assertDatabaseCount('programmings', 8);
    }

    public function test_capacity_roles_and_inactive_groups_are_rejected(): void
    {
        $this->group->vehicle->update(['occupats_capacity' => 1]);
        $this->postJson(route('programming.check'), $this->data())->assertUnprocessable();
        $this->postJson(route('programming.check'), $this->data(['driver_id' => $this->helper->id, 'helpers' => []]))->assertUnprocessable();
        $this->group->update(['state' => false]);
        $this->postJson(route('programming.check'), $this->data(['helpers' => []]))->assertUnprocessable();
    }

    public function test_group_changes_after_validation_require_a_new_check(): void
    {
        $checked = $this->checked($this->data());
        $this->group->update(['name' => 'Configuración actualizada']);
        $this->postJson(route('programming.store'), $checked)->assertUnprocessable();
        $this->assertDatabaseCount('programmings', 0);
    }

    public function test_helpers_cannot_be_assigned_to_another_vehicle_in_the_same_shift(): void
    {
        $this->generate();
        $otherDriver = $this->employee(1);
        $vehicle = $this->group->vehicle->replicate();
        $vehicle->plate = 'XYZ-456';
        $vehicle->code = 'RSU-2';
        $vehicle->save();
        $other = $this->group->replicate();
        $other->name = 'Otro vehículo';
        $other->vehicle_id = $vehicle->id;
        $other->driver_id = $otherDriver->id;
        $other->save();
        $response = $this->postJson(route('programming.check'), $this->data(['staff_group_id' => $other->id, 'driver_id' => $otherDriver->id]))
            ->assertOk()->assertJsonPath('valid', false);
        $this->assertSame($this->helper->id, $response->json('issues.0.employee_id'));
        $this->assertSame('Asignación en la misma fecha y turno', $response->json('issues.0.reason'));
    }

    public function test_vehicle_cannot_be_reserved_by_different_people_in_the_same_shift(): void
    {
        $this->generate();
        $otherDriver = $this->employee(1);
        $otherHelper = $this->employee(2);
        $other = $this->group->replicate();
        $other->name = 'Otro personal';
        $other->save();
        $response = $this->postJson(route('programming.check'), $this->data(['staff_group_id' => $other->id, 'driver_id' => $otherDriver->id, 'helpers' => [$otherHelper->id]]))
            ->assertOk()->assertJsonPath('valid', false);
        $this->assertSame('Vehículo asignado en la misma fecha y turno', $response->json('issues.0.reason'));
    }

    public function test_expired_validation_and_cancelled_contracts_are_rejected(): void
    {
        $checked = $this->checked($this->data());
        $this->travel(16)->minutes();
        $this->postJson(route('programming.store'), $checked)->assertUnprocessable();
        $this->driver->contracts()->update(['cancelled_at' => now()]);
        $this->postJson(route('programming.check'), $this->data())->assertOk()->assertJsonPath('valid', false);
        $this->assertDatabaseCount('programmings', 0);
    }

    public function test_snapshots_edit_finish_and_history_are_persisted(): void
    {
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $this->group->update(['name' => 'Plantilla renombrada']);
        $this->group->zone->update(['name' => 'Zona renombrada']);
        $this->getJson(route('programming.show', $row))->assertOk()->assertJsonPath('zone', 'Zona registrada')->assertJsonPath('group', 'Equipo registrado')->assertJsonPath('helper_ids.0', $this->helper->id);
        $data = $this->checked($this->data(['programming_id' => $row->id, 'end_date' => '2026-11-02', 'notes' => 'Cambio registrado']));
        $this->putJson(route('programming.update', $row), $data)->assertOk();
        $this->assertSame('Cambio registrado', $row->fresh()->notes);
        $this->postJson(route('programming.finish', $row))->assertOk();
        $this->assertSame('Finalizado', $row->fresh()->status);
        $this->assertNotNull($row->fresh()->finished_at);
        $this->getJson(route('programming.history', $row->id))->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.1.before.notes', 'Recorrido real')->assertJsonPath('data.1.after.notes', 'Cambio registrado');
        $this->deleteJson(route('programming.destroy', $row))->assertUnprocessable();
    }

    public function test_deletion_preserves_history_and_releases_reservations(): void
    {
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $this->deleteJson(route('programming.destroy', $row))->assertOk();
        $this->assertSoftDeleted($row);
        $this->assertDatabaseCount('programming_employees', 0);
        $this->getJson(route('programming.history', $row->id))->assertOk()->assertJsonPath('data.1.action', 'Eliminación');
        $this->generate(['end_date' => '2026-11-02'])->assertJsonPath('created', 1);
        $this->assertSame(1, Programming::count());
    }
}
