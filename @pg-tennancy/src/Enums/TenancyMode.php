<?php declare(strict_types=1);

namespace PgTenancy\Enums;

/**
 * Tenancy modes supported by the package.
 * 
 * This enum defines the different ways tenants can be identified and isolated:
 * - Subdomain: Tenants are identified by subdomain (e.g. tenant1.example.com)
 * - Path: Tenants are identified by URL path prefix (e.g. example.com/tenant1)
 * - Team: Tenants are identified by team membership
 */
enum TenancyMode: string
{
    /**
     * Tenant is identified by subdomain (e.g. tenant1.example.com)
     */
    case SUBDOMAIN = 'subdomain';

    /**
     * Tenant is identified by URL path prefix (e.g. example.com/tenant1)
     */
    case PATH = 'path';

    /**
     * Tenant is identified by team membership
     */
    case TEAM = 'team';
}

