<?php

namespace Tests\Feature;

use App\Models\admin\Contract;
use App\Models\admin\Vacation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class VacationsCrudTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeId;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('VacationsCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 6, 1)->startOfDay());
        $this->actingAs(User::factory()->create());

        $typeId = DB::table('employee_types')->insertGetId([
            'name' => 'Conductor',
            'description' => 'Tipo de prueba',
        ]);
        $this->employeeId = DB::table('employees')->insertGetId([
            'employee_type_id' => $typeId,
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'birthdate' => '1990-01-01',
            'email' => 'ana.vacaciones@example.test',
            'password' => bcrypt('test-password'),
            'status' => 'Activo',
        ]);
        $this->contract();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_only_active_employees_with_current_permanent_or_named_contract_can_register(): void
    {
        DB::table('employees')->where('id', $this->employeeId)->update(['status' => 'Inactivo']);
        $this->postJson(route('vacations.store'), $this->data())
            ->assertUnprocessable()->assertJsonValidationErrors('employee_id');

        DB::table('employees')->where('id', $this->employeeId)->update(['status' => 'Activo']);
        Contract::query()->delete();
        $this->contract(['type' => 'Temporal', 'end_date' => '2026-09-01']);
        $this->postJson(route('vacations.store'), $this->data())
            ->assertUnprocessable()->assertJsonValidationErrors('employee_id');

        Contract::query()->delete();
        $cancelled = $this->contract();
        $cancelled->forceFill(['cancelled_at' => now(), 'is_active' => false])->save();
        $this->postJson(route('vacations.store'), $this->data())
            ->assertUnprocessable()->assertJsonValidationErrors('employee_id');

        Contract::query()->delete();
        $this->contract(['type' => 'Nombrado']);
        $this->postJson(route('vacations.store'), $this->data())->assertCreated();
        $this->assertDatabaseCount('vacations', 1);
    }

    public function test_contract_must_be_current_and_cover_the_complete_period(): void
    {
        Contract::query()->delete();
        $this->contract(['type' => 'Nombrado', 'end_date' => '2026-07-05']);

        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]))->assertUnprocessable()->assertJsonValidationErrors('employee_id');

        Contract::query()->delete();
        $this->contract(['start_date' => '2026-07-01']);
        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]))->assertUnprocessable()->assertJsonValidationErrors('employee_id');
    }

    public function test_fractioned_periods_count_calendar_days_and_keep_one_annual_balance(): void
    {
        $first = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-03',
            'end_date' => '2026-07-06',
        ]))->assertCreated()->assertJsonPath('data.scheduled_days', 4)->assertJsonPath('data.balance', 30);
        $this->postJson(route('vacations.approve', $first->json('data.id')))
            ->assertOk()->assertJsonPath('data.balance', 26);

        $second = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-15',
        ]))->assertCreated()->assertJsonPath('data.scheduled_days', 6)->assertJsonPath('data.balance', 26);
        $this->postJson(route('vacations.approve', $second->json('data.id')))
            ->assertOk()->assertJsonPath('data.balance', 20);

        $this->assertSame([20, 20], Vacation::orderBy('id')->pluck('pending_days')->all());
        $this->assertSame([4, 6], Vacation::orderBy('id')->pluck('scheduled_days')->all());
    }

    public function test_limit_overlap_and_cross_year_periods_are_rejected(): void
    {
        $first = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-20',
        ]))->assertCreated();

        $this->postJson(route('vacations.approve', $first->json('data.id')))->assertOk();

        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-11',
        ]))->assertUnprocessable()->assertJsonValidationErrors('end_date');

        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-22',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-12-30',
            'end_date' => '2027-01-02',
        ]))->assertUnprocessable()->assertJsonValidationErrors('end_date');

        $exact = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-10',
        ]))->assertCreated();

        $this->postJson(route('vacations.approve', $exact->json('data.id')))
            ->assertOk()->assertJsonPath('data.balance', 0);

        $this->getJson(route('vacations.balance', [
            'employee_id' => $this->employeeId,
            'year' => 2026,
        ]))->assertOk()->assertJsonPath('available_days', 0);

        $this->assertDatabaseCount('vacations', 2);
    }
    public function test_historical_days_count_but_do_not_block_dates_and_regularization_does_not_duplicate_them(): void
    {
        $historical = Vacation::create([
            'employee_id' => $this->employeeId,
            'year' => 2026,
            'scheduled_days' => 10,
            'pending_days' => 10,
            'status' => 'Aprobada',
        ]);

        $new = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
        ]))->assertCreated()->assertJsonPath('data.balance', 20);
        $this->postJson(route('vacations.approve', $new->json('data.id')))
            ->assertOk()->assertJsonPath('data.balance', 15);
        $this->assertSame([15, 15], Vacation::orderBy('id')->pluck('pending_days')->all());

        $listing = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vacations.index', ['draw' => 1, 'start' => 0, 'length' => 10]));
        $this->assertStringContainsString(
            'Aprobada',
            collect($listing->json('data'))->pluck('status')->implode(' '),
        );
        $this->assertContains('Sin fechas', collect($listing->json('data'))->pluck('period')->all());

        $this->putJson(route('vacations.update', $historical), $this->data([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-09',
        ]))->assertUnprocessable()->assertJsonValidationErrors('end_date');

        $this->putJson(route('vacations.update', $historical), $this->data([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-10',
        ]))->assertOk()->assertJsonPath('data.scheduled_days', 10)->assertJsonPath('data.balance', 15);

        $this->assertSame(15, Vacation::sum('scheduled_days'));
        $this->assertSame('2026-08-01', $historical->fresh()->start_date->toDateString());
    }

    public function test_historical_regularization_uses_contract_validity_at_the_requested_period(): void
    {
        DB::table('employees')->where('id', $this->employeeId)->update(['status' => 'Inactivo']);
        $contract = Contract::firstOrFail();
        $contract->forceFill(['cancelled_at' => '2026-04-15 12:00:00', 'is_active' => false])->save();

        $covered = $this->historicalVacation(10);
        $this->putJson(route('vacations.update', $covered), $this->data([
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-10',
        ]))->assertOk()->assertJsonPath('data.scheduled_days', 10);

        $afterCancellation = $this->historicalVacation(10);
        $this->putJson(route('vacations.update', $afterCancellation), $this->data([
            'start_date' => '2026-04-16',
            'end_date' => '2026-04-25',
        ]))->assertUnprocessable()->assertJsonValidationErrors('employee_id');
        $this->assertTrue($afterCancellation->fresh()->isHistorical());

        $contract->delete();
        $this->contract([
            'type' => 'Nombrado',
            'end_date' => '2026-05-05',
            'is_active' => false,
        ]);
        $afterContractEnd = $this->historicalVacation(10);
        $this->putJson(route('vacations.update', $afterContractEnd), $this->data([
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-10',
        ]))->assertUnprocessable()->assertJsonValidationErrors('employee_id');
        $this->assertTrue($afterContractEnd->fresh()->isHistorical());
    }

    public function test_historical_regularization_keeps_year_and_scheduled_days_and_cannot_be_deleted_first(): void
    {
        $historical = $this->historicalVacation(10);

        $this->putJson(route('vacations.update', $historical), $this->data([
            'year' => 2027,
            'start_date' => '2027-07-01',
            'end_date' => '2027-07-10',
        ]))->assertUnprocessable()->assertJsonValidationErrors('year');

        $this->deleteJson(route('vacations.destroy', $historical), ['confirmed' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vacation')
            ->assertJsonPath(
                'errors.vacation.0',
                'El registro histórico no se puede eliminar directamente. Primero debe regularizarlo asignándole sus fechas.',
            );

        $this->assertDatabaseHas('vacations', [
            'id' => $historical->id,
            'year' => 2026,
            'scheduled_days' => 10,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function test_new_request_is_pending_and_rejection_requires_a_reason(): void
    {
        $response = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
        ]))->assertCreated()
            ->assertJsonPath('data.status', 'Pendiente')
            ->assertJsonPath('data.balance', 30);
        $vacation = Vacation::findOrFail($response->json('data.id'));

        $this->postJson(route('vacations.reject', $vacation), [])
            ->assertUnprocessable()->assertJsonValidationErrors('rejection_reason');
        $this->postJson(route('vacations.reject', $vacation), ['rejection_reason' => 'No corresponde al cronograma autorizado.'])
            ->assertOk()->assertJsonPath('data.status', 'Rechazada')->assertJsonPath('data.balance', 30);

        $this->postJson(route('vacations.approve', $vacation))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->postJson(route('vacations.reject', $vacation), ['rejection_reason' => 'Otro motivo'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->get(route('vacations.show', $vacation->fresh()))
            ->assertOk()->assertSee('No corresponde al cronograma autorizado.');
    }

    public function test_pending_requests_reserve_balance_and_block_overlapping_periods(): void
    {
        $response = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]))->assertCreated()->assertJsonPath('data.balance', 30);

        $vacation = Vacation::findOrFail($response->json('data.id'));

        $this->getJson(route('vacations.check', [
            'employee_id' => $this->employeeId,
            'date' => '2026-12-03',
        ]))->assertOk()->assertJsonPath('on_vacation', false);

        $this->getJson(route('vacations.balance', [
            'employee_id' => $this->employeeId,
            'year' => 2026,
        ]))->assertOk()->assertJsonPath('available_days', 25);

        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-12-03',
            'end_date' => '2026-12-07',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $this->postJson(route('vacations.approve', $vacation))
            ->assertOk()
            ->assertJsonPath('data.status', 'Aprobada')
            ->assertJsonPath('data.balance', 25);

        $this->getJson(route('vacations.balance', [
            'employee_id' => $this->employeeId,
            'year' => 2026,
        ]))->assertOk()->assertJsonPath('available_days', 25);

        $this->getJson(route('vacations.check', [
            'employee_id' => $this->employeeId,
            'date' => '2026-12-03',
        ]))->assertOk()->assertJsonPath('on_vacation', true);
    }

    public function test_rejected_requests_release_reserved_balance(): void
    {
        $response = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]))->assertCreated();

        $vacation = Vacation::findOrFail($response->json('data.id'));

        $this->getJson(route('vacations.balance', [
            'employee_id' => $this->employeeId,
            'year' => 2026,
        ]))->assertOk()->assertJsonPath('available_days', 25);

        $this->postJson(route('vacations.reject', $vacation), [
            'rejection_reason' => 'No corresponde al cronograma autorizado.',
        ])->assertOk()->assertJsonPath('data.status', 'Rechazada');

        $this->getJson(route('vacations.balance', [
            'employee_id' => $this->employeeId,
            'year' => 2026,
        ]))->assertOk()->assertJsonPath('available_days', 30);
    }
    public function test_ordinary_new_requests_cannot_start_in_the_past(): void
    {
        $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-03',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');
    }

    public function test_crud_listing_modals_and_accidental_delete_protection(): void
    {
        $this->get(route('vacations.index'))
            ->assertOk()
            ->assertSee('Gestión de Vacaciones')
            ->assertSee('Nueva vacación');
        $this->get(route('vacations.create'))
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('Permanente')
            ->assertSee('data-error-for="start_date"', false)
            ->assertSee('data-error-for="end_date"', false);

        $new = $this->postJson(route('vacations.store'), $this->data())->assertCreated();
        $vacation = Vacation::first();

        $listing = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vacations.index', ['draw' => 1, 'start' => 0, 'length' => 10]));
        $listing->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.dni', '12345678')
            ->assertJsonPath('data.0.scheduled_days', 10)
            ->assertJsonPath('data.0.balance', 30)
            ->assertJsonPath('data.0.status', '<span class="badge text-bg-warning">Pendiente</span>')
            ->assertJsonPath('data.0.period', '01/07/2026 - 10/07/2026');
        $actions = (string) $listing->json('data.0.actions');
        $this->assertStringContainsString('btn btn-outline-primary btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('btn btn-outline-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('btn btn-outline-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('btnAprobar', $actions);
        $this->assertStringContainsString('btnRechazar', $actions);

        $this->get(route('vacations.show', $vacation))->assertOk()->assertSee('Detalle de la vacación');
        $this->get(route('vacations.edit', $vacation))->assertOk()->assertSee('Editar vacación');

        $this->putJson(route('vacations.update', $vacation), $this->data([
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
        ]))->assertOk()->assertJsonPath('data.balance', 30);

        $this->postJson(route('vacations.approve', $vacation))
            ->assertOk()->assertJsonPath('data.balance', 25);
        $this->deleteJson(route('vacations.destroy', $vacation), ['confirmed' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('vacations', ['id' => $vacation->id, 'status' => 'Aprobada']);

        $pending = $this->postJson(route('vacations.store'), $this->data([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ]))->assertCreated();
        $pendingVacation = Vacation::findOrFail($pending->json('data.id'));
        $this->deleteJson(route('vacations.destroy', $pendingVacation), ['confirmed' => true])->assertOk();
        $this->assertDatabaseHas('vacations', ['id' => $vacation->id, 'status' => 'Aprobada']);
        $this->assertDatabaseMissing('vacations', ['id' => $pendingVacation->id]);
    }

    public function test_availability_endpoint_ignores_historical_rows_and_finds_dated_periods(): void
    {
        Vacation::create([
            'employee_id' => $this->employeeId,
            'year' => 2026,
            'scheduled_days' => 5,
            'pending_days' => 25,
            'status' => 'Aprobada',
        ]);
        $new = $this->postJson(route('vacations.store'), $this->data())->assertCreated();
        $this->postJson(route('vacations.approve', $new->json('data.id')))->assertOk();

        $this->getJson(route('vacations.check', ['employee_id' => $this->employeeId, 'date' => '2026-07-05']))
            ->assertOk()->assertJsonPath('on_vacation', true)
            ->assertJsonPath('vacation.start_date', '2026-07-01');
        $this->getJson(route('vacations.check', ['employee_id' => $this->employeeId, 'date' => '2026-08-01']))
            ->assertOk()->assertJsonPath('on_vacation', false)
            ->assertJsonPath('vacation', null);
        $this->assertTrue(Vacation::employeeIsOnVacation($this->employeeId, '2026-07-10'));
        $this->assertFalse(Vacation::employeeIsOnVacation($this->employeeId, '2026-07-11'));
    }

    public function test_balance_endpoint_only_excludes_a_vacation_from_the_same_employee_and_year(): void
    {
        $this->postJson(route('vacations.store'), $this->data())->assertCreated();
        $vacation = Vacation::firstOrFail();

        $this->getJson(route('vacations.balance', [
            'employee_id' => $this->employeeId,
            'year' => 2026,
            'excluding_id' => $vacation->id,
        ]))->assertOk()->assertJsonPath('available_days', 30);

        $this->getJson(route('vacations.balance', [
            'employee_id' => $this->employeeId,
            'year' => 2027,
            'excluding_id' => $vacation->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('excluding_id');
    }

    public function test_vacation_routes_require_authentication(): void
    {
        $vacation = Vacation::create([
            'employee_id' => $this->employeeId,
            'year' => 2026,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
            'scheduled_days' => 10,
            'pending_days' => 20,
            'status' => 'Aprobada',
        ]);
        auth()->logout();

        $this->getJson(route('vacations.index'))->assertUnauthorized();
        $this->postJson(route('vacations.store'), $this->data())->assertUnauthorized();
        $this->putJson(route('vacations.update', $vacation), $this->data())->assertUnauthorized();
        $this->deleteJson(route('vacations.destroy', $vacation), ['confirmed' => true])->assertUnauthorized();
        $this->postJson(route('vacations.approve', $vacation))->assertUnauthorized();
        $this->postJson(route('vacations.reject', $vacation), ['rejection_reason' => 'Motivo'])
            ->assertUnauthorized();
        $this->getJson(route('vacations.check', ['employee_id' => $this->employeeId, 'date' => '2026-07-01']))
            ->assertUnauthorized();
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

    private function contract(array $overrides = []): Contract
    {
        return Contract::create(array_merge([
            'employee_id' => $this->employeeId,
            'type' => 'Permanente',
            'start_date' => '2025-01-01',
            'end_date' => null,
            'salary' => '1500.00',
            'is_active' => true,
        ], $overrides));
    }

    private function historicalVacation(int $scheduledDays): Vacation
    {
        return Vacation::create([
            'employee_id' => $this->employeeId,
            'year' => 2026,
            'scheduled_days' => $scheduledDays,
            'pending_days' => $scheduledDays,
            'status' => 'Aprobada',
        ]);
    }
}
