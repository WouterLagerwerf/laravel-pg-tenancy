<?php declare(strict_types=1);

namespace PgTenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PgTenancy\Contracts\TenantResolver;
use PgTenancy\Support\TenancyManager;

/**
 * Middleware that identifies and initializes the current tenant.
 * 
 * This middleware resolves the tenant from the incoming request and initializes
 * the tenancy context. If no tenant is resolved and forbid_unresolved config is true,
 * it will abort with a 404. Otherwise it continues with the landlord connection.
 */
class IdentifyTenant
{
    /**
     * Create a new IdentifyTenant middleware instance.
     *
     * @param TenantResolver $tenantResolver The service to resolve tenants from requests
     * @param TenancyManager $tenancyManager The service to manage tenant initialization
     */
    public function __construct(
        protected TenantResolver $tenantResolver,
        protected TenancyManager $tenancyManager
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware/handler in the pipeline
     * @return mixed The response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException When tenant not found and forbid_unresolved is true
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->tenantResolver->resolve($request);

        if ($tenant) {
            $this->tenancyManager->initializeForTenant($tenant);
        } else {
            if (config('tenancy.forbid_unresolved', false)) {
                abort(404);
            }
            // No tenant found; continue with landlord connection
        }

        return $next($request);
    }
}

