<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard & Viewer Route Prefix
    |--------------------------------------------------------------------------
    |
    | The route prefix for accessing the cloner dashboard and site viewer.
    | Dashboard: /{route_prefix}
    | Viewer:    /{route_prefix}/view/{site_id}/{path?}
    |
    */
    'route_prefix' => 'site-cloner',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the dashboard and viewer routes.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Sites Storage Directory
    |--------------------------------------------------------------------------
    |
    | Absolute or relative path where cloned sites and manifests will be stored.
    |
    */
    'storage_path' => storage_path('app/site-cloner/sites'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => 30,

    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',

    /*
    |--------------------------------------------------------------------------
    | Asset Handling
    |--------------------------------------------------------------------------
    |
    | Domains or hosts that should not be downloaded and kept as external links
    | (e.g. Google Fonts, unpkg, cdnjs, etc.).
    |
    */
    'external_cdn_hosts' => [
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'fonts.bunny.net',
        'bunny.net',
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of HTML pages to download per site (null for unlimited).
    |
    */
    'max_pages' => 500,

    /*
    |--------------------------------------------------------------------------
    | Agent Skills Target Path
    |--------------------------------------------------------------------------
    */
    'skills_path' => '.agents/skills',
];
