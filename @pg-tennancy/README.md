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

Each tenant gets its own PostgreSQL schema and role. Connection uses `options = '-c search_path={schema},public'` to be PgBouncer-friendly.

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

Preferred flow: create a User, then create a Team for that user. The package will provision the tenant schema via the Team observer.

```php
use PgTenancy\Models\Team;

// After creating $user
$team = Team::createForUser('Acme Inc', $user);
// A tenant record is created and schema is provisioned automatically (observer)
```


