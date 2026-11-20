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
        $this->get(route('cambios.index'))
            ->assertOk()
            ->assertSee('Cambios')
            ->assertSee('Nuevo cambio masivo')
            ->assertSee('changeFilters')
            ->assertSee('changesTable');
    }

    public function test_bulk_change_interface_is_available_under_changes(): void
    {
        $this->get(route('cambios.bulk.index'))
            ->assertOk()
            ->assertSee('Nuevo cambio masivo')
            ->assertSee('bulkModificationModal')
            ->assertSee('bulkModificationStart')
            ->assertSee('bulkModificationEnd')
            ->assertSee('bulkModificationZone')
            ->assertSee('bulkModificationPreview')
            ->assertSee('bulkModificationApply')
            ->assertSee('Descripción adicional')
            ->assertSee('no puede deshacerse automáticamente')
            ->assertSee('js/bulk-programming-modifications.js')
            ->assertSee('/admin/cambios/masivos');
    }

    public function test_change_listing_rejects_an_inverted_date_range(): void
    {
        $this->getJson(route('cambios.index', [
            'filter_start' => '2026-11-10',
            'filter_end' => '2026-11-01',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['filter_end']);
    }
}
