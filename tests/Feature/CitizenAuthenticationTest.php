<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Models\admin\Employee;
use App\Models\admin\EmployeeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CitizenAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_register(): void
    {
        $response = $this->postJson('/api/v1/ciudadano/register', [
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('citizen.email', 'ana@example.com')
            ->assertJsonMissing(['password' => 'password123'])
            ->assertJsonStructure(['access_token', 'token_type', 'citizen']);

        $this->assertDatabaseHas('citizens', ['dni' => '12345678', 'email' => 'ana@example.com']);
        $this->assertTrue(Hash::check('password123', Citizen::firstOrFail()->password));
    }

    public function test_citizen_can_login_with_email_and_password(): void
    {
        Citizen::create([
            'dni' => '12345678', 'first_name' => 'Ana', 'last_name' => 'Pérez',
            'email' => 'ana@example.com', 'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/ciudadano/login', [
            'email' => 'ana@example.com', 'password' => 'password123',
        ])->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['access_token', 'citizen']);
    }

    public function test_citizen_login_rejects_invalid_credentials(): void
    {
        Citizen::create([
            'dni' => '12345678', 'first_name' => 'Ana', 'last_name' => 'Pérez',
            'email' => 'ana@example.com', 'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/ciudadano/login', [
            'email' => 'ana@example.com', 'password' => 'incorrecta',
        ])->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_duplicate_dni_and_email_are_rejected(): void
    {
        Citizen::create([
            'dni' => '12345678', 'first_name' => 'Ana', 'last_name' => 'Pérez',
            'email' => 'ana@example.com', 'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/ciudadano/register', [
            'dni' => '12345678', 'first_name' => 'Luis', 'last_name' => 'Rojas',
            'email' => 'ana@example.com', 'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['dni', 'email']);
    }

    public function test_citizen_can_view_profile(): void
    {
        $citizen = Citizen::create([
            'dni' => '12345678', 'first_name' => 'Ana', 'last_name' => 'Pérez',
            'email' => 'ana@example.com', 'password' => Hash::make('password123'),
        ]);

        $token = $citizen->createToken('flutter-citizen')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/ciudadano/perfil')
            ->assertOk()->assertJsonPath('data.email', 'ana@example.com')
            ->assertJsonMissing(['password' => 'password123']);
    }

    public function test_logout_revokes_only_current_citizen_token(): void
    {
        $citizen = Citizen::create([
            'dni' => '12345678', 'first_name' => 'Ana', 'last_name' => 'Pérez',
            'email' => 'ana@example.com', 'password' => Hash::make('password123'),
        ]);
        $first = $citizen->createToken('flutter-citizen')->plainTextToken;
        $second = $citizen->createToken('flutter-citizen')->plainTextToken;

        $this->withToken($first)->postJson('/api/v1/ciudadano/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => (int) explode('|', $first, 2)[0]]);
        $this->withToken($second)->getJson('/api/v1/ciudadano/perfil')->assertOk();
    }

    public function test_user_token_cannot_access_citizen_routes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-user')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/ciudadano/perfil')->assertForbidden();
    }

    public function test_employee_token_cannot_access_citizen_routes(): void
    {
        $employeeType = EmployeeType::create(['name' => 'Conductor', 'description' => 'Conductor de prueba']);
        $employee = Employee::create([
            'employee_type_id' => $employeeType->id, 'dni' => '87654321', 'first_name' => 'Luis',
            'last_name' => 'Rojas', 'birthdate' => '1990-01-01', 'email' => 'driver@example.com',
            'password' => Hash::make('password123'), 'status' => 'Activo',
        ]);
        $token = $employee->createToken('flutter-driver')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/ciudadano/perfil')->assertForbidden();
    }

    public function test_citizen_token_cannot_access_driver_routes(): void
    {
        $citizen = Citizen::create([
            'dni' => '12345678', 'first_name' => 'Ana', 'last_name' => 'Pérez',
            'email' => 'ana@example.com', 'password' => Hash::make('password123'),
        ]);
        $token = $citizen->createToken('flutter-citizen')->plainTextToken;

        $this->withToken($token)->getJson('/api/conductor/programaciones')->assertForbidden();
    }

    public function test_user_token_can_access_generic_user_endpoint(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-user')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_citizen_token_cannot_access_generic_user_endpoint(): void
    {
        $citizen = Citizen::create([
            'dni' => '12345678', 'first_name' => 'Ana', 'last_name' => 'Pérez',
            'email' => 'ana@example.com', 'password' => Hash::make('password123'),
        ]);
        $token = $citizen->createToken('flutter-citizen')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertForbidden();
    }

    public function test_employee_token_cannot_access_generic_user_endpoint(): void
    {
        $employeeType = EmployeeType::create(['name' => 'Conductor', 'description' => 'Conductor de prueba']);
        $employee = Employee::create([
            'employee_type_id' => $employeeType->id, 'dni' => '87654321', 'first_name' => 'Luis',
            'last_name' => 'Rojas', 'birthdate' => '1990-01-01', 'email' => 'driver@example.com',
            'password' => Hash::make('password123'), 'status' => 'Activo',
        ]);
        $token = $employee->createToken('flutter-driver')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertForbidden();
    }
}
