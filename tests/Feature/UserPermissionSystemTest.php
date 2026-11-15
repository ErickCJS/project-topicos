<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\DevelopmentTestUserSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPermissionSystemTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): void { Artisan::call('permissions:sync-routes'); }

    public function test_transition_migration_copies_each_users_type_permissions_before_dropping_legacy_tables(): void
    {
        $connection = 'permission_migration_test';
        config(['database.connections.'.$connection => array_merge(config('database.connections.sqlite'), ['database' => ':memory:'])]);
        DB::purge($connection);
        $originalDefault = config('database.default');
        config(['database.default' => $connection]);

        try {
            Schema::create('user_types', function (Blueprint $table) {
                $table->id(); $table->string('name'); $table->string('slug'); $table->boolean('is_admin');
                $table->boolean('is_system')->default(false); $table->boolean('is_active')->default(true); $table->timestamps();
            });
            Schema::create('users', function (Blueprint $table) {
                $table->id(); $table->string('email'); $table->foreignId('user_type_id')->nullable()->constrained('user_types');
            });
            Schema::create('permissions', fn (Blueprint $table) => $table->id());
            Schema::create('user_type_permissions', function (Blueprint $table) {
                $table->foreignId('user_type_id')->constrained('user_types')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->primary(['user_type_id', 'permission_id']);
            });
            DB::table('user_types')->insert([
                ['id' => 1, 'name' => 'Administrador', 'slug' => 'admin', 'is_admin' => true, 'created_at' => now(), 'updated_at' => now()],
                ['id' => 2, 'name' => 'Operaciones', 'slug' => 'ops', 'is_admin' => false, 'created_at' => now(), 'updated_at' => now()],
            ]);
            DB::table('users')->insert([
                ['id' => 1, 'email' => 'admin@example.test', 'user_type_id' => 1],
                ['id' => 2, 'email' => 'one@example.test', 'user_type_id' => 2],
                ['id' => 3, 'email' => 'user@gmail.com', 'user_type_id' => 2],
            ]);
            DB::table('permissions')->insert([['id' => 10], ['id' => 11]]);
            DB::table('user_type_permissions')->insert([
                ['user_type_id' => 1, 'permission_id' => 10], ['user_type_id' => 1, 'permission_id' => 11],
                ['user_type_id' => 2, 'permission_id' => 10],
            ]);

            $migration = require base_path('database/migrations/2026_11_14_000001_convert_user_types_to_individual_permissions.php');
            $migration->up();

            $this->assertDatabaseHas('users', ['id' => 1, 'is_admin' => 1, 'user_type_label' => 'Administrador']);
            $this->assertDatabaseHas('users', ['id' => 3, 'is_admin' => 0, 'user_type_label' => 'Operaciones']);
            $this->assertDatabaseHas('user_permissions', ['user_id' => 1, 'permission_id' => 11]);
            $this->assertDatabaseHas('user_permissions', ['user_id' => 2, 'permission_id' => 10]);
            $this->assertDatabaseHas('user_permissions', ['user_id' => 3, 'permission_id' => 10]);
            $this->assertFalse(Schema::hasTable('user_types'));
            $this->assertFalse(Schema::hasTable('user_type_permissions'));
            $this->assertFalse(Schema::hasColumn('users', 'user_type_id'));
        } finally {
            config(['database.default' => $originalDefault]);
            DB::purge($connection);
        }
    }

    public function test_users_of_the_same_label_have_independent_permissions_and_new_users_start_empty(): void
    {
        $this->catalog();
        $admin = User::factory()->create();
        $first = User::factory()->regular()->create(['user_type_label' => 'Operaciones']);
        $second = User::factory()->regular()->create(['user_type_label' => 'Operaciones']);
        $view = Permission::where('slug', 'vehiculos.view')->firstOrFail();
        $first->permissions()->sync([$view->id]);

        $this->assertTrue(app(\App\Services\UserPermissionService::class)->allows($first, 'vehiculos.view'));
        $this->assertFalse(app(\App\Services\UserPermissionService::class)->allows($second, 'vehiculos.view'));
        $this->assertSame([], User::factory()->regular()->create()->permissions()->pluck('slug')->all());
        $this->assertTrue(app(\App\Services\UserPermissionService::class)->isAdministrator($admin));
        $this->assertTrue(app(\App\Services\UserPermissionService::class)->allows($admin, 'cualquier.permiso'));
    }

    public function test_changing_the_descriptive_label_does_not_change_authorization(): void
    {
        $this->catalog();
        $admin = User::factory()->create();
        $user = User::factory()->regular()->create(['user_type_label' => 'A']);
        $permission = Permission::where('slug', 'vehiculos.view')->firstOrFail();
        $user->permissions()->sync([$permission->id]);

        $this->actingAs($admin)->patchJson(route('users.type-label', $user), ['user_type_label' => 'B'])->assertOk();
        $this->assertTrue(app(\App\Services\UserPermissionService::class)->allows($user->fresh(), 'vehiculos.view'));
        $this->assertSame([$permission->id], $user->fresh()->permissions()->pluck('permissions.id')->all());
    }

    public function test_administrator_can_save_permissions_for_one_account_from_modal_endpoint(): void
    {
        $this->catalog();
        $admin = User::factory()->create();
        $user = User::factory()->regular()->create();
        $permission = Permission::where('slug', 'vehiculos.details')->firstOrFail();
        $this->actingAs($admin)->withHeaders(['X-Modal-Request' => '1'])->get(route('users.permissions', $user))->assertOk()->assertSee('permissionMatrixForm');
        $this->actingAs($admin)->putJson(route('users.permissions.save', $user), ['permissions' => [$permission->id]])->assertOk();
        $this->assertSame([$permission->id], $user->fresh()->permissions()->pluck('permissions.id')->all());
    }

    public function test_user_index_shows_labels_and_individual_permission_and_classification_actions(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->regular()->create(['user_type_label' => 'Operaciones']);
        $this->actingAs($admin)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('users.index'))
            ->assertOk()->assertJsonPath('data.1.type', 'Operaciones')
            ->assertJsonFragment(['type' => 'Administrador'])
            ->assertSee('bi-tag')->assertSee('bi-key');
    }

    public function test_test_user_seeder_assigns_three_permissions_without_changing_existing_account_data(): void
    {
        $this->catalog();
        $user = User::factory()->create(['email' => 'user@gmail.com', 'password' => 'OriginalPass123', 'name' => 'Conservado']);
        $original = $user->fresh()->only(['password', 'name', 'email']);
        $this->seed(DevelopmentTestUserSeeder::class);
        $user = $user->fresh();
        $this->assertSame($original, $user->only(array_keys($original)));
        $this->assertFalse($user->is_admin);
        $this->assertSame(['dashboard.view', 'vehiculos.details', 'vehiculos.view'], $user->permissions()->orderBy('slug')->pluck('slug')->all());
    }

    public function test_last_active_administrator_cannot_be_disabled_demoted_or_deleted(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->patchJson(route('users.status', $admin), ['is_active' => false])->assertUnprocessable();
        $this->actingAs($admin)->putJson(route('users.update', $admin), [
            'name' => $admin->name, 'last_name' => $admin->last_name, 'dni' => $admin->dni, 'email' => $admin->email,
            'is_admin' => false, 'is_active' => true,
        ])->assertUnprocessable();
        $this->actingAs($admin)->deleteJson(route('users.destroy', $admin))->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => 1, 'is_admin' => 1]);
    }

    public function test_administrators_cannot_be_deleted_and_regular_users_can_authenticate_and_logout(): void
    {
        $this->catalog();
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();
        $this->actingAs($admin)->deleteJson(route('users.destroy', $otherAdmin))->assertUnprocessable();

        auth()->logout();
        $user = User::factory()->regular()->create();
        $user->permissions()->sync(Permission::whereIn('slug', ['dashboard.view', 'vehiculos.view'])->pluck('id'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('vehicles.index'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_access_api_returns_individual_permissions_and_account_fields(): void
    {
        $this->catalog();
        $user = User::factory()->regular()->create(['user_type_label' => 'Operaciones']);
        $user->permissions()->sync(Permission::where('slug', 'dashboard.view')->pluck('id'));
        $this->actingAs($user)->getJson('/api/me/access')->assertOk()->assertJsonPath('is_admin', false)->assertJsonPath('user_type_label', 'Operaciones')->assertJsonPath('permisos', ['dashboard.view']);
    }
}
