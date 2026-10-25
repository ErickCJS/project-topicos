<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ProgrammingInterfaceTest extends TestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('programming.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_the_interface_without_programming_tables(): void
    {
        $this->actingAs(User::factory()->make(['id' => 1]));

        $this->get(route('programming.index'))
            ->assertOk()
            ->assertSee('Lista de Programaciones')
            ->assertSee('programmingFormModal')
            ->assertSee('Validar disponibilidad')
            ->assertSee('js/programming.js')
            ->assertSee('css/programming.css');
    }
}
