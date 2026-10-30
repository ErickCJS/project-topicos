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
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-30 12:00:00', 'America/Lima'));
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

    private function groupData(array $changes = []): array
    {
        return array_replace(['name' => 'Nuevo equipo', 'shift_id' => $this->group->shift_id,
            'zone_id' => $this->group->zone_id, 'vehicle_id' => $this->group->vehicle_id,
            'driver_id' => $this->driver->id, 'helpers' => [$this->helper->id], 'days' => [2], 'state' => true], $changes);
    }

    public function test_staff_groups_require_active_driver_and_valid_roles_on_create_and_edit(): void
    {
        foreach ([['driver_id' => null], ['driver_id' => $this->helper->id], ['helpers' => [$this->driver->id]]] as $changes) {
            $this->postJson(route('staffgroups.store'), $this->groupData($changes))->assertUnprocessable();
            $this->putJson(route('staffgroups.update', $this->group), $this->groupData($changes))->assertUnprocessable();
        }
        $this->driver->update(['status' => 'Inactivo']);
        $this->postJson(route('staffgroups.store'), $this->groupData())->assertUnprocessable()->assertJsonValidationErrors('driver_id');
        $this->driver->update(['status' => 'Activo']);
        $this->helper->update(['status' => 'Inactivo']);
        $this->postJson(route('staffgroups.store'), $this->groupData())->assertUnprocessable()->assertJsonValidationErrors('helpers.0');
        $this->assertDatabaseCount('staff_groups', 1);
    }

    public function test_staff_groups_require_at_least_one_helper_on_create_and_edit(): void
    {
        foreach ([null, [], [null], ['', null]] as $helpers) {
            $this->postJson(route('staffgroups.store'), $this->groupData(['helpers' => $helpers]))
                ->assertUnprocessable()->assertJsonValidationErrors('helpers');
            $this->putJson(route('staffgroups.update', $this->group), $this->groupData(['helpers' => $helpers]))
                ->assertUnprocessable()->assertJsonValidationErrors('helpers');
        }
        $this->assertDatabaseCount('staff_groups', 1);
        $this->assertSame(1, $this->group->helpers()->count());
        $this->postJson(route('staffgroups.store'), $this->groupData(['helpers' => [$this->helper->id, null]]))->assertCreated();
    }

    public function test_staff_groups_reject_repeated_days_helpers_and_excess_capacity(): void
    {
        $this->postJson(route('staffgroups.store'), $this->groupData(['days' => [2, 2]]))->assertUnprocessable()->assertJsonValidationErrors('days.0');
        $this->postJson(route('staffgroups.store'), $this->groupData(['helpers' => [$this->helper->id, $this->helper->id]]))->assertUnprocessable()->assertJsonValidationErrors('helpers.0');
        $this->group->vehicle->update(['occupats_capacity' => 1]);
        $this->postJson(route('staffgroups.store'), $this->groupData())->assertUnprocessable()->assertJsonValidationErrors('helpers');
        $this->putJson(route('staffgroups.update', $this->group), $this->groupData())->assertUnprocessable()->assertJsonValidationErrors('helpers');
        $this->assertDatabaseCount('staff_groups', 1);
    }

    public function test_staff_groups_block_vehicle_conflicts_across_zones_and_personnel_conflicts(): void
    {
        $zone = $this->group->zone->replicate(); $zone->name = 'Otra zona'; $zone->save();
        $this->postJson(route('staffgroups.store'), $this->groupData(['zone_id' => $zone->id, 'days' => [1]]))
            ->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
        $vehicle = $this->group->vehicle->replicate(); $vehicle->plate = 'XYZ-999'; $vehicle->code = 'OTRO'; $vehicle->save();
        $this->postJson(route('staffgroups.store'), $this->groupData(['vehicle_id' => $vehicle->id, 'days' => [1]]))
            ->assertUnprocessable()->assertJsonValidationErrors('driver_id');
        $driver = $this->employee(1);
        $this->postJson(route('staffgroups.store'), $this->groupData(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'days' => [1]]))
            ->assertUnprocessable()->assertJsonValidationErrors('helpers');
    }

    public function test_staff_groups_allow_other_days_and_own_edit_but_block_conflicting_update(): void
    {
        $this->postJson(route('staffgroups.store'), $this->groupData())->assertCreated();
        $other = StaffGroup::where('name', 'Nuevo equipo')->firstOrFail();
        $this->putJson(route('staffgroups.update', $other), $this->groupData())->assertOk();
        $this->putJson(route('staffgroups.update', $other), $this->groupData(['days' => [1]]))->assertUnprocessable();
        $this->assertSame([2], $other->days()->pluck('day')->all());
        $shift = Shift::create(['name' => 'Tarde', 'start_time' => '14:00', 'end_time' => '22:00']);
        $this->postJson(route('staffgroups.store'), $this->groupData(['name' => 'Otro turno', 'shift_id' => $shift->id, 'days' => [1]]))->assertCreated();
        $this->postJson(route('staffgroups.store'), $this->groupData(['name' => 'Otro turno']))->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_staff_groups_ignore_inactive_reservations_and_revalidate_activation(): void
    {
        $this->postJson(route('staffgroups.store'), $this->groupData(['days' => [1], 'state' => false]))->assertCreated();
        $other = StaffGroup::where('name', 'Nuevo equipo')->firstOrFail();
        $this->assertFalse((bool) $other->state);
        $this->putJson(route('staffgroups.update', $other), $this->groupData(['days' => [1], 'state' => true]))->assertUnprocessable();
        $this->group->update(['state' => false]);
        $this->putJson(route('staffgroups.update', $other), $this->groupData(['days' => [1], 'state' => true]))->assertOk();
    }

    public function test_staff_group_selector_uses_active_people_and_active_reservations(): void
    {
        $query = ['turnos' => $this->group->shift_id, 'zona' => $this->group->zone_id, 'vehiculo' => $this->group->vehicle_id, 'dias' => [1]];
        $response = $this->getJson(route('staffgroups.helpers', $query))->assertOk();
        $this->assertArrayNotHasKey($this->driver->id, $response->json('conductores'));
        $this->group->update(['state' => false]);
        $response = $this->getJson(route('staffgroups.helpers', $query))->assertOk();
        $this->assertArrayHasKey($this->driver->id, $response->json('conductores'));
        $this->helper->update(['status' => 'Inactivo']);
        $response = $this->getJson(route('staffgroups.helpers', $query))->assertOk();
        $this->assertArrayNotHasKey($this->helper->id, $response->json('ayudantes'));
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

    public function test_past_dates_are_rejected_but_today_is_allowed(): void
    {
        $past = $this->data(['start_date' => '2026-10-29', 'end_date' => '2026-10-30', 'days' => [4, 5]]);
        $this->postJson(route('programming.check'), $past)->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->postJson(route('programming.store'), $past)->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->generate(['start_date' => '2026-10-30', 'end_date' => '2026-10-30', 'days' => [5]])->assertJsonPath('created', 1);
        $this->assertDatabaseCount('programmings', 1);
    }

    public function test_save_rejects_a_validated_date_that_has_become_past_in_lima(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-02 23:59:00', 'America/Lima'));
        $checked = $this->checked($this->data(['end_date' => '2026-11-02']));
        $this->travel(2)->minutes();
        $this->postJson(route('programming.store'), $checked)->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->assertDatabaseCount('programmings', 0);
    }

    public function test_past_programmings_remain_searchable_but_cannot_be_resaved_with_past_dates(): void
    {
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-11-03 12:00:00', 'America/Lima'));
        $this->getJson(route('programming.index', ['start_date' => '2026-11-02', 'end_date' => '2026-11-02']))
            ->assertOk()->assertJsonCount(1, 'data');
        $past = $this->data(['programming_id' => $row->id, 'end_date' => '2026-11-02']);
        $this->putJson(route('programming.update', $row), $past)->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->assertSame('2026-11-02', $row->fresh()->date->toDateString());
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
        $this->postJson(route('programming.check'), $this->data())->assertOk()->assertJsonPath('valid', false)
            ->assertJsonPath('count', 0)->assertJsonPath('validation_token', null)->assertJsonCount(3, 'omitted_duplicates');
        $this->postJson(route('programming.store'), $this->data())->assertUnprocessable();
        $this->generate(['days' => [1, 2, 3, 5, 7]])->assertJsonPath('created', 1);
        $this->assertDatabaseCount('programmings', 4);
        $this->assertDatabaseHas('programmings', ['date' => '2026-11-03']);
    }

    public function test_repeated_generation_is_blocked_even_when_calling_save_directly(): void
    {
        $this->generate();
        try {
            app(\App\Services\ProgrammingService::class)->save($this->data(), auth()->id());
            $this->fail('No debe aceptar guardar una operación sin fechas nuevas.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertStringContainsString('No hay fechas nuevas', $exception->errors()['programming'][0]);
        }
        $this->assertDatabaseCount('programmings', 4);
        $this->assertDatabaseCount('programming_employees', 8);
        $this->assertDatabaseCount('programming_changes', 4);
    }

    public function test_empty_generation_revokes_previous_validation_and_cannot_be_saved(): void
    {
        $checked = $this->checked($this->data());
        app(\App\Services\ProgrammingService::class)->save($this->data(), auth()->id());
        $this->postJson(route('programming.check'), $this->data())->assertOk()->assertJsonPath('valid', false)
            ->assertJsonPath('count', 0)->assertJsonPath('validation_token', null);
        $this->postJson(route('programming.store'), $checked)->assertUnprocessable();
        $this->assertDatabaseCount('programmings', 4);
    }

    public function test_all_holidays_omitted_does_not_authorize_an_empty_generation(): void
    {
        Holiday::create(['name' => 'Feriado local', 'date' => '2026-11-04', 'state' => true]);
        $data = $this->data(['start_date' => '2026-11-04', 'end_date' => '2026-11-04', 'days' => [3]]);
        $this->postJson(route('programming.check'), $data)->assertOk()->assertJsonPath('valid', false)
            ->assertJsonPath('count', 0)->assertJsonPath('validation_token', null)->assertJsonPath('omitted_holidays.0', '2026-11-04');
        $this->postJson(route('programming.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('programmings', 0);
    }

    public function test_holiday_catalog_only_returns_active_dates_matching_period_and_weekdays(): void
    {
        Holiday::create(['name' => 'Feriado elegido', 'date' => '2026-11-04', 'state' => true]);
        Holiday::create(['name' => 'Otro día semanal', 'date' => '2026-11-03', 'state' => true]);
        Holiday::create(['name' => 'Feriado inactivo', 'date' => '2026-11-06', 'state' => false]);
        Holiday::create(['name' => 'Fuera del período', 'date' => '2026-12-02', 'state' => true]);
        $query = ['start_date' => '2026-11-02', 'end_date' => '2026-11-08', 'days' => [1, 3, 5, 7]];
        $this->getJson(route('programming.holidays', $query))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-11-04')->assertJsonPath('data.0.names.0', 'Feriado elegido');
        $this->getJson(route('programming.holidays', array_replace($query, ['end_date' => '2026-11-01'])))->assertUnprocessable();
        auth()->logout();
        $this->getJson(route('programming.holidays', $query))->assertUnauthorized();
    }

    public function test_selected_holiday_is_generated_and_authorization_is_preserved_without_changing_calendar(): void
    {
        $holiday = Holiday::create(['name' => 'Feriado local', 'date' => '2026-11-04', 'state' => true]);
        $this->generate(['work_holidays' => ['2026-11-04']])->assertJsonPath('created', 4)->assertJsonPath('included_holidays.0', '2026-11-04');
        $row = Programming::whereDate('date', '2026-11-04')->firstOrFail();
        $this->assertSame('Feriado local', $row->snapshot['holiday_work']['names'][0]);
        $this->assertSame(auth()->id(), $row->snapshot['holiday_work']['authorized_by']);
        $this->assertNotEmpty($row->snapshot['holiday_work']['authorized_at']);
        $this->assertTrue($holiday->fresh()->state);
        $this->getJson(route('programming.show', $row))->assertOk()->assertJsonPath('work_holidays', ['2026-11-04']);
        $this->getJson(route('programming.history', $row->id))->assertOk()
            ->assertJsonPath('data.0.after.snapshot.holiday_work.date', '2026-11-04');
        $this->postJson(route('programming.check'), $this->data(['work_holidays' => ['2026-11-04']]))
            ->assertOk()->assertJsonPath('valid', false)->assertJsonPath('count', 0)->assertJsonPath('validation_token', null);
        $this->assertDatabaseCount('programmings', 4);
    }

    public function test_omitted_holiday_can_be_added_later_without_duplicating_other_dates(): void
    {
        Holiday::create(['name' => 'Feriado local', 'date' => '2026-11-04', 'state' => true]);
        $this->generate()->assertJsonPath('created', 3)->assertJsonPath('omitted_holidays.0', '2026-11-04');
        $this->generate(['work_holidays' => ['2026-11-04']])->assertJsonPath('created', 1)->assertJsonCount(3, 'omitted_duplicates');
        $this->assertDatabaseCount('programmings', 4);
    }

    public function test_holiday_exceptions_reject_invalid_inactive_outside_or_unselected_dates(): void
    {
        Holiday::create(['name' => 'Activo', 'date' => '2026-11-04', 'state' => true]);
        Holiday::create(['name' => 'Inactivo', 'date' => '2026-11-06', 'state' => false]);
        Holiday::create(['name' => 'Otro día semanal', 'date' => '2026-11-03', 'state' => true]);
        Holiday::create(['name' => 'Fuera del período', 'date' => '2026-11-09', 'state' => true]);
        foreach ([['2026-11-02'], ['2026-11-03'], ['2026-11-06'], ['2026-11-09'], ['2026-11-04', '2026-11-04'], ['malformado']] as $dates) {
            $this->postJson(route('programming.check'), $this->data(['work_holidays' => $dates]))->assertUnprocessable();
        }
        $this->assertDatabaseCount('programmings', 0);
    }

    public function test_working_a_holiday_still_checks_vacations_contracts_and_occupied_staff(): void
    {
        Holiday::create(['name' => 'Feriado local', 'date' => '2026-11-04', 'state' => true]);
        $data = $this->data(['start_date' => '2026-11-04', 'end_date' => '2026-11-04', 'days' => [3], 'work_holidays' => ['2026-11-04']]);
        foreach ([$this->driver, $this->helper] as $person) {
            $vacation = Vacation::create(['employee_id' => $person->id, 'year' => 2026, 'start_date' => '2026-11-04', 'end_date' => '2026-11-04', 'scheduled_days' => 1, 'pending_days' => 29, 'status' => 'Aprobada']);
            $this->postJson(route('programming.check'), $data)->assertOk()->assertJsonPath('valid', false)
                ->assertJsonPath('issues.0.reason', 'Vacaciones aprobadas');
            $vacation->delete();
        }
        $this->helper->contracts()->update(['end_date' => '2026-11-03']);
        $this->postJson(route('programming.check'), $data)->assertOk()->assertJsonPath('valid', false)->assertJsonPath('issues.0.reason', 'Sin contrato vigente');
        $this->helper->contracts()->update(['end_date' => null]);
        $this->postJson(route('programming.store'), $this->checked($data))->assertCreated();
        $other = $this->group->replicate(); $other->name = 'Otro grupo'; $other->save();
        $this->postJson(route('programming.check'), array_replace($data, ['staff_group_id' => $other->id]))->assertOk()->assertJsonPath('valid', false);
    }

    public function test_changing_holiday_choices_requires_revalidation_and_calendar_is_rechecked_on_save(): void
    {
        $holiday = Holiday::create(['name' => 'Feriado local', 'date' => '2026-11-04', 'state' => true]);
        $checked = $this->checked($this->data());
        $this->postJson(route('programming.store'), array_replace($checked, ['work_holidays' => ['2026-11-04']]))->assertUnprocessable();
        $checked = $this->checked($this->data(['work_holidays' => ['2026-11-04']]));
        $holiday->update(['state' => false]);
        $this->postJson(route('programming.store'), $checked)->assertUnprocessable();
        $this->assertDatabaseCount('programmings', 0);
    }

    public function test_holiday_programming_can_be_edited_with_its_exception_and_audited(): void
    {
        Holiday::create(['name' => 'Feriado local', 'date' => '2026-11-04', 'state' => true]);
        $data = $this->data(['start_date' => '2026-11-04', 'end_date' => '2026-11-04', 'days' => [3], 'work_holidays' => ['2026-11-04']]);
        $this->postJson(route('programming.store'), $this->checked($data))->assertCreated();
        $row = Programming::firstOrFail();
        $edit = array_replace($data, ['programming_id' => $row->id, 'notes' => 'Servicio autorizado']);
        $this->putJson(route('programming.update', $row), $this->checked($edit))->assertOk();
        $this->assertSame('Servicio autorizado', $row->fresh()->notes);
        $this->assertSame('2026-11-04', $row->fresh()->snapshot['holiday_work']['date']);
        $this->assertDatabaseCount('programming_changes', 2);
    }

    public function test_moving_a_route_to_a_holiday_requires_explicit_selection_and_updates_its_reservations(): void
    {
        Holiday::create(['name' => 'Todos los Santos', 'date' => '2026-11-01', 'state' => true]);
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $edit = $this->data(['programming_id' => $row->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7]]);
        $this->getJson(route('programming.holidays', ['start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7]]))
            ->assertOk()->assertJsonPath('data.0.date', '2026-11-01');
        $this->postJson(route('programming.check'), $edit)->assertOk()->assertJsonPath('valid', false)
            ->assertJsonPath('validation_token', null)->assertJsonPath('omitted_holidays', ['2026-11-01']);
        $this->putJson(route('programming.update', $row), $edit)->assertUnprocessable();
        $this->assertSame('2026-11-02', $row->fresh()->date->toDateString());
        $this->putJson(route('programming.update', $row), $this->checked(array_replace($edit, ['work_holidays' => ['2026-11-01']])))->assertOk();
        $this->assertSame('2026-11-01', $row->fresh()->date->toDateString());
        $this->assertSame('2026-11-01', $row->fresh()->snapshot['holiday_work']['date']);
        $this->assertDatabaseMissing('programming_employees', ['date' => '2026-11-02']);
        $this->assertDatabaseCount('programmings', 1);
        $this->assertDatabaseCount('programming_changes', 2);
        $this->assertDatabaseHas('programming_employees', ['programming_id' => $row->id, 'employee_id' => $this->driver->id, 'date' => '2026-11-01']);
    }

    public function test_editing_a_holiday_rejects_existing_group_date_and_keeps_original_route(): void
    {
        Holiday::create(['name' => 'Todos los Santos', 'date' => '2026-11-01', 'state' => true]);
        $this->generate(['start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'days' => [7, 1], 'work_holidays' => ['2026-11-01']]);
        $row = Programming::whereDate('date', '2026-11-02')->firstOrFail();
        $edit = $this->data(['programming_id' => $row->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7], 'work_holidays' => ['2026-11-01']]);
        $this->postJson(route('programming.check'), $edit)->assertOk()->assertJsonPath('valid', false)
            ->assertJsonPath('omitted_duplicates', ['2026-11-01'])->assertJsonPath('validation_token', null);
        $this->putJson(route('programming.update', $row), $edit)->assertUnprocessable();
        $this->assertSame('2026-11-02', $row->fresh()->date->toDateString());
        $this->assertDatabaseCount('programmings', 2);
    }

    public function test_moving_to_a_holiday_checks_personnel_and_vehicle_conflicts_and_rechecks_on_save(): void
    {
        Holiday::create(['name' => 'Todos los Santos', 'date' => '2026-11-01', 'state' => true]);
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $edit = $this->data(['programming_id' => $row->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7], 'work_holidays' => ['2026-11-01']]);
        $checked = $this->checked($edit);
        $other = $this->group->replicate(); $other->name = 'Otro recorrido feriado'; $other->save();
        app(\App\Services\ProgrammingService::class)->save(array_replace($edit, ['programming_id' => null, 'staff_group_id' => $other->id]), auth()->id());
        $this->putJson(route('programming.update', $row), $checked)->assertUnprocessable();
        $check = $this->postJson(route('programming.check'), $edit)->assertOk()->assertJsonPath('valid', false);
        $issues = collect($check->json('issues'));
        $this->assertTrue($issues->contains('employee_id', $this->driver->id));
        $this->assertTrue($issues->contains('employee_id', $this->helper->id));
        $this->assertTrue($issues->contains('reason', 'Vehículo asignado en la misma fecha y turno'));
        $this->assertSame('2026-11-02', $row->fresh()->date->toDateString());
        $this->assertDatabaseCount('programmings', 2);
    }

    public function test_edit_detects_already_authorized_holiday_before_checkbox_is_selected(): void
    {
        Holiday::create(['name' => 'Todos los Santos', 'date' => '2026-11-01', 'state' => true]);
        $this->generate(['start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'days' => [7, 1], 'work_holidays' => ['2026-11-01']]);
        $row = Programming::whereDate('date', '2026-11-02')->firstOrFail();
        $existing = Programming::whereDate('date', '2026-11-01')->firstOrFail();
        $edit = $this->data(['programming_id' => $row->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7]]);
        foreach ([[], ['2026-11-01']] as $selection) {
            $this->postJson(route('programming.check'), array_replace($edit, ['work_holidays' => $selection]))
                ->assertOk()->assertJsonPath('valid', false)->assertJsonPath('validation_token', null)
                ->assertJsonPath('omitted_duplicates', ['2026-11-01'])
                ->assertJsonPath('issues.0.routes.0.id', $existing->id)
                ->assertJsonPath('issues.0.routes.0.holiday_authorized', true)
                ->assertJsonPath('issues.0.routes.0.authorized_by', auth()->user()->name);
        }
        $this->assertSame('2026-11-02', $row->fresh()->date->toDateString());
        $this->assertDatabaseCount('programmings', 2);
    }

    public function test_edit_detects_another_routes_resources_even_when_holiday_is_unchecked(): void
    {
        Holiday::create(['name' => 'Todos los Santos', 'date' => '2026-11-01', 'state' => true]);
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $other = $this->group->replicate(); $other->name = 'Otro recorrido autorizado'; $other->save();
        $this->generate(['staff_group_id' => $other->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7], 'work_holidays' => ['2026-11-01']]);
        $edit = $this->data(['programming_id' => $row->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7]]);
        $check = $this->postJson(route('programming.check'), $edit)->assertOk()->assertJsonPath('valid', false)
            ->assertJsonPath('issues.0.routes.0.group', 'Otro recorrido autorizado')
            ->assertJsonPath('issues.0.routes.0.holiday_authorized', true);
        $issues = collect($check->json('issues'));
        $this->assertTrue($issues->contains('employee_id', $this->driver->id));
        $this->assertTrue($issues->contains('employee_id', $this->helper->id));
        $this->assertTrue($issues->contains('reason', 'Vehículo asignado en la misma fecha y turno'));
    }

    public function test_another_holiday_route_with_different_resources_does_not_enable_or_block_this_route(): void
    {
        Holiday::create(['name' => 'Todos los Santos', 'date' => '2026-11-01', 'state' => true]);
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $driver = $this->employee(1); $helper = $this->employee(2);
        $vehicle = $this->group->vehicle->replicate(); $vehicle->plate = 'HOL-001'; $vehicle->code = 'HOL-1'; $vehicle->save();
        $other = $this->group->replicate(); $other->name = 'Recursos diferentes'; $other->vehicle_id = $vehicle->id; $other->driver_id = $driver->id; $other->save();
        $this->generate(['staff_group_id' => $other->id, 'driver_id' => $driver->id, 'helpers' => [$helper->id], 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7], 'work_holidays' => ['2026-11-01']]);
        $edit = $this->data(['programming_id' => $row->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [7]]);
        $this->postJson(route('programming.check'), $edit)->assertOk()->assertJsonPath('valid', false)
            ->assertJsonCount(0, 'issues')->assertJsonPath('omitted_holidays', ['2026-11-01']);
        $this->putJson(route('programming.update', $row), $this->checked(array_replace($edit, ['work_holidays' => ['2026-11-01']])))->assertOk();
        $this->assertSame('2026-11-01', $row->fresh()->date->toDateString());
    }

    public function test_editing_rejects_a_weekday_that_does_not_match_the_new_date(): void
    {
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        $this->postJson(route('programming.check'), $this->data(['programming_id' => $row->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'days' => [1]]))
            ->assertUnprocessable()->assertJsonValidationErrors('programming');
        $this->assertSame('2026-11-02', $row->fresh()->date->toDateString());
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

    public function test_approved_vacations_block_both_roles_including_start_and_end_dates(): void
    {
        foreach ([$this->driver, $this->helper] as $person) {
            $vacation = Vacation::create(['employee_id' => $person->id, 'year' => 2026, 'start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'scheduled_days' => 3, 'pending_days' => 27, 'status' => 'Aprobada']);
            $check = $this->postJson(route('programming.check'), $this->data())
                ->assertOk()->assertJsonPath('valid', false)->assertJsonPath('validation_token', null)
                ->assertJsonPath('issues.0.employee_id', $person->id)
                ->assertJsonPath('issues.0.reason', 'Vacaciones aprobadas')
                ->assertJsonPath('issues.0.dates', ['2026-11-02', '2026-11-04']);
            $this->assertNotEmpty($check->json('issues.0.person'));
            $this->postJson(route('programming.store'), $this->data())->assertUnprocessable();
            $vacation->delete();
        }
        $this->assertDatabaseCount('programmings', 0);
        $this->assertDatabaseCount('programming_employees', 0);
    }

    public function test_unapproved_or_outside_vacations_do_not_block_programming(): void
    {
        foreach ([['Pendiente', '2026-11-02', '2026-11-08'], ['Rechazada', '2026-11-02', '2026-11-08'], ['Aprobada', '2026-11-09', '2026-11-15'], ['Aprobada', '2026-10-26', '2026-11-01']] as [$status, $start, $end]) {
            $vacation = Vacation::create(['employee_id' => $this->driver->id, 'year' => 2026, 'start_date' => $start, 'end_date' => $end, 'scheduled_days' => 7, 'pending_days' => 23, 'status' => $status]);
            $this->postJson(route('programming.check'), $this->data())->assertOk()->assertJsonPath('valid', true)->assertJsonCount(0, 'issues');
            $vacation->delete();
        }
    }

    public function test_edit_save_rechecks_vacations_and_preserves_existing_assignments(): void
    {
        $this->generate(['end_date' => '2026-11-02']);
        $row = Programming::firstOrFail();
        foreach ([$this->driver, $this->helper] as $person) {
            $checked = $this->checked($this->data(['programming_id' => $row->id, 'end_date' => '2026-11-02', 'notes' => 'No debe guardarse']));
            $vacation = Vacation::create(['employee_id' => $person->id, 'year' => 2026, 'start_date' => '2026-11-02', 'end_date' => '2026-11-02', 'scheduled_days' => 1, 'pending_days' => 29, 'status' => 'Aprobada']);
            $this->putJson(route('programming.update', $row), $checked)->assertUnprocessable();
            $this->assertSame('Recorrido real', $row->fresh()->notes);
            $this->assertSame(2, $row->employees()->count());
            $this->postJson(route('programming.check'), $this->data(['programming_id' => $row->id, 'end_date' => '2026-11-02']))
                ->assertOk()->assertJsonPath('valid', false)->assertJsonPath('issues.0.employee_id', $person->id);
            $vacation->delete();
        }
        $this->assertDatabaseCount('programmings', 1);
        $this->assertDatabaseCount('programming_changes', 1);
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
