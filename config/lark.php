<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lark Open Platform Base URL
    |--------------------------------------------------------------------------
    | Lark (International): https://open.larksuite.com/open-apis
    | Feishu (China):       https://open.feishu.cn/open-apis
    */
    'base_url' => env('LARK_BASE_URL', 'https://open.larksuite.com/open-apis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    | Prefix and TTL for caching tenant_access_token in seconds.
    | Lark tenant access token is valid for 7200 seconds (2 hours).
    */
    'cache_prefix' => 'lark_tenant_token:',
    'token_ttl' => 7000,

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => 15,
    'retry_times' => 3,
    'retry_sleep' => 250,
];
