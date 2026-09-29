<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MenoyeMan product settings
    |--------------------------------------------------------------------------
    */

    // Free trial length for new businesses (spec §13).
    'trial_days' => env('MENOYEMAN_TRIAL_DAYS', 10),

    // Grace period after subscription expiry during which the public menu
    // stays live (spec §13).
    'grace_days' => env('MENOYEMAN_GRACE_DAYS', 3),

    // Keep data of expired accounts before cleanup (spec §13).
    'expired_data_retention_days' => env('MENOYEMAN_EXPIRED_RETENTION_DAYS', 90),

    // Public menu base URL. When the "menu" subdomain is unavailable on the
    // host, the fallback path (menoyeman.ir/m/{slug}) is used instead.
    'menu_base_url' => env('MENOYEMAN_MENU_BASE_URL'),
    'menu_use_path' => env('MENOYEMAN_MENU_USE_PATH', true),

    // Domains (informational; used for links/QR codes).
    'app_domain' => env('MENOYEMAN_APP_DOMAIN', 'menoyeman.ir'),

];
