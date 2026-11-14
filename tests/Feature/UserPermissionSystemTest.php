<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\admin\Brand;
use App\Models\admin\Brandmodel;
use App\Models\admin\Color;
use App\Models\admin\Vehicle;
use App\Models\admin\Vehicletype;
use App\Models\User;
use App\Models\UserType;
use Database\Seeders\DevelopmentTestUserSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPermissionSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_sync_assigns_routes_to_stable_permissions(): void
    {
        Artisan::call('permissions:sync-routes');
        $this->assertDatabaseHas('permission_routes', ['route_name' => 'vehicles.index']);
        $this->assertDatabaseHas('permission_routes', ['route_name' => 'vehicles.photo.subirimagenes']);
        $this->assertDatabaseHas('permission_routes', ['route_name' => 'programming.update-modifications']);
    }

    public function test_limited_user_can_read_authorized_routes_but_cannot_manage_users(): void
    {
        Artisan::call('permissions:sync-routes');
        $type = UserType::create(['name' => 'Consulta', 'slug' => 'consulta', 'is_active' => true]);
        $type->permissions()->sync(Permission::whereIn('slug', ['dashboard.view', 'vehiculos.view', 'vehiculos.details'])->pluck('id'));
        $user = User::factory()->create(['user_type_id' => $type->id]);
        $this->assertTrue(app(\App\Services\UserPermissionService::class)->allows($user, 'dashboard.view'));
        $this->assertDatabaseHas('permission_routes', ['route_name' => 'admin.home', 'permission_id' => Permission::where('slug', 'dashboard.view')->value('id')]);

        $dashboard = $this->actingAs($user)->get(route('admin.home'))->assertOk()->assertSee('data-can-edit="0"', false);
        $dashboard->assertSee(route('admin.home'), false)->assertSee(route('vehicles.index'), false)
            ->assertDontSee(route('brands.index'), false)
            ->assertDontSee('Gestión de personal')
            ->assertDontSee('Programación')
            ->assertDontSee('Gestión de cambios')
            ->assertDontSee('Gestión de usuarios');
        $brand = Brand::create(['name' => 'Marca de prueba']);
        $model = Brandmodel::create(['name' => 'Modelo de prueba', 'brand_id' => $brand->id]);
        $color = Color::create(['name' => 'Azul', 'code' => '#0000ff']);
        $vehicleType = Vehicletype::create(['name' => 'Camioneta']);
        $vehicle = Vehicle::create(['code' => 'V001', 'name' => 'Vehículo de prueba', 'plate' => 'ABC123', 'year' => 2025, 'load_capacity' => 100, 'occupats_capacity' => 5, 'fuel_capacity' => 60, 'compact_capacity' => 5, 'type_id' => $vehicleType->id, 'brand_id' => $brand->id, 'model_id' => $model->id, 'color_id' => $color->id]);
        $this->actingAs($user)->get(route('vehicles.index'))->assertOk()->assertDontSee('id="btnNuevo"', false);
        $table = $this->actingAs($user)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->getJson(route('vehicles.index'))->assertOk()->json('data.0.actions');
        $this->assertStringContainsString('btnVer', $table);
        $this->assertStringNotContainsString('btnEditar', $table);
        $this->assertStringNotContainsString('btnEliminar', $table);
        $this->assertStringNotContainsString('btnImagen', $table);
        $this->actingAs($user)->get(route('vehicles.edit', $vehicle))->assertForbidden();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->post(route('vehicles.store'), [])->assertForbidden();

        $administrator = User::factory()->create();
        $adminActions = $this->actingAs($administrator)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->getJson(route('vehicles.index'))->assertOk()->json('data.0.actions');
        $this->assertStringContainsString('btnVer', $adminActions);
        $this->assertStringContainsString('btnImagen', $adminActions);
        $this->assertStringContainsString('btnEditar', $adminActions);
        $this->assertStringContainsString('btnEliminar', $adminActions);
    }

    public function test_api_access_endpoint_returns_permissions_and_rejects_inactive_accounts(): void
    {
        Artisan::call('permissions:sync-routes');
        $type = UserType::create(['name' => 'Consulta', 'slug' => 'consulta', 'is_active' => true]);
        $type->permissions()->sync(Permission::whereIn('slug', ['dashboard.view'])->pluck('id'));
        $user = User::factory()->create(['user_type_id' => $type->id]);
        Sanctum::actingAs($user);
        $this->getJson('/api/me/access')->assertOk()->assertJsonPath('permisos.0', 'dashboard.view');
        $user->update(['is_active' => false]);
        $this->getJson('/api/me/access')->assertForbidden();
    }

    public function test_user_crud_persists_personal_data_and_keeps_an_empty_password(): void
    {
        $type = UserType::create(['name' => 'Operaciones', 'slug' => 'operaciones', 'is_active' => true]);
        $administrator = User::factory()->create();
        $this->actingAs($administrator)->get(route('users.index'))->assertOk()->assertSee('Gestión de usuarios');
        $response = $this->actingAs($administrator)->post(route('users.store'), [
            'name' => 'Ana', 'last_name' => 'Pérez', 'dni' => '12345678', 'email' => 'ana@example.test',
            'password' => 'Secreto123', 'user_type_id' => $type->id, 'is_active' => 1,
        ]);
        $response->assertRedirect(route('users.index'));
        $user = User::where('email', 'ana@example.test')->firstOrFail();
        $oldPassword = $user->password;
        $this->actingAs($administrator)->put(route('users.update', $user), [
            'name' => 'Ana María', 'last_name' => 'Pérez', 'dni' => '12345678', 'email' => 'ana@example.test',
            'password' => '', 'user_type_id' => $type->id, 'is_active' => 1,
        ])->assertRedirect(route('users.index'));
        $this->assertSame($oldPassword, $user->fresh()->password);
        $this->assertSame('Ana María', $user->fresh()->name);
        $this->assertTrue(Hash::check('Secreto123', $oldPassword));
    }

    public function test_development_test_account_is_limited_and_seed_is_idempotent(): void
    {
        Artisan::call('permissions:sync-routes');
        $this->seed(DevelopmentTestUserSeeder::class);
        $this->seed(DevelopmentTestUserSeeder::class);
        $user = User::where('email', 'user@gmail.com')->firstOrFail();
        $this->assertSame('Usuario', $user->name);
        $this->assertSame('De prueba', $user->last_name);
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertSame(['dashboard.view', 'vehiculos.details', 'vehiculos.view'], $user->userType->permissions()->orderBy('slug')->pluck('slug')->all());
        $this->assertFalse($user->userType->is_admin);
        $this->post('/login', ['email' => 'user@gmail.com', 'password' => 'Password123'])->assertRedirect(config('fortify.home'));
        $this->get(route('admin.home'))->assertOk()->assertSee('data-can-edit="0"', false)
            ->assertSee(route('vehicles.index'), false)
            ->assertDontSee(route('brands.index'), false)
            ->assertDontSee('Gestión de personal')
            ->assertDontSee('Programación')
            ->assertDontSee('Gestión de cambios')
            ->assertDontSee('Gestión de usuarios');
        $this->get(route('vehicles.index'))->assertOk()->assertDontSee('id="btnNuevo"', false);
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_last_active_administrator_cannot_be_deactivated_or_deleted(): void
    {
        $administrator = User::factory()->create();
        $this->actingAs($administrator)->patchJson(route('users.status', $administrator), ['is_active' => false])->assertUnprocessable();
        $this->actingAs($administrator)->deleteJson(route('users.destroy', $administrator))->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $administrator->id, 'is_active' => 1]);
    }

    public function test_protected_administrator_type_cannot_be_configured_or_deleted(): void
    {
        $administrator = User::factory()->create();
        $this->actingAs($administrator)->get(route('user-types.permissions', $administrator->userType))->assertForbidden();
        $this->actingAs($administrator)->delete(route('user-types.destroy', $administrator->userType))->assertUnprocessable();
    }

    public function test_permission_matrix_loads_the_existing_catalog(): void
    {
        Artisan::call('permissions:sync-routes');
        $administrator = User::factory()->create();
        $type = UserType::create(['name' => 'Operaciones', 'slug' => 'operaciones', 'is_active' => true]);
        $this->actingAs($administrator)->get(route('user-types.permissions', $type))->assertOk()->assertSee('Gestión de vehículos')->assertSee('Gestionar imágenes');
    }

    public function test_administrator_menu_does_not_depend_on_individual_permissions(): void
    {
        $administrator = User::factory()->create();
        $this->actingAs($administrator)->get(route('admin.home'))->assertOk()
            ->assertSee('Inicio')
            ->assertSee('Gestión de vehículos')
            ->assertSee('Gestión de personal')
            ->assertSee('Programación')
            ->assertSee('Gestión de cambios')
            ->assertSee('Gestión de usuarios')
            ->assertSee('Tipos de usuario')
            ->assertSee('Colores')
            ->assertSee('Marcas')
            ->assertSee('Modelos')
            ->assertSee('Tipos de vehículo')
            ->assertSee('Vehículos')
            ->assertSee('Tipos de personal')
            ->assertSee('Personal')
            ->assertSee('Contratos')
            ->assertSee('Asistencias')
            ->assertSee('Vacaciones')
            ->assertSee('Turnos')
            ->assertSee('Zonas')
            ->assertSee('Feriados')
            ->assertSee('Grupo de personal')
            ->assertSee('Motivos')
            ->assertSee('Cambios')
            ->assertSee('data-can-edit="1"', false);
    }

    public function test_unassigned_user_can_authenticate_without_permissions_or_a_type(): void
    {
        $user = User::factory()->create(['user_type_id' => null]);
        $passwordHash = $user->password;
        $this->assertTrue(Hash::check('password', $passwordHash));

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(config('fortify.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_explicitly_inactive_account_cannot_authenticate(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_initial_assignment_admins_existing_users_and_preserves_test_account_data(): void
    {
        $operatorType = UserType::create(['name' => 'Tipo anterior', 'slug' => 'tipo-anterior', 'is_active' => true]);
        $first = User::factory()->create(['user_type_id' => $operatorType->id, 'profile_photo_path' => 'profile-photos/primera.jpg']);
        $second = User::factory()->create(['user_type_id' => null, 'profile_photo_path' => 'profile-photos/segunda.jpg']);
        $testType = UserType::create(['name' => 'Usuario de prueba', 'slug' => 'usuario-de-prueba', 'is_active' => true]);
        $testUser = User::factory()->create(['email' => 'user@gmail.com', 'user_type_id' => $testType->id, 'profile_photo_path' => 'profile-photos/prueba.jpg']);
        $original = ['password' => $testUser->password, 'email' => $testUser->email, 'name' => $testUser->name, 'photo' => $testUser->profile_photo_path];
        $token = $testUser->createToken('sesión existente')->plainTextToken;
        $userId = $testUser->id;
        $migration = require base_path('database/migrations/2026_11_13_000003_assign_existing_users_to_admin_type.php');

        $migration->up();

        $adminType = UserType::where('slug', 'administrador')->firstOrFail();
        $this->assertTrue($adminType->is_admin);
        $this->assertTrue($adminType->is_system);
        $this->assertSame($adminType->id, $first->fresh()->user_type_id);
        $this->assertSame($adminType->id, $second->fresh()->user_type_id);
        $testUser = User::findOrFail($userId);
        $this->assertSame($testType->id, $testUser->user_type_id);
        $this->assertSame($original['password'], $testUser->password);
        $this->assertSame($original['email'], $testUser->email);
        $this->assertSame($original['name'], $testUser->name);
        $this->assertSame($original['photo'], $testUser->profile_photo_path);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $userId, 'name' => 'sesión existente']);
        $this->assertNotEmpty($token);
    }

    public function test_permission_sync_does_not_make_later_users_administrators(): void
    {
        Artisan::call('permissions:sync-routes');
        $type = UserType::create(['name' => 'Operaciones', 'slug' => 'operaciones', 'is_active' => true]);
        $laterUser = User::factory()->create(['user_type_id' => $type->id]);

        Artisan::call('permissions:sync-routes');

        $this->assertSame($type->id, $laterUser->fresh()->user_type_id);
        $this->assertFalse($laterUser->fresh()->userType->is_admin);
    }

    public function test_test_user_seeder_changes_only_the_type_and_permissions_of_an_existing_test_account(): void
    {
        $user = User::factory()->create([
            'email' => 'user@gmail.com', 'name' => 'Nombre conservado', 'last_name' => 'Apellido conservado',
            'password' => 'Clave anterior', 'profile_photo_path' => 'profile-photos/conservada.jpg', 'is_active' => false,
            'two_factor_secret' => 'secreto-2fa', 'two_factor_recovery_codes' => 'codigos-2fa',
        ]);
        $user->createToken('token conservado');
        $original = $user->fresh()->only(['email', 'name', 'last_name', 'password', 'profile_photo_path', 'is_active', 'two_factor_secret', 'two_factor_recovery_codes']);

        $this->seed(DevelopmentTestUserSeeder::class);

        $fresh = $user->fresh();
        $this->assertSame($original, $fresh->only(array_keys($original)));
        $this->assertFalse($fresh->userType->is_admin);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $fresh->id, 'name' => 'token conservado']);
    }

    public function test_permission_matrix_supports_test_and_custom_types_and_hides_protected_types(): void
    {
        Artisan::call('permissions:sync-routes');
        $administrator = User::factory()->create();
        $testType = UserType::where('slug', 'usuario-de-prueba')->firstOrFail();
        $this->assertSame(3, $testType->id);
        $this->actingAs($administrator)->get(route('user-types.permissions', 3))->assertOk()
            ->assertSee('Permisos disponibles')->assertSee('Usuario de prueba');
        $this->actingAs($administrator)->get(route('user-types.permissions', $administrator->userType))->assertForbidden();
        $pending = UserType::where('slug', 'pendiente-de-asignacion')->firstOrFail();
        $this->actingAs($administrator)->get(route('user-types.permissions', $pending))->assertForbidden();

        $customSystemType = UserType::create(['name' => 'Tipo del sistema', 'slug' => 'tipo-del-sistema', 'is_system' => true, 'is_active' => true]);
        $this->actingAs($administrator)->get(route('user-types.permissions', $customSystemType))->assertOk();
        $this->actingAs($administrator)->get(route('user-types.index'))->assertOk()
            ->assertSee(route('user-types.permissions', $testType), false)
            ->assertSee(route('user-types.permissions', $customSystemType), false)
            ->assertDontSee(route('user-types.permissions', $pending), false)
            ->assertDontSee(route('user-types.permissions', $administrator->userType), false);

        $regularType = UserType::create(['name' => 'Usuario regular', 'slug' => 'usuario-regular', 'is_active' => true]);
        $regularUser = User::factory()->create(['user_type_id' => $regularType->id]);
        $this->actingAs($regularUser)->get(route('user-types.permissions', $testType))->assertForbidden();
    }

    public function test_permission_matrix_saves_complete_partial_and_empty_selections(): void
    {
        Artisan::call('permissions:sync-routes');
        $administrator = User::factory()->create();
        $type = UserType::where('slug', 'usuario-de-prueba')->firstOrFail();
        $permissionIds = Permission::whereIn('slug', ['vehiculos.view', 'vehiculos.details', 'vehiculos.create'])->orderBy('id')->pluck('id')->all();

        $this->actingAs($administrator)->put(route('user-types.permissions.save', $type), ['permissions' => $permissionIds])->assertRedirect(route('user-types.index'));
        $this->assertEqualsCanonicalizing($permissionIds, $type->fresh()->permissions()->pluck('permissions.id')->all());

        $partial = array_slice($permissionIds, 0, 1);
        $this->actingAs($administrator)->put(route('user-types.permissions.save', $type), ['permissions' => $partial])->assertRedirect(route('user-types.index'));
        $this->assertEqualsCanonicalizing($partial, $type->fresh()->permissions()->pluck('permissions.id')->all());

        $this->actingAs($administrator)->put(route('user-types.permissions.save', $type), ['permissions' => []])->assertRedirect(route('user-types.index'));
        $this->assertSame([], $type->fresh()->permissions()->pluck('permissions.id')->all());
    }
}
