<?php

namespace Tests\Feature;

use App\Models\admin\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ContractsCrudTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeId;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('ContractsCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 12)->startOfDay());
        $this->actingAs(User::factory()->create());
        $typeId = DB::table('employee_types')->insertGetId(['name' => 'Conductor']);
        $this->employeeId = DB::table('employees')->insertGetId([
            'employee_type_id' => $typeId, 'dni' => '12345678', 'first_name' => 'Ana',
            'last_name' => 'Perez', 'birthdate' => '1990-01-01', 'email' => 'ana@example.test',
            'password' => bcrypt('test-password'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->employeeId, 'type' => 'Temporal',
            'start_date' => '2026-10-12', 'end_date' => '2026-12-12', 'salary' => '1500.00',
        ], $overrides);
    }

    private function previous(array $overrides = []): Contract
    {
        return Contract::create($this->data(array_merge([
            'start_date' => '2026-06-01', 'end_date' => '2026-08-12',
        ], $overrides)));
    }

    public static function types(): array
    {
        return [['Permanente'], ['Nombrado'], ['Temporal']];
    }

    #[DataProvider('types')]
    public function test_first_contract_supports_every_type(string $type): void
    {
        $data = $this->data(['type' => $type, 'end_date' => $type === 'Temporal' ? '2026-12-12' : null]);
        $this->postJson(route('contracts.store'), $data)->assertCreated();
        $saved = Contract::first();
        $this->assertSame($type, $saved->type);
        $this->assertSame($data['start_date'], $saved->start_date->toDateString());
        $this->assertSame($data['end_date'], $saved->end_date?->toDateString());
        $this->assertSame('1500.00', $saved->salary);
        $this->assertSame($type !== 'Temporal', $saved->allowsVacations());
    }

    public function test_temporal_requires_end_date_and_chronological_dates(): void
    {
        $this->postJson(route('contracts.store'), $this->data(['end_date' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->postJson(route('contracts.store'), $this->data(['end_date' => '2026-10-11']))
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->assertDatabaseCount('contracts', 0);
    }

    #[DataProvider('indefiniteTypes')]
    public function test_indefinite_contracts_reject_end_dates_and_another_contract(string $type): void
    {
        $this->postJson(route('contracts.store'), $this->data(['type' => $type]))
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->previous(['type' => $type, 'end_date' => null, 'is_active' => false]);
        $this->postJson(route('contracts.store'), $this->data())
            ->assertUnprocessable()->assertJsonValidationErrors('employee_id');
        $this->assertDatabaseCount('contracts', 1);
    }

    public static function indefiniteTypes(): array
    {
        return [['Permanente'], ['Nombrado']];
    }

    public function test_registration_is_blocked_until_two_months_even_with_a_future_start(): void
    {
        $this->previous(['end_date' => '2026-08-13']);
        $this->postJson(route('contracts.store'), $this->data([
            'start_date' => '2026-11-01', 'end_date' => '2026-12-01',
        ]))->assertUnprocessable()->assertJsonValidationErrors('employee_id');
        $this->assertDatabaseCount('contracts', 1);
    }

    #[DataProvider('types')]
    public function test_all_types_are_available_at_the_exact_two_month_boundary(string $type): void
    {
        $this->previous();
        $this->postJson(route('contracts.store'), $this->data([
            'type' => $type, 'end_date' => $type === 'Temporal' ? '2026-12-12' : null,
        ]))->assertCreated();
        $this->assertDatabaseCount('contracts', 2);
    }

    public function test_backdating_cannot_bypass_the_gap(): void
    {
        $this->previous();
        $this->postJson(route('contracts.store'), $this->data(['start_date' => '2026-10-11']))
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->postJson(route('contracts.store'), $this->data(['start_date' => '2026-01-01', 'end_date' => '2026-02-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');
    }

    public function test_month_end_uses_calendar_months_without_overflow(): void
    {
        $this->travelTo(now()->setDate(2026, 2, 28)->startOfDay());
        $this->previous(['start_date' => '2025-10-01', 'end_date' => '2025-12-31']);
        $this->postJson(route('contracts.store'), $this->data([
            'start_date' => '2026-02-28', 'end_date' => '2026-05-28',
        ]))->assertCreated();
    }

    public function test_edit_checks_both_preceding_and_following_contracts(): void
    {
        $first = $this->previous();
        $this->postJson(route('contracts.store'), $this->data())->assertCreated();
        $second = Contract::orderByDesc('id')->first();

        $this->putJson(route('contracts.update', $second), $this->data(['start_date' => '2026-10-11']))
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->putJson(route('contracts.update', $first), $this->data([
            'start_date' => '2026-06-01', 'end_date' => '2026-08-13',
        ]))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->putJson(route('contracts.update', $first), $this->data([
            'type' => 'Permanente', 'start_date' => '2026-06-01', 'end_date' => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->assertSame('2026-08-12', $first->fresh()->end_date->toDateString());
    }

    public function test_edit_same_contract_and_salary_does_not_count_as_rehiring(): void
    {
        $contract = $this->previous(['type' => 'Nombrado', 'end_date' => null]);
        $this->putJson(route('contracts.update', $contract), $this->data([
            'type' => 'Nombrado', 'start_date' => '2026-06-01', 'end_date' => null, 'salary' => '1700.50',
        ]))->assertOk();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'salary' => '1700.50']);
    }

    public function test_vacation_records_prevent_conversion_to_temporal(): void
    {
        $contract = $this->previous(['type' => 'Nombrado', 'end_date' => null]);
        DB::table('vacations')->insert(['employee_id' => $this->employeeId, 'year' => 2026, 'scheduled_days' => 30, 'pending_days' => 30]);
        $this->putJson(route('contracts.update', $contract), $this->data())
            ->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_list_search_order_pagination_and_form_rendering(): void
    {
        $this->previous();
        $this->get(route('contracts.index'))->assertOk()->assertSee('Listado de Contratos');
        $this->get(route('contracts.create'))->assertOk()->assertSee('12345678 - Ana Perez')->assertDontSee('trial_period_months');
        $this->getJson(route('contracts.edit', Contract::first()))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->get(route('contracts.edit', $this->previous(['start_date' => '2026-10-12', 'end_date' => '2026-12-12'])))
            ->assertOk()->assertSee('2026-10-12');
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get(route('contracts.index', [
            'draw' => 1, 'start' => 0, 'length' => 1, 'search' => ['value' => '12345678'],
            'columns' => [['data' => 'dni', 'name' => 'employees.dni', 'searchable' => 'true', 'orderable' => 'true']],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]));
        $response->assertOk()->assertJsonPath('recordsFiltered', 2)->assertJsonPath('data.0.employee', 'Ana Perez')
            ->assertJsonPath('data.0.next_hiring_date', '12/10/2026');
    }

    public function test_contract_can_be_viewed_in_read_only_mode_before_and_after_cancellation(): void
    {
        $contract = Contract::create($this->data());

        $this->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('Detalle del contrato')
            ->assertSee('Información de solo lectura')
            ->assertSee('Ana Perez')
            ->assertSee('12345678')
            ->assertSee('Temporal')
            ->assertSee('S/ 1,500.00')
            ->assertSee('12/10/2026')
            ->assertSee('12/12/2026')
            ->assertSee('Vigente')
            ->assertDontSee('Fecha de anulación')
            ->assertDontSee('<form', false)
            ->assertDontSee('<input', false)
            ->assertDontSee('<select', false);

        $this->patchJson(route('contracts.cancel', $contract))->assertOk();

        $this->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('Anulado')
            ->assertSee('Fecha de anulación')
            ->assertSee('12/10/2026 00:00');

        $this->getJson(route('contracts.show', 999999))->assertNotFound();
    }

    public function test_contract_actions_have_required_order_icons_colors_routes_and_visibility(): void
    {
        $contract = Contract::create($this->data());

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('contracts.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

        $response->assertOk();
        $actions = (string) $response->json('data.0.edit');

        $this->assertStringContainsString('btn btn-info btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver contrato"', $actions);
        $this->assertStringContainsString(route('contracts.show', $contract), $actions);
        $this->assertStringContainsString('btn btn-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar contrato"', $actions);
        $this->assertStringContainsString(route('contracts.edit', $contract), $actions);
        $this->assertStringContainsString('btn btn-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-ban', $actions);
        $this->assertStringContainsString('title="Anular"', $actions);
        $this->assertStringContainsString('aria-label="Anular contrato"', $actions);
        $this->assertStringContainsString(route('contracts.cancel', $contract), $actions);
        $this->assertLessThan(strpos($actions, 'btnEditar'), strpos($actions, 'btnVer'));
        $this->assertLessThan(strpos($actions, 'btnAnular'), strpos($actions, 'btnEditar'));

        $this->patchJson(route('contracts.cancel', $contract))->assertOk();

        $closedResponse = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('contracts.index', ['draw' => 1, 'start' => 0, 'length' => 10]));
        $closedActions = (string) $closedResponse->json('data.0.edit');

        $this->assertStringContainsString('btnVer', $closedActions);
        $this->assertStringNotContainsString('btnEditar', $closedActions);
        $this->assertStringNotContainsString('btnAnular', $closedActions);
    }

    public function test_contract_views_use_the_approved_responsive_theme_design(): void
    {
        $contract = Contract::create($this->data());

        $this->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('class="btn btn-success ms-auto"', false)
            ->assertSee('bi bi-plus-circle', false)
            ->assertSee('class="table-responsive"', false)
            ->assertSee('width: 36px;', false)
            ->assertSee('height: 31px;', false)
            ->assertSee('var(--bs-body-bg)', false)
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSee('error.status === 422', false);

        $this->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('class="contract-form"', false)
            ->assertSee('bi bi-floppy', false)
            ->assertSee('btn btn-secondary', false)
            ->assertSee('Guardar')
            ->assertDontSee('fas fa-', false)
            ->assertDontSee('btn btn-danger', false);

        $this->get(route('contracts.edit', $contract))
            ->assertOk()
            ->assertSee('Actualizar')
            ->assertSee('contract-select', false)
            ->assertSee('btn btn-secondary', false);
    }

    public static function temporalDurations(): array
    {
        return [
            ['2026-10-12', '2027-01-12', true],
            ['2026-10-12', '2027-01-13', false],
            ['2026-10-12', '2026-11-12', true],
            ['2026-10-12', '2026-10-13', true],
            ['2027-01-31', '2027-04-30', true],
            ['2027-01-31', '2027-05-01', false],
            ['2027-11-30', '2028-02-29', true],
        ];
    }

    #[DataProvider('temporalDurations')]
    public function test_temporal_maximum_uses_three_calendar_months(string $start, string $end, bool $allowed): void
    {
        $response = $this->postJson(route('contracts.store'), $this->data(['start_date' => $start, 'end_date' => $end]));
        if ($allowed) {
            $response->assertCreated();
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('end_date');
            $this->assertDatabaseCount('contracts', 0);
        }
    }

    public function test_edit_cannot_extend_temporal_over_three_months(): void
    {
        $contract = $this->previous(['start_date' => '2026-10-12', 'end_date' => '2026-12-12']);
        $this->putJson(route('contracts.update', $contract), $this->data([
            'start_date' => '2026-10-12', 'end_date' => '2027-01-13',
        ]))->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->assertSame('2026-12-12', $contract->fresh()->end_date->toDateString());
    }

    public function test_new_contract_preserves_history_and_rejects_overlapping_periods(): void
    {
        $first = $this->previous();
        $this->postJson(route('contracts.store'), $this->data(['start_date' => '2026-08-01', 'end_date' => '2026-09-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->postJson(route('contracts.store'), $this->data())->assertCreated();
        $this->assertDatabaseCount('contracts', 2);
        $this->assertSame('2026-06-01', $first->fresh()->start_date->toDateString());
        $this->assertSame('2026-08-12', $first->fresh()->end_date->toDateString());
    }

    public function test_routes_require_authentication(): void
    {
        $contract = Contract::create($this->data());
        auth()->logout();
        $this->getJson(route('contracts.show', $contract))->assertUnauthorized();
        $this->postJson(route('contracts.store'), $this->data())->assertUnauthorized();
        $this->patchJson(route('contracts.cancel', 1))->assertUnauthorized();
    }

    public function test_new_contract_rejects_past_start_even_for_workers_without_history(): void
    {
        $this->postJson(route('contracts.store'), $this->data(['start_date' => '2026-10-11']))
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->assertDatabaseCount('contracts', 0);
        $this->get(route('contracts.create'))->assertOk()->assertSee('min="2026-10-12"', false);
    }

    public function test_salary_must_be_at_least_1350_on_create_and_update(): void
    {
        foreach (['0', '-1', '1349.99'] as $salary) {
            $this->postJson(route('contracts.store'), $this->data(['salary' => $salary]))
                ->assertUnprocessable()->assertJsonValidationErrors('salary');
        }
        $this->postJson(route('contracts.store'), $this->data(['salary' => '1350.00']))->assertCreated();
        $contract = Contract::first();
        $this->putJson(route('contracts.update', $contract), $this->data(['salary' => '1349.99']))
            ->assertUnprocessable()->assertJsonValidationErrors('salary');
        $this->assertSame('1350.00', $contract->fresh()->salary);
        $this->putJson(route('contracts.update', $contract), $this->data(['salary' => '1350.01']))->assertOk();
        $this->assertSame('1350.01', $contract->fresh()->salary);
    }

    public function test_expired_contract_cannot_be_edited_cancelled_or_reactivated(): void
    {
        $contract = $this->previous();
        $this->getJson(route('contracts.edit', $contract))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->putJson(route('contracts.update', $contract), $this->data(['is_active' => true]))
            ->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->patchJson(route('contracts.cancel', $contract))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->assertSame('2026-08-12', $contract->fresh()->end_date->toDateString());
        $this->assertNull($contract->fresh()->cancelled_at);
        $this->assertSame('Finalizado', $contract->fresh()->statusLabel());
    }

    #[DataProvider('types')]
    public function test_cancellation_preserves_history_and_cannot_be_undone(string $type): void
    {
        $this->postJson(route('contracts.store'), $this->data([
            'type' => $type, 'end_date' => $type === 'Temporal' ? '2026-12-12' : null,
        ]))->assertCreated();
        $contract = Contract::first();
        $this->patchJson(route('contracts.cancel', $contract))->assertOk();
        $saved = $contract->fresh();
        $this->assertFalse($saved->is_active);
        $this->assertSame('2026-10-12', $saved->cancelled_at->toDateString());
        $this->assertSame('2026-10-12', $saved->effectiveEndDate()->toDateString());
        $this->assertSame($type === 'Temporal' ? '2026-12-12' : null, $saved->end_date?->toDateString());
        $this->assertSame('Anulado', $saved->statusLabel());
        $this->assertFalse($saved->allowsVacations());
        $this->getJson(route('contracts.edit', $contract))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->putJson(route('contracts.update', $contract), $this->data(['is_active' => true, 'cancelled_at' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->patchJson(route('contracts.cancel', $contract))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_cancelled_temporal_uses_cancellation_date_for_two_month_wait(): void
    {
        $this->postJson(route('contracts.store'), $this->data())->assertCreated();
        $contract = Contract::first();
        $this->patchJson(route('contracts.cancel', $contract))->assertOk();
        $this->travelTo(now()->setDate(2026, 12, 11));
        $next = $this->data(['start_date' => '2026-12-12', 'end_date' => '2027-01-12']);
        $this->postJson(route('contracts.store'), $next)->assertUnprocessable()->assertJsonValidationErrors('employee_id');
        $this->travelTo(now()->setDate(2026, 12, 12));
        $this->postJson(route('contracts.store'), $next)->assertCreated();
        $this->assertDatabaseCount('contracts', 2);
        $this->assertSame('2026-12-12', $contract->fresh()->end_date->toDateString());
    }

    public function test_future_contract_can_be_cancelled_without_corrupting_scheduled_dates(): void
    {
        $this->postJson(route('contracts.store'), $this->data([
            'start_date' => '2027-01-01', 'end_date' => '2027-02-01',
        ]))->assertCreated();
        $contract = Contract::first();
        $this->patchJson(route('contracts.cancel', $contract))->assertOk();
        $this->assertSame('2027-01-01', $contract->fresh()->start_date->toDateString());
        $this->assertSame('2027-02-01', $contract->fresh()->end_date->toDateString());
        $this->travelTo(now()->setDate(2026, 12, 12));
        $this->postJson(route('contracts.store'), $this->data([
            'start_date' => '2026-12-12', 'end_date' => '2027-01-12',
        ]))->assertCreated();
    }

    #[DataProvider('indefiniteTypes')]
    public function test_cancelled_indefinite_contract_still_does_not_allow_another_contract(string $type): void
    {
        $this->postJson(route('contracts.store'), $this->data(['type' => $type, 'end_date' => null]))->assertCreated();
        $this->patchJson(route('contracts.cancel', Contract::first()))->assertOk();
        $this->travelTo(now()->setDate(2027, 1, 12));
        $this->postJson(route('contracts.store'), $this->data([
            'start_date' => '2027-01-12', 'end_date' => '2027-02-12',
        ]))->assertUnprocessable()->assertJsonValidationErrors('employee_id');
    }

    public function test_active_contract_with_past_start_keeps_original_date_when_editing_salary(): void
    {
        $contract = $this->previous(['start_date' => '2026-10-01', 'end_date' => '2026-12-01']);
        $this->putJson(route('contracts.update', $contract), $this->data([
            'start_date' => '2026-10-01', 'end_date' => '2026-12-01', 'salary' => 1600,
        ]))->assertOk();
        $this->putJson(route('contracts.update', $contract), $this->data([
            'start_date' => '2026-10-02', 'end_date' => '2026-12-01',
        ]))->assertUnprocessable()->assertJsonValidationErrors('start_date');
    }

    public function test_cancelled_contract_list_shows_closure_and_only_view_action(): void
    {
        $this->postJson(route('contracts.store'), $this->data())->assertCreated();
        $this->patchJson(route('contracts.cancel', Contract::first()))->assertOk();
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('contracts.index', ['draw' => 1, 'start' => 0, 'length' => 10]));
        $response->assertOk()->assertJsonPath('data.0.status', 'Anulado')
            ->assertJsonPath('data.0.end_date', '12/10/2026')
            ->assertJsonPath('data.0.next_hiring_date', '12/12/2026');

        $actions = (string) $response->json('data.0.edit');
        $this->assertStringContainsString('btnVer', $actions);
        $this->assertStringNotContainsString('btnEditar', $actions);
        $this->assertStringNotContainsString('btnAnular', $actions);
    }
}
