<?php

namespace Tests\Feature;

use App\Models\admin\Contract;
use Database\Seeders\ContractEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContractEmployeesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_workers_without_contracts_and_can_be_repeated_safely(): void
    {
        $this->seed(ContractEmployeesSeeder::class);
        $this->assertDatabaseCount('employees', 10);
        $this->assertDatabaseCount('employee_types', 2);
        $this->assertDatabaseCount('contracts', 0);
        $worker = DB::table('employees')->where('dni', '99001001')->first();
        $this->assertTrue(Hash::check('password', $worker->password));
        $contract = Contract::create([
            'employee_id' => $worker->id, 'type' => 'Nombrado', 'start_date' => '2026-10-12',
            'end_date' => null, 'salary' => 1500,
        ]);
        DB::table('employees')->where('id', $worker->id)->update(['first_name' => 'Nombre editado']);

        $this->seed(ContractEmployeesSeeder::class);

        $this->assertDatabaseCount('employees', 10);
        $this->assertDatabaseCount('employee_types', 2);
        $this->assertDatabaseHas('employees', ['id' => $worker->id, 'first_name' => 'Nombre editado', 'password' => $worker->password]);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'employee_id' => $worker->id]);
    }

    public function test_seeder_does_not_replace_an_existing_worker_with_a_matching_dni(): void
    {
        $typeId = DB::table('employee_types')->insertGetId(['name' => 'Conductor', 'description' => 'Conservar descripción']);
        $workerId = DB::table('employees')->insertGetId([
            'employee_type_id' => $typeId, 'dni' => '99001001', 'first_name' => 'Existente',
            'last_name' => 'Original', 'birthdate' => '1980-01-01', 'email' => 'original@example.test',
            'password' => Hash::make('original-password'),
        ]);

        $this->seed(ContractEmployeesSeeder::class);

        $this->assertDatabaseCount('employees', 10);
        $this->assertDatabaseHas('employees', ['id' => $workerId, 'first_name' => 'Existente', 'email' => 'original@example.test']);
        $this->assertDatabaseHas('employee_types', ['id' => $typeId, 'description' => 'Conservar descripción']);
    }
}
