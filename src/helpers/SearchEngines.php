<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

/**
 * Search engine helper.
 *
 * Clicky returns search engines as domains (e.g. "google.com", "google.co.uk").
 * This maps them to a brand: a display label plus the slug of a bundled logo,
 * so the UI can show an icon and collapse a brand's regional domains into one
 * row.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class SearchEngines
{
    // Constants
    // =========================================================================

    /**
     * @var array<string, array{0: string, 1: string|null}> Match needle (lower-case) => [brand label, logo slug or null].
     */
    private const ENGINES = [
        'google' => ['Google', 'google'],
        'bing' => ['Bing', 'bing'],
        'duckduckgo' => ['DuckDuckGo', 'duckduckgo'],
        'yahoo' => ['Yahoo', 'yahoo'],
        'baidu' => ['Baidu', null],
        'yandex' => ['Yandex', null],
        'ecosia' => ['Ecosia', null],
        'startpage' => ['Startpage', null],
        'brave' => ['Brave Search', null],
        'ask.' => ['Ask', null],
        'qwant' => ['Qwant', null],
    ];

    // Public Methods
    // =========================================================================

    /**
     * Matches a Clicky search engine domain to a brand.
     *
     * @param string $domain The engine domain from Clicky.
     * @return array{label: string, slug: string|null} The brand label and logo slug (null = no logo).
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function match(string $domain): array
    {
        $haystack = strtolower($domain);

        foreach (self::ENGINES as $needle => [$label, $slug]) {
            if (str_contains($haystack, $needle)) {
                return ['label' => $label, 'slug' => $slug];
            }
        }

        // Unknown engine: keep the domain as the label, no logo.
        return ['label' => $domain, 'slug' => null];
    }
}
