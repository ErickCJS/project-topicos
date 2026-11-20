<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserTypeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('permissions:sync-routes');
    }

    private function createRegularUserWithAccess(): User
    {
        $user = User::factory()->regular()->create([
            'is_active' => true,
        ]);

        $dashboardPermission = Permission::where(
            'slug',
            'dashboard.view'
        )->firstOrFail();

        $user->permissions()->sync([
            $dashboardPermission->id,
        ]);

        return $user;
    }

    public function test_regular_user_cannot_access_user_type_listing(): void
    {
        $user = $this->createRegularUserWithAccess();

        $this->actingAs($user)
            ->get(route('user-types.index'))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_open_user_type_creation_form(): void
    {
        $user = $this->createRegularUserWithAccess();

        $this->actingAs($user)
            ->get(route('user-types.create'))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_create_user_types(): void
    {
        $user = $this->createRegularUserWithAccess();

        $this->actingAs($user)
            ->postJson(route('user-types.store'), [
                'name' => 'Supervisor',
                'description' => 'Personal de supervisión.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('user_types', [
            'name' => 'Supervisor',
        ]);
    }

    public function test_regular_user_cannot_edit_user_types(): void
    {
        $user = $this->createRegularUserWithAccess();

        $type = UserType::create([
            'name' => 'Operador',
            'description' => 'Personal operativo.',
        ]);

        $this->actingAs($user)
            ->get(route('user-types.edit', $type->id))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_update_user_types(): void
    {
        $user = $this->createRegularUserWithAccess();

        $type = UserType::create([
            'name' => 'Operador',
            'description' => 'Personal operativo.',
        ]);

        $this->actingAs($user)
            ->putJson(route('user-types.update', $type->id), [
                'name' => 'Supervisor',
                'description' => 'Nueva clasificación.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('user_types', [
            'id' => $type->id,
            'name' => 'Operador',
        ]);
    }

    public function test_administrator_can_access_user_type_management(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        UserType::create([
            'name' => 'Operador',
            'description' => 'Personal operativo.',
        ]);

        $this->actingAs($admin)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Modal-Request' => '1',
            ])
            ->get(route('user-types.index'))
            ->assertOk()
            ->assertViewIs('admin.user-types.partials.index-modal');

        $this->actingAs($admin)
            ->get(route('user-types.create'))
            ->assertOk()
            ->assertViewIs('admin.user-types.partials.form-modal');
    }

    public function test_user_type_administration_does_not_grant_individual_permissions(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->regular()->create();

        $type = UserType::create([
            'name' => 'Operador',
            'description' => 'Personal operativo.',
        ]);

        $this->actingAs($admin)
            ->postJson(route('user-types.store'), [
                'name' => 'Supervisor',
                'description' => 'Personal de supervisión.',
            ])
            ->assertOk();

        $user->update([
            'user_type_id' => $type->id,
        ]);

        $this->assertSame(
            [],
            $user->fresh()->permissions()
                ->pluck('slug')
                ->all()
        );

        $this->assertFalse(
            app(\App\Services\UserPermissionService::class)
                ->allows($user, 'vehiculos.view')
        );
    }
}
