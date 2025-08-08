<?php declare(strict_types=1);

namespace PgTenancy\Events;

use PgTenancy\Models\Tenant;

/**
 * Event dispatched after a tenant has been created and saved.
 */
class TenantCreated
{
    public function __construct(public Tenant $tenant) {}
}


