<?php

namespace PgTenancy\Tests;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Crypt;
use Mockery as m;
use PgTenancy\Models\Tenant;
use PgTenancy\Support\PostgresSchemaManager;

class SchemaManagerTest extends TestCase
{
    public function test_create_for_tenant_executes_expected_sql()
    {
        $tenant = new Tenant([
            'slug' => 'acme',
        ]);

        $pdo = m::mock(\PDO::class);
        // create role + create schema + grant usage + alter default privs (tables) + alter default privs (sequences)
        $pdo->shouldReceive('exec')->times(5);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getPdo')->andReturn($pdo);

        $db = m::mock(DatabaseManager::class);
        $db->shouldReceive('connection')->andReturn($connection);

        $manager = new PostgresSchemaManager($db, $this->app['config']);
        $plain = $manager->createForTenant($tenant, 'secret');

        $this->assertEquals('secret', $plain);
        $this->assertNotEmpty($tenant->schema);
        $this->assertNotEmpty($tenant->db_username);
        $this->assertNotEmpty($tenant->db_password);
    }
}


