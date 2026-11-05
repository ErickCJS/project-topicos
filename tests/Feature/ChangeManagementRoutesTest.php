<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeManagementRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_change_management_routes_are_available(): void
    {
        $this->get(route('reason.index'))->assertOk()->assertSee('Motivos');
        $this->get(route('cambios.index'))->assertOk()->assertSee('Cambios');
        $this->get(route('programming-changes.index'))->assertOk()
            ->assertSee('Cambios de programación')->assertSee('massChangeModal');
    }
}
