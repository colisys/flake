<?php

/**
 * View config
 */

return [
    // View path
    'path' => BASE_DIR . '/views',
    // Cache config
    // 0 - disable cache, but noted when compiled view throw error, it would not be tracable
    // 1 - enable cache
    'enable_cache' => 0,
    // Cache time to live, in seconds
    // Quick note:
    // < 0 - always cache, until cache file is deleted
    // 3600 - 1 hour
    // 86400 - 1 day
    'cache_ttl' => 3600,
    // View parse rules
    'rules' => [
        \Flake\View\Rules\Conditions::class,
        \Flake\View\Rules\Includes::class,
        \Flake\View\Rules\Literal::class,
        \Flake\View\Rules\Loop::class,
        \Flake\View\Rules\Variable::class,
        \Flake\View\Rules\Code::class,
        \Flake\View\Rules\Raw::class,

        // This may be useful for security, however, it may break some normal views
        \Flake\View\Rules\XSSClean::class,
    ]
];
