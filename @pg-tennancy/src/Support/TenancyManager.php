<?php declare(strict_types=1);

namespace PgTenancy\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PgTenancy\Models\Tenant;

/**
 * Manage the active tenant and configure the tenant database connection.
 * 
 * This class handles setting and clearing the current tenant context, as well as
 * configuring the tenant-specific database connection with proper schema isolation.
 * It manages the lifecycle of tenant connections and ensures proper cleanup.
 */
class TenancyManager
{
    /**
     * Default connection name used for tenant database connections.
     */
    public const DEFAULT_TENANT_CONNECTION = 'tenant';

    /**
     * The currently active tenant instance, if any.
     *
     * @var Tenant|null
     */
    protected ?Tenant $currentTenant = null;

    /**
     * Create a new tenancy manager instance.
     *
     * @param Application $app The Laravel application instance
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Get the current tenant, if any.
     *
     * @return Tenant|null The currently active tenant or null if none set
     */
    public function current(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Initialize application context and DB connection for the tenant.
     * 
     * Sets up the tenant-specific database connection with proper schema isolation
     * and stores the tenant as the current active tenant.
     *
     * @param Tenant $tenant The tenant to initialize context for
     * @return void
     */
    public function initializeForTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        $this->configureTenantConnection($tenant);
    }

    /**
     * Clear the current tenant and purge the tenant connection.
     * 
     * Removes the current tenant context and cleans up the tenant database connection.
     * This ensures no tenant-specific data remains in memory.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->currentTenant = null;
        DB::purge($this->tenantConnectionName());
    }

    /**
     * Get the tenant connection name used in the application's DB config.
     *
     * @return string The configured tenant connection name
     */
    public function tenantConnectionName(): string
    {
        return self::DEFAULT_TENANT_CONNECTION;
    }

    /**
     * Configure the tenant connection using search_path for schema isolation.
     * 
     * Sets up a tenant-specific database connection by copying the base connection config
     * and applying tenant credentials and schema search path. This ensures proper data
     * isolation between tenants.
     *
     * @param Tenant $tenant The tenant to configure connection for
     * @return void
     * @throws \RuntimeException When base connection is not configured
     */
    protected function configureTenantConnection(Tenant $tenant): void
    {
        $baseConnection = Config::get('tenancy.base_connection', 'pgsql');
        $config = Config::get("database.connections.$baseConnection");

        if (!$config) {
            throw new \RuntimeException("Base connection '$baseConnection' is not configured.");
        }

        $schema = $tenant->schema;
        $username = $tenant->db_username;
        $password = Crypt::decryptString($tenant->db_password);

        $tenantConfig = $config;
        $tenantConfig['username'] = $username;
        $tenantConfig['password'] = $password;
        $tenantConfig['options'] = ($tenantConfig['options'] ?? '') . ' -c search_path=' . $schema . ',public';

        Config::set('database.connections.'.$this->tenantConnectionName(), $tenantConfig);
        DB::purge($this->tenantConnectionName());
        DB::connection($this->tenantConnectionName())->reconnect();
    }
}
