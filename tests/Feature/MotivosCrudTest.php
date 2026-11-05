<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\admin\Reason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotivosCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Las pruebas de motivos requieren SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_motivo_crud_works(): void
    {
        $this->get(route('reason.index'))->assertOk()->assertSee('Listado de Motivos');

        $this->postJson(route('reason.store'), [
            'name' => 'Falta de personal',
            'description' => 'No hubo personal disponible para el turno.',
        ])->assertCreated()->assertJsonPath('message', 'Motivo registrado correctamente.');

        $this->assertDatabaseHas('reason', ['name' => 'Falta de personal']);

        $motivo = Reason::firstOrFail();

        $this->get(route('reason.edit', ['reason' => $motivo->id]))->assertOk()->assertSee('Falta de personal');

        $this->putJson(route('reason.update', ['reason' => $motivo->id]), [
            'name' => 'Falta de personal actualizada',
            'description' => 'Se ajustó el motivo.',
        ])->assertOk()->assertJsonPath('message', 'Motivo actualizado correctamente.');

        $this->assertDatabaseHas('reason', ['id' => $motivo->id, 'name' => 'Falta de personal actualizada']);

        $this->deleteJson(route('reason.destroy', ['reason' => $motivo->id]))
            ->assertOk()
            ->assertJsonPath('message', 'Motivo eliminado correctamente.');

        $this->assertDatabaseMissing('reason', ['id' => $motivo->id]);
    }
}
