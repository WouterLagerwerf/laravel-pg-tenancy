<?php declare(strict_types=1);

namespace PgTenancy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PgTenancy\Models\Tenant;
use PgTenancy\Support\TenancyManager;

/**
 * CLI command to run tenant migrations for all tenants.
 *
 * This command executes database migrations for each tenant in the system.
 * It supports running fresh migrations, regular migrations, and optional seeding.
 *
 * @property string $signature Command signature with arguments and options
 * @property string $description Command description
 */
class MigrateTenantsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'tenancy:migrate {--fresh} {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tenant migrations for all tenants';

    /**
     * Execute the console command.
     *
     * @param TenancyManager $tenancyManager The tenancy manager service
     * @return int Command exit code
     *
     * @throws \Illuminate\Database\QueryException When database operations fail
     */
    public function handle(TenancyManager $tenancyManager): int
    {
        $tenants = Tenant::query()->get();
        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->slug}");
            $tenancyManager->initializeForTenant($tenant);

            if ($this->option('fresh')) {
                Artisan::call('migrate:fresh', [
                    '--database' => $tenancyManager->tenantConnectionName(),
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
            } else {
                Artisan::call('migrate', [
                    '--database' => $tenancyManager->tenantConnectionName(),
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
            }

            if ($this->option('seed')) {
                Artisan::call('db:seed', [
                    '--database' => $tenancyManager->tenantConnectionName(),
                    '--class' => 'DatabaseSeeder',
                    '--force' => true,
                ]);
            }
        }

        $this->info('Tenant migrations complete.');
        return self::SUCCESS;
    }
}
