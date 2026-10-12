<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Single Tenant Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the application runs in single-tenant (on-premise) mode:
    |
    | - Public registration is disabled
    | - Billing / subscription flow is bypassed
    | - Superadmin panel is hidden
    | - Only one Company record is allowed
    | - SetTenant middleware skips subscription expiration checks
    |
    | Set to false to run as multi-tenant SaaS.
    |
    */

    'single_mode' => env('SINGLE_TENANT_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Locked Company ID
    |--------------------------------------------------------------------------
    |
    | When single_mode is enabled, this is the only Company ID that the
    | application will operate against. Typically 1 (the company seeded via
    | GemilangCompanySeeder).
    |
    */

    'company_id' => (int) env('SINGLE_TENANT_COMPANY_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Brand Configuration
    |--------------------------------------------------------------------------
    |
    | Client-facing brand identity used when running in single_mode. These
    | values are also used as fallbacks in multi-tenant mode so UI elements
    | have sensible defaults.
    |
    */

    'brand' => [
        'name' => env('BRAND_NAME', env('APP_NAME', 'GajiPro')),
        'logo_path' => env('BRAND_LOGO_PATH', 'images/brand/logo.svg'),
        'logo_mark_path' => env('BRAND_LOGO_MARK_PATH', 'images/brand/logo-mark.svg'),
        'favicon_path' => env('BRAND_FAVICON_PATH', 'images/brand/favicon.svg'),
        'primary_color' => env('BRAND_PRIMARY_COLOR', '#2563eb'),
        'accent_color' => env('BRAND_ACCENT_COLOR', '#f97316'),
        'footer_text' => env('BRAND_FOOTER_TEXT', null),
    ],

];
