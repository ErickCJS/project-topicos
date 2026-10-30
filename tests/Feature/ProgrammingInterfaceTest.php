<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgrammingInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Las pruebas de Programaciones requieren SQLite en memoria.');
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('programming.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_the_interface_with_empty_catalogs(): void
    {
        $this->actingAs(User::factory()->make(['id' => 1]));

        $this->get(route('programming.index'))
            ->assertOk()
            ->assertSee('Lista de Programaciones')
            ->assertSee('programmingFormModal')
            ->assertSee('Validar disponibilidad')
            ->assertSee('Feriados del período')
            ->assertSee('programHolidayOptions')
            ->assertSee('js/programming.js')
            ->assertSee('css/programming.css');
    }
}
