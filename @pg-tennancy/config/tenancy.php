<?php

return [
    // subdomain | path | team
    'mode' => env('TENANCY_MODE', 'team'),

    // Base connection to clone for tenants
    'base_connection' => env('TENANCY_BASE_CONNECTION', 'pgsql'),

    // Privileged connection used to create schemas/roles
    'system_connection' => env('TENANCY_SYSTEM_CONNECTION', 'pgsql'),

    // If true, abort 404 when tenant cannot be resolved
    'forbid_unresolved' => env('TENANCY_FORBID_UNRESOLVED', false),

    // Subdomain mode
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'example.com'),

    // Path mode
    'path_segment_index' => env('TENANCY_PATH_SEGMENT_INDEX', 1),

    // Team mode
    'team' => [
        'team_model' => env('TENANCY_TEAM_MODEL', \PgTenancy\Models\Team::class),
        'user_model' => env('TENANCY_USER_MODEL', 'App\\Models\\User'),
        'pivot_table' => env('TENANCY_TEAM_PIVOT', 'team_user'),
        'user_current_team_relationship' => env('TENANCY_USER_TEAM_REL', 'currentTeam'),
    ],
];


