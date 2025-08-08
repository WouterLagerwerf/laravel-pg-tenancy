<?php

namespace PgTenancy\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PgTenancy\Contracts\TenantResolver;
use PgTenancy\Models\Tenant;

class TenantResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('tenants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug')->unique();
            $table->string('schema')->unique();
            $table->string('db_username')->unique();
            $table->text('db_password');
            $table->string('domain')->nullable()->unique();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function test_subdomain_resolution()
    {
        Tenant::create([
            'slug' => 'acme',
            'schema' => 't_acme',
            'db_username' => 'u_acme',
            'db_password' => 'encrypted',
            'domain' => 'acme.example.com',
        ]);

        $this->app['config']->set('tenancy.mode', 'subdomain');
        $resolver = $this->app->make(TenantResolver::class);
        $request = \Illuminate\Http\Request::create('http://acme.example.com');
        $tenant = $resolver->resolve($request);
        $this->assertNotNull($tenant);
        $this->assertEquals('acme', $tenant->slug);
    }

    public function test_path_resolution()
    {
        Tenant::create([
            'slug' => 'beta',
            'schema' => 't_beta',
            'db_username' => 'u_beta',
            'db_password' => 'encrypted',
        ]);

        $this->app['config']->set('tenancy.mode', 'path');
        $resolver = $this->app->make(TenantResolver::class);
        $request = \Illuminate\Http\Request::create('http://example.com/beta/dashboard');
        $tenant = $resolver->resolve($request);
        $this->assertNotNull($tenant);
        $this->assertEquals('beta', $tenant->slug);
    }
}


