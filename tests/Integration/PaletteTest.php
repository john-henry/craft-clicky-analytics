<?php

/**
 * Coverage for Palette. The colour lookups read the active scheme from the
 * plugin settings singleton, so each test sets the scheme it needs explicitly
 * (the configured environment value is not assumed) before asserting.
 */

use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Sets the active colour scheme on the plugin settings for the current test.
 */
function useScheme(string $scheme): void
{
    ClickyAnalytics::getInstance()->getSettings()->colorScheme = $scheme;
}

// ---------------------------------------------------------------------------
// Scheme options
// ---------------------------------------------------------------------------

describe('Palette::schemes() and swatches()', function () {
    it('offers the five built-in schemes', function () {
        expect(array_keys(Palette::schemes()))
            ->toBe(['clicky', 'ocean', 'mono', 'forest', 'berry']);
    });

    it('builds a swatch (label + five colours) for each scheme', function () {
        $swatches = Palette::swatches();

        expect($swatches)->toHaveKeys(['clicky', 'ocean', 'mono', 'forest', 'berry']);
        expect($swatches['clicky']['colors'])->toHaveCount(5);
        expect($swatches['clicky']['label'])->not->toBeEmpty();
    });

    it('builds each swatch from the primary plus the four rank-tint bars, not the old per-role palette', function () {
        useScheme('clicky');
        expect(Palette::swatches()['clicky']['colors'])
            ->toBe(['#d9553f', '#f7dbe0', '#f9e2d2', '#f2ead8', '#e9edf2']);
    });
});

// ---------------------------------------------------------------------------
// Current-scheme colours
// ---------------------------------------------------------------------------

describe('Palette colours for the active scheme', function () {
    it('returns the scheme primary', function () {
        useScheme('clicky');
        // AA-corrected accent (darkened from the original #ee5a24 brand
        // orange so it passes 4.5:1 on white as a chart stroke and as
        // 12px+ bold text).
        expect(Palette::primary())->toBe('#d9553f');
    });

    it('reflects a changed colour scheme', function () {
        useScheme('ocean');
        expect(Palette::primary())->toBe('#2f6fb8');
    });

    it('returns the four rank-tint bar colours for the active scheme', function () {
        useScheme('clicky');
        expect(Palette::bars())->toBe(['#f7dbe0', '#f9e2d2', '#f2ead8', '#e9edf2']);
    });

    it('returns the secondary (donut "returning" slice) colour', function () {
        useScheme('clicky');
        expect(Palette::secondary())->toBe('#e59f3c');
    });

    it('returns the soft chart-area-fill colour', function () {
        useScheme('clicky');
        expect(Palette::soft())->toBe('#fcebe5');
    });

    it('reflects a changed scheme for bars, secondary and soft', function () {
        useScheme('ocean');
        expect(Palette::bars())->toBe(['#d9e8f8', '#d8eff0', '#e1e7f5', '#eaeef3']);
        expect(Palette::secondary())->toBe('#37939b');
        expect(Palette::soft())->toBe('#e4eefb');
    });

    it('returns the first palette colour for the first role', function () {
        // "pages" is the first role and maps to palette index 0.
        useScheme('clicky');
        expect(Palette::accent('pages'))->toBe('#f59e0b');
    });

    it('returns a distinct accent for a later role', function () {
        // "browsers" is the sixth role (index 5).
        useScheme('clicky');
        expect(Palette::accent('browsers'))->toBe('#c81e5b');
    });

    it('falls back to the primary colour for an unknown role', function () {
        useScheme('forest');
        expect(Palette::accent('not-a-role'))->toBe(Palette::primary());
    });

    it('returns the pie palette for the active scheme', function () {
        useScheme('clicky');
        expect(Palette::pie())->toBe([
            '#ee5a24', '#f59e0b', '#3b82f6', '#14b8a6', '#c81e5b', '#9a3412', '#db2777', '#0e7490',
        ]);
    });
});

// ---------------------------------------------------------------------------
// Invalid scheme fallback
// ---------------------------------------------------------------------------

describe('Palette: invalid scheme fallback', function () {
    it('falls back to the default scheme when the setting is unknown', function () {
        useScheme('does-not-exist');

        // Default (clicky) scheme primary and first accent.
        expect(Palette::primary())->toBe('#d9553f');
        expect(Palette::accent('pages'))->toBe('#f59e0b');
    });
});
