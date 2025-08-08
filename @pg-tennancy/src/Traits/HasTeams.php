<?php declare(strict_types=1);

namespace PgTenancy\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PgTenancy\Models\Team;

/**
 * Trait HasTeams
 * 
 * Provides team relationship functionality for User models.
 * Allows users to belong to multiple teams and have a current active team.
 */
trait HasTeams
{
    /**
     * Get all teams that the user belongs to.
     * 
     * Establishes a many-to-many relationship between users and teams using a configurable pivot table.
     * The pivot table stores additional attributes like role and ownership status.
     *
     * @return BelongsToMany The teams relationship with pivot data
     */
    public function teams(): BelongsToMany
    {
        $pivot = config('tenancy.team.pivot_table', 'team_user');
        return $this->belongsToMany(Team::class, $pivot)
            ->withPivot(['role', 'is_owner'])
            ->withTimestamps();
    }

    /**
     * Get the user's current active team.
     * 
     * Establishes a belongs-to relationship to track which team is currently active for the user.
     * The current team is referenced by the current_team_id foreign key.
     *
     * @return BelongsTo The current team relationship
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }
}
