<?php

namespace Tests\Unit;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class EnforceActiveCitizenZoneAssignmentMigrationTest extends TestCase
{
    public function test_mysql_version_validation_works_during_pretend_without_selecting_version(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getServerVersion')->once()->andReturn('8.0.30');

        DB::shouldReceive('connection')->once()->andReturn($connection);
        DB::shouldReceive('selectOne')->never();

        $migration = require base_path('database/migrations/2026_11_20_110000_enforce_active_citizen_zone_assignment.php');
        $method = new ReflectionMethod($migration, 'validateMysqlVersion');
        $method->invoke($migration);

        $this->assertTrue(true);
    }

    public function test_mysql_version_validation_rejects_unsupported_versions(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getServerVersion')->once()->andReturn('5.6.51');

        DB::shouldReceive('connection')->once()->andReturn($connection);

        $migration = require base_path('database/migrations/2026_11_20_110000_enforce_active_citizen_zone_assignment.php');
        $method = new ReflectionMethod($migration, 'validateMysqlVersion');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MySQL 5.7 o superior');
        $method->invoke($migration);
    }
}
