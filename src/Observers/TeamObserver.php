<?php declare(strict_types=1);

namespace PgTenancy\Observers;

use Illuminate\Contracts\Events\Dispatcher;
use PgTenancy\Events\TenantCreated;
use PgTenancy\Events\TenantDeleted;
use PgTenancy\Events\TenantUpdated;
use PgTenancy\Models\Team;
use PgTenancy\Models\Tenant;
use PgTenancy\Support\PostgresSchemaManager;

/**
 * Observer that keeps tenant and schema lifecycle in sync with teams.
 * 
 * This observer handles the creation, updating and deletion of tenants and their 
 * associated database schemas when team model events occur.
 */
class TeamObserver
{
    /**
     * Create a new team observer instance.
     *
     * @param PostgresSchemaManager $schemaManager Manager for tenant schema operations
     * @param Dispatcher $events Event dispatcher for tenant lifecycle events
     */
    public function __construct(
        protected PostgresSchemaManager $schemaManager,
        protected Dispatcher $events
    ) {}

    /**
     * Handle the Team "created" event.
     *
     * Creates a new tenant and database schema for the team if one doesn't already exist.
     * Dispatches a TenantCreated event after successful creation.
     *
     * @param Team $team The team that was created
     * @return void
     */
    public function created(Team $team): void
    {
        $existing = Tenant::where('team_id', $team->getKey())->first();
        if ($existing) {
            return;
        }

        $tenant = new Tenant([
            'slug' => $team->slug ?: (string) $team->getKey(),
            'team_id' => $team->getKey(),
        ]);

        $plain = $this->schemaManager->createForTenant($tenant);
        $tenant->save();
        $this->events->dispatch(new TenantCreated($tenant));
    }

    /**
     * Handle the Team "updated" event.
     *
     * Finds the associated tenant and dispatches a TenantUpdated event.
     * Does not modify the schema or role names by default.
     *
     * @param Team $team The team that was updated
     * @return void
     */
    public function updated(Team $team): void
    {
        $tenant = Tenant::where('team_id', $team->getKey())->first();
        if (!$tenant) {
            return;
        }
        $this->events->dispatch(new TenantUpdated($tenant));
    }

    /**
     * Handle the Team "deleted" event.
     *
     * Drops the associated tenant's database schema and deletes the tenant record.
     * Dispatches a TenantDeleted event after successful deletion.
     *
     * @param Team $team The team that was deleted
     * @return void
     */
    public function deleted(Team $team): void
    {
        $tenant = Tenant::where('team_id', $team->getKey())->first();
        if (!$tenant) {
            return;
        }
        $this->schemaManager->dropForTenant($tenant);
        $tenant->delete();
        $this->events->dispatch(new TenantDeleted($tenant));
    }
}
