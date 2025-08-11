<?php declare(strict_types=1);

namespace PgTenancy;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use PgTenancy\Console\CreateTenantCommand;
use PgTenancy\Console\DeleteTenantCommand;
use PgTenancy\Console\MigrateTenantsCommand;
use PgTenancy\Contracts\TenantResolver as TenantResolverContract;
use PgTenancy\Resolvers\SubdomainTenantResolver;
use PgTenancy\Resolvers\PathTenantResolver;
use PgTenancy\Resolvers\TeamTenantResolver;
use PgTenancy\Support\TenancyManager;
use PgTenancy\Support\PostgresSchemaManager;
use PgTenancy\Observers\TeamObserver;
use PgTenancy\Models\Team;
use PgTenancy\Enums\TenancyMode;

/**
 * Service provider for the PgTenancy package.
 * 
 * This service provider handles the registration and bootstrapping of all PgTenancy
 * components including:
 * - Configuration merging and publishing
 * - Service container bindings for core services
 * - Artisan command registration
 * - Migration publishing
 * - Model observers
 * - Tenant resolver configuration based on tenancy mode
 */
class PgTenancyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * 
     * Handles binding of core services into the service container and merging of
     * package configuration. This includes:
     * - TenancyManager singleton for managing active tenant state
     * - PostgresSchemaManager singleton for schema/role management
     * - TenantResolver binding based on configured tenancy mode
     * - Registration of package Artisan commands
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tenancy.php', 'tenancy');

        $this->app->singleton(TenancyManager::class, function ($app) {
            return new TenancyManager($app);
        });

        $this->app->singleton(PostgresSchemaManager::class, function ($app) {
            return new PostgresSchemaManager($app['db'], $app['config']);
        });

        $this->app->bind(TenantResolverContract::class, function ($app) {
            $mode = TenancyMode::from($app['config']->get('tenancy.mode', TenancyMode::SUBDOMAIN->value));
            return match ($mode) {
                TenancyMode::SUBDOMAIN => new SubdomainTenantResolver($app['config']),
                TenancyMode::PATH => new PathTenantResolver($app['config']),
                TenancyMode::TEAM => new TeamTenantResolver($app['config']),
            };
        });

        $this->commands([
            CreateTenantCommand::class,
            DeleteTenantCommand::class,
            MigrateTenantsCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     * 
     * Handles publishing of package assets and configuration of runtime components:
     * - Publishes configuration file
     * - Publishes base tenant migrations
     * - Publishes tenant-specific migrations
     * - Publishes team-related migrations
     * - Registers team model observer
     *
     * @param Dispatcher $events The event dispatcher instance
     * @return void
     */
    public function boot(Dispatcher $events): void
    {
        $this->publishes([
            __DIR__.'/../config/tenancy.php' => config_path('tenancy.php'),
        ], 'tenancy-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_tenants_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_tenants_table.php'),
        ], 'tenancy-migrations');

        $this->publishes([
            __DIR__.'/../database/migrations/tenant' => database_path('migrations/tenant'),
        ], 'tenancy-tenant-migrations');

        $this->publishes([
            __DIR__.'/../database/migrations/create_teams_tables.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()+1).'_create_teams_tables.php'),
        ], 'tenancy-team-migrations');

        $teamModel = $this->app['config']->get('tenancy.team.team_model', Team::class);
        if (class_exists($teamModel)) {
            $teamModel::observe($this->app->make(TeamObserver::class));
        }
    }
}
