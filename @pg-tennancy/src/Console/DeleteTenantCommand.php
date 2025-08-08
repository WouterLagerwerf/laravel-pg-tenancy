<?php declare(strict_types=1);

namespace PgTenancy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use PgTenancy\Events\TenantDeleted;
use PgTenancy\Models\Tenant;
use PgTenancy\Support\PostgresSchemaManager;

/**
 * CLI command to delete a tenant and drop its schema/role.
 *
 * This command deletes an existing tenant and removes its dedicated PostgreSQL schema and role.
 * It handles tenant lookup by ID or slug, confirmation prompts, and cleanup of database resources.
 *
 * @property string $signature Command signature with arguments and options
 * @property string $description Command description
 */
class DeleteTenantCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'tenancy:tenant:delete {id-or-slug} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a tenant and drop its PostgreSQL schema and role';

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
        $idOrSlug = $this->argument('id-or-slug');

        $tenant = Tenant::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->first();
        if (!$tenant) {
            $this->error('Tenant not found.');
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('This will drop schema and role. Continue?')) {
            return self::INVALID;
        }

        $schemaManager->dropForTenant($tenant);
        $tenant->delete();

        Event::dispatch(new TenantDeleted($tenant));
        $this->info('Tenant deleted.');
        return self::SUCCESS;
    }
}
