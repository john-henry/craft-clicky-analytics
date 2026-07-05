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

use craft\helpers\App;

return [
    // Your Clicky Site ID. Best kept in an environment variable.
    // Found in Clicky under Preferences → Site → "Site ID & keys".
    'siteId' => App::env('CLICKY_SITE_ID') ?: null,

    // Your private Clicky Sitekey. Keep this secret; use an environment variable.
    'siteKey' => App::env('CLICKY_SITEKEY') ?: null,

    // The date range widgets and the page-stats field use by default. One of:
    // today, yesterday, 2-days-ago, last-7-days, last-14-days, last-28-days,
    // last-60-days, last-90-days.
    'defaultDateRange' => 'last-7-days',

    // How long, in seconds, to cache Clicky API responses (0 disables caching).
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

    // Whether the toolbar uses a slim style instead of the full bar.
    'slimBarStyle' => false,
];
