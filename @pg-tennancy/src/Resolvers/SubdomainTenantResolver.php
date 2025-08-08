<?php declare(strict_types=1);

namespace PgTenancy\Resolvers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use PgTenancy\Contracts\TenantResolver;
use PgTenancy\Models\Tenant;

/**
 * Resolves tenants based on the subdomain of the incoming request.
 * 
 * This resolver extracts the tenant identifier from the subdomain portion of the hostname
 * and looks up the corresponding tenant by either matching the slug or full domain.
 * 
 * For example:
 * - tenant-a.example.com -> resolves tenant with slug 'tenant-a'
 * - custom.domain.com -> resolves tenant with domain 'custom.domain.com'
 */
class SubdomainTenantResolver implements TenantResolver
{
    /**
     * Create a new subdomain tenant resolver instance.
     *
     * @param ConfigRepository $config The config repository for accessing tenant settings
     */
    public function __construct(protected ConfigRepository $config) {}

    /**
     * Resolve the tenant from the request subdomain.
     *
     * Extracts the subdomain from the hostname by removing the configured base domain,
     * then looks up a tenant by either matching the subdomain against the slug or
     * the full hostname against the domain.
     *
     * @param Request $request The incoming HTTP request
     * @return Tenant|null The resolved tenant or null if not found
     */
    public function resolve(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $baseDomain = $this->config->get('tenancy.base_domain');

        if (!$baseDomain) {
            return null;
        }

        if (!str_ends_with($host, $baseDomain)) {
            return null;
        }

        $left = rtrim(substr($host, 0, -strlen($baseDomain)), '.');
        if ($left === '') {
            return null;
        }

        $labels = explode('.', $left);
        $slug = $labels[count($labels) - 1];

        return Tenant::where('slug', $slug)->orWhere('domain', $host)->first();
    }
}
