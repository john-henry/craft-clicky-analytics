<?php

/**
 * Clicky Analytics config
 * =============================================================================
 *
 * Copy this file to your project's `config/` folder as `clicky-analytics.php` to override
 * the plugin's settings from code. Any value set here takes precedence over the
 * settings saved in the Control Panel (and those fields become read-only there).
 *
 * This file supports Craft's multi-environment config, e.g.:
 *
 *     return [
 *         '*' => [
 *             'cacheDuration' => 300,
 *         ],
 *         'production' => [
 *             'cacheDuration' => 900,
 *         ],
 *     ];
 *
 * @copyright Copyright (c) John Henry Donovan
 */

return [
    // Your Clicky Site ID, found in Clicky under Preferences → Site → "Site ID & keys".
    // Given as an environment variable reference, which the plugin resolves when it's used.
    'siteId' => '$CLICKY_SITE_ID',

    // Your private Clicky Sitekey. Always give it as a reference like this, not as
    // `App::env()` or the key itself, so the key never reaches the settings screen.
    'siteKey' => '$CLICKY_SITE_KEY',

    // Clicky credentials for Craft sites that track into their own Clicky site,
    // keyed by site handle. Other sites use the pair above.
    // 'sites' => [
    //     'siteHandle' => [
    //         'siteId' => '$CLICKY_OTHER_SITE_ID',
    //         'siteKey' => '$CLICKY_OTHER_SITE_KEY',
    //     ],
    // ],

    // The date range dashboard widgets use by default. The page-stats field and
    // the Twig variable have their own defaults. One of:
    // today, yesterday, 2-days-ago, last-7-days, last-14-days, last-28-days,
    // last-60-days, last-90-days.
    'defaultDateRange' => 'last-7-days',

    // How long, in seconds, to cache Clicky API responses (0 disables caching,
    // except for the visitors-online count and recent visitors feed, which are
    // always cached for 30 seconds).
    'cacheDuration' => 300,

    // The accent colour scheme. One of: clicky, ocean, mono, forest, berry.
    'colorScheme' => 'clicky',

    // Whether to inject the Clicky tracking snippet into front-end requests.
    'injectTrackingCode' => false,

    // Whether the injected snippet includes the `<noscript>` pixel fallback,
    // for visitors with JavaScript disabled.
    'trackNoScript' => true,

    // Whether the CP UI uses a compact density instead of comfortable.
    'compactDensity' => false,

    // Whether the ranked lists use slimmer bars instead of full-height ones.
    'slimBarStyle' => false,
];
