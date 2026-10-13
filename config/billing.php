<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    | Set BILLING_ENABLED=false to bypass all license checks (e.g. local dev).
    */
    'enabled' => env('BILLING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Central billing server
    |--------------------------------------------------------------------------
    */
    'server_url' => env('BILLING_SERVER_URL', 'https://billing.techghor.com.bd'),

    'endpoint' => '/api/v2/customer/billing-overview',

    // Customer API Key, Recurring Group Key, or Invoice Payment Hash (sent as a Bearer token).
    'api_key' => env('BILLING_API_KEY'),

    // 'customer' = Customer API Key (X-Customer-Api-Key), 'recurring' = Recurring Group Key (X-Recurring-Group-Key), 'invoice' = Invoice Payment Hash (X-Payment-Hash).
    'key_type' => env('BILLING_KEY_TYPE', 'customer'),

    /*
    |--------------------------------------------------------------------------
    | HTTP behaviour
    |--------------------------------------------------------------------------
    */
    'timeout'         => (float) env('BILLING_TIMEOUT', 8),
    'connect_timeout' => (float) env('BILLING_CONNECT_TIMEOUT', 4),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    | cache_ttl           Seconds a successful response is served from cache.
    | stale_ttl           Seconds the last known good response is kept as a
    |                     fallback when the central server is unreachable.
    | failure_backoff     Seconds to wait before retrying after a failed call,
    |                     so downtime never slows down every request.
    */
    'cache_ttl'       => (int) env('BILLING_CACHE_TTL', 3600),
    'stale_ttl'       => (int) env('BILLING_STALE_TTL', 604800),
    'failure_backoff' => (int) env('BILLING_FAILURE_BACKOFF', 60),
    'cache_key'       => 'techghor_billing_overview',

    /*
    |--------------------------------------------------------------------------
    | License rules
    |--------------------------------------------------------------------------
    */
    // Extra days of access after licence_end_date before lockout applies.
    'grace_period_days' => (int) env('BILLING_GRACE_DAYS', 0),

    // When the server cannot be reached and nothing is cached:
    // true  = keep the app running (recommended), false = lock the app.
    'fail_open' => env('BILLING_FAIL_OPEN', true),

    'currency' => env('BILLING_CURRENCY', 'Tk.'),

    /*
    |--------------------------------------------------------------------------
    | Exempt routes
    |--------------------------------------------------------------------------
    | Route names (wildcards allowed) or URI patterns that are never locked.
    | The package's own "billing.*" routes are always exempt.
    */
    'exempt_routes' => [
        'billing.*',
        'login',
        'logout',
    ],

    // Where to send the user once the license is valid again.
    'redirect_after_valid' => '/',

    /*
    |--------------------------------------------------------------------------
    | Expiration Warning & Notifications
    |--------------------------------------------------------------------------
    | Number of days before licence_end_date to start displaying warning badges
    | and notification alerts in the UI.
    */
    'warning_days' => (int) env('BILLING_WARNING_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Layout configuration
    |--------------------------------------------------------------------------
    | Blade layout view or component to extend in the billing overview page.
    */
    'layout' => env('BILLING_LAYOUT', 'layouts.app'),

    /*
    |--------------------------------------------------------------------------
    | Module Sidebar Menu
    |--------------------------------------------------------------------------
    | Automatically register into App\Modules\Core\Services\MenuService if present.
    */
    'menu' => [
        'enabled' => env('BILLING_MENU_ENABLED', true),
        'section' => env('BILLING_MENU_SECTION', 'Core System'),
        'label'   => env('BILLING_MENU_LABEL', 'Billing & License'),
        'icon'    => env('BILLING_MENU_ICON', 'receipt'),
        'order'   => (int) env('BILLING_MENU_ORDER', 95),
        'route'   => 'billing.index',
        'active'  => 'billing.*',
        'badge'   => 'billing.badge',
    ],
];