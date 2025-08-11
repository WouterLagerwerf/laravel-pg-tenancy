# Laravel PG Tenancy

Multi-tenancy for Laravel with PostgreSQL schema isolation and connection pooling. Supports subdomain, path, or Team model based tenancy modes.

## Installation

1. Require the package (adjust VCS path if local):

```bash
composer require pg-tennancy/laravel-pg-tenancy
```

2. Publish config and migrations:

```bash
php artisan vendor:publish --provider="PgTenancy\\PgTenancyServiceProvider" --tag=tenancy-config
php artisan vendor:publish --provider="PgTenancy\\PgTenancyServiceProvider" --tag=tenancy-migrations
php artisan vendor:publish --provider="PgTenancy\\PgTenancyServiceProvider" --tag=tenancy-tenant-migrations
```

3. Configure `config/tenancy.php` and ensure a privileged `system_connection` can create schemas/roles.

4. Add middleware to HTTP kernel or route group:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        \PgTenancy\Http\Middleware\IdentifyTenant::class,
    ],
];
```

## Modes

- Subdomain: `tenant1.example.com` (configure `base_domain`)
- Path: `example.com/tenant1` (configure `path_segment_index`)
- Team: authenticated user's team (configure relation name)

## Schema Isolation

Each tenant gets its own PostgreSQL schema and role. The package sets the Postgres `search_path` to `{schema},public` on a per-request tenant connection.

## Commands

- `php artisan tenancy:tenant:create {slug} {--domain=} {--schema=} {--team-id=}`
- `php artisan tenancy:tenant:delete {id-or-slug}`
- `php artisan tenancy:migrate {--fresh} {--seed}`

## Tests

```bash
composer test
```

## License

MIT

## Programmatic Tenant Creation (Sign-up)

Preferred flow: create a User, then create a Team for that user. The package provisions the tenant schema via the Team observer and automatically runs tenant migrations.

```php
use PgTenancy\Models\Team;

// After creating $user
$team = Team::createForUser('Acme Inc', $user);
// A tenant record is created and schema is provisioned automatically (observer)
```

## Add tenancy to your registration flow (Livewire Volt example)

1) Ensure team-based tenancy mode:

```php
// config/tenancy.php
return [
    'mode' => 'team',
];
```

2) Register route middleware so tenancy resolves after auth:

```php
// bootstrap/app.php
->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware) {
    $middleware->alias([
        'tenant' => \PgTenancy\Http\Middleware\IdentifyTenant::class,
    ]);
})

// routes/web.php
Route::view('dashboard', 'dashboard')->middleware(['auth', 'tenant', 'verified']);
```

3) Extend your register component to accept a team name and create the team after user creation:

```php
// resources/views/livewire/auth/register.blade.php (excerpt)
use PgTenancy\\Models\\Team;

public string $team_name = '';

public function register(): void
{
    // ... validate + create $user + Auth::login($user)
    $teamName = trim($this->team_name) !== '' ? $this->team_name : ($user->name . "'s Team");
    Team::createForUser($teamName, $user);
    // redirect to dashboard
}

// Add an input to the form
<flux:input wire:model="team_name" :label="__('Team name')" type="text" autocomplete="organization" />
```

4) Show current tenant schema (optional):

```php
// resources/views/dashboard.blade.php (excerpt)
$schemaName = DB::selectOne('select current_schema() as schema')->schema ?? null;
```

Notes:
- On `TenantCreated`, the package runs tenant migrations in `database/migrations/tenant` on the tenant connection.
- Team slugs and tenant schemas are made unique automatically (even for duplicate team names).


