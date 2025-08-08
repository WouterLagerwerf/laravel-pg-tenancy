<?php declare(strict_types=1);

namespace PgTenancy\Resolvers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PgTenancy\Contracts\TenantResolver;
use PgTenancy\Models\Tenant;

/**
 * Resolves tenants based on the authenticated user's current team relationship.
 * 
 * This resolver looks up the tenant associated with the authenticated user's current team.
 * The team relationship name is configurable via the 'tenancy.team.user_current_team_relationship'
 * config value (defaults to 'currentTeam').
 *
 * For example:
 * - Authenticated user with current team ID 1 -> resolves tenant with team_id 1
 * - Unauthenticated user -> resolves to null
 * - Authenticated user with no current team -> resolves to null
 */
class TeamTenantResolver implements TenantResolver
{
    /**
     * Create a new team tenant resolver instance.
     *
     * @param ConfigRepository $config The config repository for accessing tenant settings
     */
    public function __construct(protected ConfigRepository $config) {}

    /**
     * Resolve the tenant from the authenticated user's current team.
     *
     * Looks up the tenant record associated with the authenticated user's current team.
     * Returns null if the user is not authenticated, has no current team, or no tenant
     * exists for the team.
     *
     * @param Request $request The incoming HTTP request
     * @return Tenant|null The resolved tenant or null if not found
     */
    public function resolve(Request $request): ?Tenant
    {
        $relation = $this->config->get('tenancy.team.user_current_team_relationship', 'currentTeam');
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        $team = $user->{$relation} ?? null;
        if (!$team) {
            return null;
        }
        return Tenant::where('team_id', $team->getKey())->first();
    }
}
