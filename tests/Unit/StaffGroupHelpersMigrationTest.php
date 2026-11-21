<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class StaffGroupHelpersMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_table_is_reconciled_without_losing_rows(): void
    {
        $this->assertTrue(Schema::hasTable('staff_group_helpers'));

        $before = DB::table('staff_group_helpers')->count();
        DB::statement('DROP INDEX staff_group_helpers_staff_group_id_employee_id_unique');

        $migration = require base_path('database/migrations/2026_11_14_140000_create_staff_group_helpers_table.php');
        $migration->up();

        $this->assertSame($before, DB::table('staff_group_helpers')->count());
        $this->assertTrue(Schema::hasIndex(
            'staff_group_helpers',
            ['staff_group_id', 'employee_id'],
            'unique'
        ));
    }

    public function test_duplicate_pairs_are_rejected_before_adding_unique_constraint(): void
    {
        Schema::drop('staff_group_helpers');
        Schema::create('staff_group_helpers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('staff_group_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamps();
        });

        DB::table('staff_group_helpers')->insert([
            ['staff_group_id' => 1, 'employee_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['staff_group_id' => 1, 'employee_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration = require base_path('database/migrations/2026_11_14_140000_create_staff_group_helpers_table.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pares staff_group_id/employee_id duplicados');
        $migration->up();
    }

    public function test_down_does_not_drop_the_table(): void
    {
        $migration = require base_path('database/migrations/2026_11_14_140000_create_staff_group_helpers_table.php');
        $migration->down();

        $this->assertTrue(Schema::hasTable('staff_group_helpers'));
    }
}
