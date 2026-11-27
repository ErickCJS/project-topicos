<?php

namespace Tests\Feature;

use App\Models\admin\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Las pruebas de Turnos requieren SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_guest_cannot_access_shift_routes(): void
    {
        auth()->logout();

        $this->get(route('shifts.index'))->assertRedirect(route('login'));
        $this->postJson(route('shifts.store'), $this->data())->assertUnauthorized();
    }

    public function test_shift_crud_supports_night_shifts(): void
    {
        $this->get(route('shifts.index'))->assertOk()->assertSee('Listado de Turnos');
        $this->get(route('shifts.create'))->assertOk()->assertSee('start_time');

        $response = $this->postJson(route('shifts.store'), $this->data([
            'name' => 'Noche',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'description' => 'Recolección nocturna',
        ]));
        $response->assertCreated()->assertJsonPath('message', 'Turno registrado correctamente.');

        $shift = Shift::firstOrFail();
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'name' => 'Noche',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'description' => 'Recolección nocturna',
        ]);
        $this->get(route('shifts.show', $shift))->assertOk()->assertSee('Noche')->assertSee('22:00')->assertSee('Recolección nocturna')->assertSee('Finaliza al día siguiente');
        $this->get(route('shifts.edit', $shift))->assertOk()->assertSee('Noche');

        $this->putJson(route('shifts.update', $shift), $this->data([
            'name' => 'Turno actualizado',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'description' => 'Horario actualizado',
        ]))->assertOk();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'name' => 'Turno actualizado', 'description' => 'Horario actualizado']);

        $this->deleteJson(route('shifts.destroy', $shift))
            ->assertOk()
            ->assertJsonPath('message', 'Turno eliminado correctamente.');
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_index_ajax_returns_datatables_data(): void
    {
        Shift::create($this->data(['name' => 'Mañana']));

        $this->getJson(route('shifts.index'))
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
            ->assertJsonPath('data.0.name', 'Mañana');
    }

    public function test_shift_validation_requires_unique_name_and_distinct_valid_times(): void
    {
        Shift::create($this->data(['name' => 'Mañana']));

        $this->postJson(route('shifts.store'), $this->data([
            'name' => 'Mañana',
            'start_time' => '08:00',
            'end_time' => '08:00',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['name', 'end_time']);

        $this->postJson(route('shifts.store'), [
            'name' => str_repeat('x', 51),
            'start_time' => '8:00',
            'end_time' => '25:00',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'start_time', 'end_time']);

        $shift = Shift::firstOrFail();
        $this->putJson(route('shifts.update', $shift), $this->data(['name' => 'Mañana']))
            ->assertOk();
        $this->assertDatabaseCount('shifts', 1);
    }

    public function test_overlapping_daytime_hours_are_rejected_on_create_and_update(): void
    {
        Shift::create($this->data(['name' => 'Mañana', 'start_time' => '06:00', 'end_time' => '14:00']));
        foreach ([['05:00', '07:00'], ['13:00', '15:00'], ['07:00', '13:00'], ['05:00', '15:00'], ['06:00', '14:00']] as [$start, $end]) {
            $this->postJson(route('shifts.store'), $this->data(['start_time' => $start, 'end_time' => $end]))
                ->assertUnprocessable()->assertJsonValidationErrors('start_time');
        }
        $other = Shift::create($this->data(['start_time' => '14:00', 'end_time' => '22:00']));
        $this->putJson(route('shifts.update', $other), $this->data(['start_time' => '13:00', 'end_time' => '22:00']))
            ->assertUnprocessable()->assertJsonValidationErrors('start_time');
        $this->assertSame('14:00', $other->fresh()->start_time);
    }

    public function test_night_shift_conflicts_are_detected_on_both_sides_of_midnight(): void
    {
        Shift::create($this->data(['name' => 'Noche', 'start_time' => '22:00', 'end_time' => '06:00']));
        foreach ([['23:00', '02:00'], ['21:00', '23:00'], ['05:00', '07:00'], ['00:00', '01:00'], ['05:00', '23:00']] as [$start, $end]) {
            $this->postJson(route('shifts.store'), $this->data(['start_time' => $start, 'end_time' => $end]))
                ->assertUnprocessable()->assertJsonValidationErrors('start_time');
        }
        $this->assertDatabaseCount('shifts', 1);
    }

    public function test_adjacent_shifts_and_editing_own_hours_are_allowed(): void
    {
        foreach ([['Mañana', '06:00', '14:00'], ['Tarde', '14:00', '22:00'], ['Noche', '22:00', '06:00']] as [$name, $start, $end]) {
            $this->postJson(route('shifts.store'), $this->data(['name' => $name, 'start_time' => $start, 'end_time' => $end]))->assertCreated();
        }
        $shift = Shift::where('name', 'Noche')->firstOrFail();
        $this->putJson(route('shifts.update', $shift), $this->data(['name' => 'Noche', 'start_time' => '22:00', 'end_time' => '06:00', 'description' => 'Turno nocturno']))->assertOk();
        $this->assertDatabaseCount('shifts', 3);
    }

    public function test_shifts_can_meet_at_midnight_without_overlapping(): void
    {
        Shift::create($this->data(['name' => 'Hasta medianoche', 'start_time' => '16:00', 'end_time' => '00:00']));
        $this->postJson(route('shifts.store'), $this->data(['name' => 'Madrugada', 'start_time' => '00:00', 'end_time' => '06:00']))->assertCreated();
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Turno base',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ], $overrides);
    }

    public function test_description_is_optional_and_has_a_length_limit(): void
    {
        $this->postJson(route('shifts.store'), $this->data(['description' => null]))->assertCreated();
        $shift = Shift::firstOrFail();
        $this->assertNull($shift->description);
        $this->putJson(route('shifts.update', $shift), $this->data(['description' => str_repeat('a', 1001)]))
            ->assertUnprocessable()->assertJsonValidationErrors('description');
        $this->assertNull($shift->fresh()->description);
    }

    public function test_edit_form_uses_hour_and_minute_values_even_when_database_contains_seconds(): void
    {
        $shift = Shift::create($this->data(['start_time' => '22:00:00', 'end_time' => '06:00:00']));
        $this->get(route('shifts.edit', $shift))->assertOk()
            ->assertSee('value="22:00"', false)->assertSee('value="06:00"', false)
            ->assertDontSee('value="22:00:00"', false);
    }
}
