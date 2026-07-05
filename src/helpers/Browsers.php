<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

/**
 * Browser helper.
 *
 * Clicky returns versioned browser names (e.g. "Google Chrome 149.0 mobile").
 * This matches them to a brand family: a display label plus the slug of a
 * bundled brand logo (browser-logos), so the UI can show an icon and, when
 * consolidation is enabled, group every version of a browser into one row.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class Browsers
{
    // Constants
    // =========================================================================

    /**
     * @var array<string, array{0: string, 1: string|null}> Match needle (lower-case) => [family label, logo slug or null].
     *
     * Order matters: the first needle found in the title wins.
     */
    private const FAMILIES = [
        'samsung' => ['Samsung Internet', 'samsung-internet'],
        'edge' => ['Microsoft Edge', 'edge'],
        'opera' => ['Opera', 'opera'],
        'brave' => ['Brave', 'brave'],
        'vivaldi' => ['Vivaldi', 'vivaldi'],
        'yandex' => ['Yandex', null],
        'duckduckgo' => ['DuckDuckGo', null],
        'internet explorer' => ['Internet Explorer', null],
        'firefox' => ['Firefox', 'firefox'],
        'safari' => ['Safari', 'safari'],
        'chromium' => ['Chromium', 'chromium'],
        'uc browser' => ['UC Browser', 'uc'],
        'ucbrowser' => ['UC Browser', 'uc'],
        'chrome' => ['Google Chrome', 'chrome'],
    ];

    // Public Methods
    // =========================================================================

    /**
     * Matches a Clicky browser title to a brand family.
     *
     * @param string $title The browser title from Clicky.
     * @return array{label: string, slug: string|null} The family label and logo slug (null = no logo).
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function match(string $title): array
    {
        $haystack = strtolower($title);

        foreach (self::FAMILIES as $needle => [$label, $slug]) {
            if (str_contains($haystack, $needle)) {
                return ['label' => $label, 'slug' => $slug];
            }
        }

        // Unknown browser: keep its full name, no logo.
        return ['label' => $title, 'slug' => null];
    }
}
