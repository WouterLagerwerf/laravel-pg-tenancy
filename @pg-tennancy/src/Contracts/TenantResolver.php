<?php declare(strict_types=1);

namespace PgTenancy\Contracts;

use Illuminate\Http\Request;
use PgTenancy\Models\Tenant;

/**
 * Resolve the current tenant from an incoming HTTP request.
 */
interface TenantResolver
{
    /**
     * Determine the tenant for the given request.
     *
     * @param Request $request
     * @return Tenant|null Returns the resolved tenant or null if not resolvable.
     */
    public function resolve(Request $request): ?Tenant;
}


