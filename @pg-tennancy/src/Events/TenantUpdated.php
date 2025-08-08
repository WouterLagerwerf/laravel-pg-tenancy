<?php declare(strict_types=1);

namespace PgTenancy\Events;

use PgTenancy\Models\Tenant;

/**
 * Event dispatched when a tenant is updated.
 */
class TenantUpdated
{
    public function __construct(public Tenant $tenant) {}
}


