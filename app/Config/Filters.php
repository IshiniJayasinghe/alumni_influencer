<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Filter aliases
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
    ];

    /**
     * Always-required filters (framework-level).
     */
    public array $required = [
        'before' => [
            // 'forcehttps', // Disabled: breaks CSRF session/cookie on HTTP localhost
            'pagecache',
        ],
        'after' => [
            'pagecache',
            'performance',
            'toolbar',
        ],
    ];

    /**
     * Global filters applied to every request.
     *
     * CSRF is enabled on all POST/PUT/PATCH/DELETE requests.
     * The API endpoint (/api/*), cron endpoint, and openapi.json are exempt
     * because they are consumed by external clients that cannot send CSRF tokens.
     *
     * SecureHeaders adds X-Frame-Options, X-Content-Type-Options,
     * X-XSS-Protection, Referrer-Policy etc. to every response.
     */
    public array $globals = [
        'before' => [
            'csrf' => ['except' => ['api/*', 'cron/*', 'openapi.json']],
        ],
        'after' => [
            'secureheaders',
        ],
    ];

    public array $methods = [];

    public array $filters = [];
}
