<?php declare(strict_types=1);

namespace PgTenancy\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tenant model representing a tenant record in the application.
 *
 * @property int $id The unique identifier of the tenant
 * @property string $slug The URL-friendly identifier for the tenant
 * @property string $schema The database schema name for this tenant
 * @property string $db_username The database username for tenant connections
 * @property string $db_password The encrypted database password for tenant connections
 * @property string|null $domain The custom domain name for this tenant (optional)
 * @property int|null $team_id The ID of the associated team (optional)
 * @property \Illuminate\Support\Carbon|null $created_at When the tenant was created
 * @property \Illuminate\Support\Carbon|null $updated_at When the tenant was last updated
 *
 * @property-read \PgTenancy\Models\Team|null $team The team this tenant belongs to
 */
class Tenant extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tenants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'schema',
        'db_username',
        'db_password',
        'domain',
        'team_id',
    ];
}

