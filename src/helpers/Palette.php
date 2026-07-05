<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;

/**
 * Colour palette helper.
 *
 * Resolves the accent colour for each widget "role" from the colour scheme
 * chosen in the plugin settings, so the whole plugin can be re-themed at once
 * while each widget keeps its own distinct accent within the scheme's family.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class Palette
{
    // Constants
    // =========================================================================

    /**
     * @var string The fallback scheme.
     */
    public const DEFAULT = 'clicky';

    /**
     * @var string[] Role keys, in the order they map onto a scheme's palette.
     */
    private const ROLE_ORDER = [
        'pages', 'sources', 'referrers', 'search', 'countries', 'browsers',
        'os', 'devices', 'entry', 'exit', 'downloads', 'goals', 'campaigns',
        'cities', 'regions', 'screen',
    ];

    /**
     * @var array<string, array{primary: string, palette: string[], pie: string[], bars: string[], secondary: string, soft: string}> The colour schemes.
     *
     * Notes on the newer per-scheme values:
     *  - `primary` is the scheme accent used for the chart line, the donut's
     *    first slice, and per-widget accents. It is darkened where needed to
     *    pass WCAG AA (4.5:1) on white so it stays safe as a chart stroke and
     *    as text at 12px+ bold.
     *  - `bars` are four ranked-list row tints (rank 1 to 4). They sit at
     *    roughly L90% deliberately, so the existing dark row text keeps AA
     *    contrast at 13px on top of them. The rank-4 tint is intentionally
     *    near-neutral to keep long tails quiet.
     *  - `secondary` is the donut's "returning" slice. It is a fill-only
     *    colour: fine at 12px+ bold, but not safe as small or fine text.
     *  - `soft` is the chart area fill: a light pastel meant to be used
     *    near-solid, not as a low-opacity tint of the accent.
     */
    private const SCHEMES = [
        'clicky' => [
            'primary' => '#d9553f',
            'palette' => ['#f59e0b', '#f2782c', '#0e7490', '#db2777', '#e0341d', '#c81e5b', '#9a3412', '#b45309', '#ca8a04', '#9f1239', '#7c2d12', '#be123c', '#c2410c', '#b45309', '#9a3412', '#78716c'],
            'pie' => ['#ee5a24', '#f59e0b', '#3b82f6', '#14b8a6', '#c81e5b', '#9a3412', '#db2777', '#0e7490'],
            'bars' => ['#f7dbe0', '#f9e2d2', '#f2ead8', '#e9edf2'],
            'secondary' => '#e59f3c',
            'soft' => '#fcebe5',
        ],
        'ocean' => [
            'primary' => '#2f6fb8',
            'palette' => ['#0ea5e9', '#0891b2', '#0d9488', '#2563eb', '#0369a1', '#14b8a6', '#155e75', '#1d4ed8', '#06b6d4', '#0e7490', '#3b82f6', '#0f766e', '#1e40af', '#0284c7', '#115e59', '#075985'],
            'pie' => ['#0284c7', '#0ea5e9', '#0d9488', '#14b8a6', '#2563eb', '#0891b2', '#1d4ed8', '#155e75'],
            'bars' => ['#d9e8f8', '#d8eff0', '#e1e7f5', '#eaeef3'],
            'secondary' => '#37939b',
            'soft' => '#e4eefb',
        ],
        'mono' => [
            'primary' => '#4a5b6d',
            'palette' => ['#475569', '#64748b', '#334155', '#94a3b8', '#52525b', '#71717a', '#3f3f46', '#6b7280', '#1e293b', '#475569', '#64748b', '#334155', '#94a3b8', '#52525b', '#71717a', '#9ca3af'],
            'pie' => ['#1e293b', '#334155', '#475569', '#64748b', '#94a3b8', '#52525b', '#71717a', '#9ca3af'],
            'bars' => ['#dfe5ec', '#e6ebf1', '#edf0f4', '#f2f4f7'],
            'secondary' => '#8595a5',
            'soft' => '#e9edf2',
        ],
        'forest' => [
            'primary' => '#3f8a4f',
            'palette' => ['#16a34a', '#65a30d', '#0d9488', '#15803d', '#4d7c0f', '#059669', '#166534', '#84cc16', '#047857', '#3f6212', '#10b981', '#14532d', '#22c55e', '#365314', '#064e3b', '#2d6a4f'],
            'pie' => ['#15803d', '#16a34a', '#22c55e', '#65a30d', '#0d9488', '#84cc16', '#047857', '#166534'],
            'bars' => ['#dcefd9', '#e9f2d7', '#dff0e7', '#ecf1ec'],
            'secondary' => '#7ba03a',
            'soft' => '#e6f3e3',
        ],
        'berry' => [
            'primary' => '#8a4bbf',
            'palette' => ['#9333ea', '#db2777', '#c026d3', '#7c3aed', '#a21caf', '#be185d', '#6d28d9', '#d6336c', '#8b5cf6', '#e11d48', '#a855f7', '#ec4899', '#7e22ce', '#c2185b', '#9d174d', '#6b21a8'],
            'pie' => ['#9333ea', '#db2777', '#c026d3', '#a21caf', '#7c3aed', '#ec4899', '#8b5cf6', '#be185d'],
            'bars' => ['#ecdff5', '#f4dfee', '#e6e3f4', '#eceef3'],
            'secondary' => '#b8509a',
            'soft' => '#f2e8f9',
        ],
    ];

    // Public Methods
    // =========================================================================

    /**
     * Returns the available colour schemes as `value => label` pairs.
     *
     * @return array<string, string> The scheme options.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function schemes(): array
    {
        return [
            'clicky' => Craft::t('clicky-analytics', 'Clicky Analytics (warm)'),
            'ocean' => Craft::t('clicky-analytics', 'Ocean (blue)'),
            'mono' => Craft::t('clicky-analytics', 'Monochrome'),
            'forest' => Craft::t('clicky-analytics', 'Forest (green)'),
            'berry' => Craft::t('clicky-analytics', 'Berry (purple)'),
        ];
    }

    /**
     * Returns each scheme with a few preview colours, for the settings swatch
     * picker: `key => { label, colors }`.
     *
     * @return array<string, array{label: string, colors: string[]}> The schemes with swatches.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function swatches(): array
    {
        $out = [];
        foreach (self::schemes() as $key => $label) {
            $scheme = self::SCHEMES[$key];
            $out[$key] = [
                'label' => $label,
                // The accent plus the four rank-tint bars: this is what the
                // scheme actually looks like on the ranked-list widgets now,
                // rather than a slice of the old per-role palette.
                'colors' => array_merge([$scheme['primary']], $scheme['bars']),
            ];
        }

        return $out;
    }

    /**
     * Returns the primary/brand accent for the current scheme (chart, counters).
     *
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function primary(): string
    {
        return self::SCHEMES[self::_current()]['primary'];
    }

    /**
     * Returns the accent for a widget role under the current scheme.
     *
     * @param string $role A role key (e.g. `pages`, `browsers`).
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function accent(string $role): string
    {
        $scheme = self::SCHEMES[self::_current()];
        $index = array_search($role, self::ROLE_ORDER, true);

        if ($index === false) {
            return $scheme['primary'];
        }

        return $scheme['palette'][$index % count($scheme['palette'])];
    }

    /**
     * Returns the pie-slice colours for the current scheme.
     *
     * @return string[] The pie palette.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function pie(): array
    {
        return self::SCHEMES[self::_current()]['pie'];
    }

    /**
     * Returns the secondary accent for the current scheme (donut "returning"
     * slice). Fill-only: fine at 12px+ bold, not safe as small or fine text.
     *
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function secondary(): string
    {
        return self::SCHEMES[self::_current()]['secondary'];
    }

    /**
     * Returns the soft area-fill colour for the current scheme (chart area).
     * A light pastel meant to be used near-solid, not as a faint accent tint.
     *
     * @return string A hex colour.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function soft(): string
    {
        return self::SCHEMES[self::_current()]['soft'];
    }

    /**
     * Returns the four ranked-list row tints for the current scheme, ordered
     * by rank position (rank 1 to 4). Rows beyond the fourth reuse the fourth
     * (near-neutral) tint.
     *
     * @return string[] The four rank tints.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function bars(): array
    {
        return self::SCHEMES[self::_current()]['bars'];
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the current (validated) scheme key from the plugin settings.
     *
     * @return string The scheme key.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private static function _current(): string
    {
        $scheme = ClickyAnalytics::getInstance()->getSettings()->colorScheme;

        return isset(self::SCHEMES[$scheme]) ? $scheme : self::DEFAULT;
    }
}
