<?php declare(strict_types=1);

namespace PgTenancy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use PgTenancy\Events\TenantCreated;
use PgTenancy\Models\Tenant;
use PgTenancy\Support\PostgresSchemaManager;

/**
 * CLI command to create a tenant and its schema/role.
 *
 * This command creates a new tenant with a dedicated PostgreSQL schema and role.
 * It handles validation, schema/role creation, and tenant record creation.
 *
 * @property string $signature Command signature with arguments and options
 * @property string $description Command description
 */
class CreateTenantCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'tenancy:tenant:create {slug} {--domain=} {--schema=} {--team-id=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with dedicated PostgreSQL schema and role';

    /**
     * Execute the console command.
     *
     * @param PostgresSchemaManager $schemaManager The schema manager service
     * @return int Command exit code
     *
     * @throws \Illuminate\Database\QueryException When database operations fail
     */
    public function handle(PostgresSchemaManager $schemaManager): int
    {
        $slug = $this->argument('slug');
        $domain = $this->option('domain');
        $schema = $this->option('schema');
        $teamId = $this->option('team-id');
        $password = $this->option('password');

        if (Tenant::where('slug', $slug)->exists()) {
            $this->error("Tenant '$slug' already exists.");
            return self::FAILURE;
        }

        $tenant = new Tenant([
            'slug' => $slug,
            'schema' => $schema,
            'domain' => $domain,
            'team_id' => $teamId,
        ]);

        $plain = $schemaManager->createForTenant($tenant, $password);
        $tenant->save();

        Event::dispatch(new TenantCreated($tenant));

        $this->info("Tenant created: {$tenant->slug}");
        $this->info("Schema: {$tenant->schema}");
        $this->info("DB Username: {$tenant->db_username}");
        $this->info("DB Password: {$plain}");

        return self::SUCCESS;
    }
}
