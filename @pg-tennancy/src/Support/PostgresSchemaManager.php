<?php declare(strict_types=1);

namespace PgTenancy\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PgTenancy\Models\Tenant;

/**
 * Create and drop PostgreSQL schemas and roles for tenant isolation.
 * 
 * This class manages the lifecycle of PostgreSQL schemas and roles for multi-tenant database isolation.
 * It handles creating and dropping schemas, managing role permissions, and generating secure credentials.
 */
class PostgresSchemaManager
{
    /**
     * Create a new PostgreSQL schema manager instance.
     *
     * @param DatabaseManager $db The database manager instance
     * @param ConfigRepository $config The config repository instance
     */
    public function __construct(
        protected DatabaseManager $db,
        protected ConfigRepository $config,
    ) {}

    /**
     * Get the connection name with privileges to create schema/roles.
     *
     * @return string The system database connection name from config
     */
    protected function systemConnectionName(): string
    {
        return $this->config->get('tenancy.system_connection', 'pgsql');
    }

    /**
     * Create schema and role for tenant; set encrypted password on model.
     *
     * Creates a new PostgreSQL schema and role for the tenant with appropriate permissions.
     * Generates secure credentials and updates the tenant model with encrypted values.
     *
     * @param Tenant $tenant The tenant model to create schema/role for
     * @param string|null $plainPassword Optional password to use instead of generating one
     * @return string The plain password generated or provided
     */
    public function createForTenant(Tenant $tenant, ?string $plainPassword = null): string
    {
        $schema = $tenant->schema ?: $this->generateSchemaName($tenant->slug);
        $username = $tenant->db_username ?: $this->generateUserName($tenant->slug);
        $password = $plainPassword ?: Str::random(32);

        $tenant->schema = $schema;
        $tenant->db_username = $username;
        $tenant->db_password = Crypt::encryptString($password);

        $conn = $this->db->connection($this->systemConnectionName());
        $pdo = $conn->getPdo();

        $pdo->exec("CREATE ROLE \"$username\" WITH LOGIN PASSWORD '".str_replace("'", "''", $password)."' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOREPLICATION;");
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS \"$schema\" AUTHORIZATION \"$username\";");
        $pdo->exec("GRANT USAGE ON SCHEMA \"$schema\" TO \"$username\";");
        $pdo->exec("ALTER DEFAULT PRIVILEGES IN SCHEMA \"$schema\" GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO \"$username\";");
        $pdo->exec("ALTER DEFAULT PRIVILEGES IN SCHEMA \"$schema\" GRANT USAGE, SELECT, UPDATE ON SEQUENCES TO \"$username\";");

        return $password;
    }

    /**
     * Drop schema and role for the tenant.
     *
     * Removes the PostgreSQL schema and role associated with the tenant.
     * Handles reassignment of owned objects before dropping to prevent orphaned objects.
     *
     * @param Tenant $tenant The tenant model whose schema/role should be dropped
     * @return void
     */
    public function dropForTenant(Tenant $tenant): void
    {
        $schema = $tenant->schema;
        $username = $tenant->db_username;

        $conn = $this->db->connection($this->systemConnectionName());
        $pdo = $conn->getPdo();

        $pdo->exec("REASSIGN OWNED BY \"$username\" TO CURRENT_USER;");
        $pdo->exec("DROP OWNED BY \"$username\";");
        $pdo->exec("DROP SCHEMA IF EXISTS \"$schema\" CASCADE;");
        $pdo->exec("DROP ROLE IF EXISTS \"$username\";");
    }

    /**
     * Generate a safe schema name from slug.
     *
     * Creates a PostgreSQL-safe schema name by sanitizing the tenant slug.
     * Prefixes with 't_' and limits length to 60 characters.
     *
     * @param string $slug The tenant slug to generate schema name from
     * @return string The sanitized schema name
     */
    public function generateSchemaName(string $slug): string
    {
        $base = 't_'.preg_replace('/[^a-z0-9_]+/i', '_', strtolower($slug));
        return substr($base, 0, 60);
    }

    /**
     * Generate a safe role name from slug.
     *
     * Creates a PostgreSQL-safe role name by sanitizing the tenant slug.
     * Prefixes with 'u_' and limits length to 60 characters.
     *
     * @param string $slug The tenant slug to generate role name from
     * @return string The sanitized role name
     */
    public function generateUserName(string $slug): string
    {
        $base = 'u_'.preg_replace('/[^a-z0-9_]+/i', '_', strtolower($slug));
        return substr($base, 0, 60);
    }
}