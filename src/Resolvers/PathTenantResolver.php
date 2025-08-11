<?php declare(strict_types=1);

namespace PgTenancy\Resolvers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use PgTenancy\Contracts\TenantResolver;
use PgTenancy\Models\Tenant;

/**
 * Resolves tenants based on a configurable path segment in the URL.
 * 
 * This resolver extracts the tenant slug from a specified segment of the URL path
 * and looks up the corresponding tenant. The segment index is configurable via
 * the 'tenancy.path_segment_index' config value (defaults to 1).
 *
 * For example, with default index of 1:
 * - /tenant-a/some/path -> resolves tenant with slug 'tenant-a'
 * - /some/tenant-b/path -> resolves tenant with slug 'some'
 */
class PathTenantResolver implements TenantResolver
{
    /**
     * Create a new path tenant resolver instance.
     *
     * @param ConfigRepository $config The config repository for accessing tenant settings
     */
    public function __construct(protected ConfigRepository $config) {}

    /**
     * Resolve the tenant from the request path.
     *
     * Extracts the tenant slug from the configured path segment index and
     * looks up the corresponding tenant record.
     *
     * @param Request $request The incoming HTTP request
     * @return Tenant|null The resolved tenant or null if not found
     */
    public function resolve(Request $request): ?Tenant
    {
        $index = (int) $this->config->get('tenancy.path_segment_index', 1);
        $segment = trim($request->path(), '/');
        if ($segment === '') {
            return null;
        }
        $parts = explode('/', $segment);
        $slug = $parts[$index - 1] ?? null;
        if (!$slug) {
            return null;
        }
        return Tenant::where('slug', $slug)->first();
    }
}

