<?php declare(strict_types=1);

namespace PgTenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Team model representing a tenant team in the application.
 *
 * @property int $id The unique identifier of the team
 * @property string $name The name of the team
 * @property string|null $slug The URL-friendly slug for the team
 * @property int|null $owner_id The ID of the team owner user
 * @property \Illuminate\Support\Carbon|null $created_at When the team was created
 * @property \Illuminate\Support\Carbon|null $updated_at When the team was last updated
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users The users belonging to this team
 * @property-read \App\Models\User|null $owner The owner of this team
 */
class Team extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'teams';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'owner_id',
    ];

    /**
     * Get the users that belong to the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        $userModel = config('tenancy.team.user_model', 'App\\Models\\User');
        $pivot = config('tenancy.team.pivot_table', 'team_user');
        return $this->belongsToMany($userModel, $pivot)
            ->withPivot(['role', 'is_owner'])
            ->withTimestamps();
    }

    /**
     * Get the owner of the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner(): BelongsTo
    {
        $userModel = config('tenancy.team.user_model', 'App\\Models\\User');
        return $this->belongsTo($userModel, 'owner_id');
    }

    /**
     * Create a new team and assign the given user as owner.
     *
     * @param string $name The name of the team
     * @param object $user The user to set as owner
     * @param array<string,mixed> $attributes Additional attributes for the team
     * @return self The newly created team instance
     */
    public static function createForUser(string $name, object $user, array $attributes = []): self
    {
        $team = new self([
            'name' => $name,
            'slug' => $attributes['slug'] ?? \Illuminate\Support\Str::slug($name),
            'owner_id' => $user->getKey(),
        ]);
        $team->save();
        $team->users()->attach($user->getKey(), ['role' => 'owner', 'is_owner' => true]);

        if (method_exists($user, 'currentTeam')) {
            $user->current_team_id = $team->getKey();
            $user->save();
        }

        return $team;
    }
}
