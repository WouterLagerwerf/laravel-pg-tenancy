<?php

namespace PgTenancy\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use PgTenancy\PgTenancyServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [PgTenancyServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $key = \Illuminate\Encryption\Encrypter::generateKey($app['config']->get('app.cipher'));
        $app['config']->set('app.key', 'base64:'.base64_encode($key));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('tenancy.base_connection', 'testing');
        $app['config']->set('tenancy.system_connection', 'testing');
        $app['config']->set('tenancy.base_domain', 'example.com');
    }
}


