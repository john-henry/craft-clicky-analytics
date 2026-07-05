<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

/**
 * Operating system helper.
 *
 * Clicky returns versioned OS names (e.g. "Windows 10", "Mac OS X"). This maps
 * them to a family: a display label plus the slug of a bundled OS logo, so the
 * UI can show an icon and, when consolidation is enabled, group every version of
 * an OS into one row.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class OperatingSystems
{
    // Constants
    // =========================================================================

    /**
     * @var array<string, array{0: string, 1: string|null}> Match needle (lower-case) => [family label, logo slug or null].
     *
     * Order matters: the first needle found in the title wins.
     */
    private const FAMILIES = [
        'windows phone' => ['Windows Phone', 'windows'],
        'windows' => ['Windows', 'windows'],
        'chrome os' => ['Chrome OS', 'chromeos'],
        'chromeos' => ['Chrome OS', 'chromeos'],
        'ipados' => ['iPadOS', 'apple'],
        'ios' => ['iOS', 'apple'],
        'iphone' => ['iOS', 'apple'],
        'ipad' => ['iPadOS', 'apple'],
        'mac os' => ['macOS', 'apple'],
        'macos' => ['macOS', 'apple'],
        'os x' => ['macOS', 'apple'],
        'android' => ['Android', 'android'],
        'ubuntu' => ['Linux', 'linux'],
        'fedora' => ['Linux', 'linux'],
        'debian' => ['Linux', 'linux'],
        'linux' => ['Linux', 'linux'],
    ];

    // Public Methods
    // =========================================================================

    /**
     * Matches a Clicky OS title to a family.
     *
     * @param string $title The OS title from Clicky.
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

        return ['label' => $title, 'slug' => null];
    }
}
