<?php

/**
 * Coverage for DateRanges. The resolution maths are pure but read the site time
 * zone and use Craft::t() for labels, so these run as integration tests. Expected
 * dates are computed against the same site time zone to stay robust as the clock
 * moves.
 */

use Craft;
use johnhenry\clickyanalytics\helpers\DateRanges;

/**
 * Returns "today" in the site time zone as a Y-m-d string, optionally offset by a
 * number of days.
 */
function siteDay(int $offsetDays = 0): string
{
    $date = new DateTime('today', new DateTimeZone(Craft::$app->getTimeZone()));

    if ($offsetDays !== 0) {
        $date->modify(sprintf('%+d days', $offsetDays));
    }

    return $date->format('Y-m-d');
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

describe('DateRanges::isValid()', function () {
    it('accepts a relative preset', function () {
        expect(DateRanges::isValid('last-7-days'))->toBeTrue();
    });

    it('accepts the custom keyword', function () {
        expect(DateRanges::isValid('custom'))->toBeTrue();
    });

    it('accepts a YYYY-MM month key', function () {
        expect(DateRanges::isValid('2024-05'))->toBeTrue();
    });

    it('rejects an unknown key', function () {
        expect(DateRanges::isValid('last-millennium'))->toBeFalse();
    });

    it('rejects null', function () {
        expect(DateRanges::isValid(null))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// Normalisation
// ---------------------------------------------------------------------------

describe('DateRanges::normalize()', function () {
    it('returns a valid value unchanged', function () {
        expect(DateRanges::normalize('last-14-days'))->toBe('last-14-days');
    });

    it('falls back to the given default for an invalid value', function () {
        expect(DateRanges::normalize('nope', 'today'))->toBe('today');
    });

    it('falls back to the DEFAULT when both value and default are invalid', function () {
        expect(DateRanges::normalize('nope', 'also-nope'))->toBe(DateRanges::DEFAULT);
    });
});

// ---------------------------------------------------------------------------
// Relative presets
// ---------------------------------------------------------------------------

describe('DateRanges::resolve(): relative presets', function () {
    it('resolves "today" to a single day with yesterday as the comparison period', function () {
        $resolved = DateRanges::resolve('today');

        expect($resolved['start'])->toBe(siteDay());
        expect($resolved['end'])->toBe(siteDay());
        expect($resolved['date'])->toBe(siteDay());            // single date, no comma
        expect($resolved['previous'])->toBe(siteDay(-1));      // the day before
    });

    it('resolves "yesterday" to the previous single day', function () {
        $resolved = DateRanges::resolve('yesterday');

        expect($resolved['start'])->toBe(siteDay(-1));
        expect($resolved['end'])->toBe(siteDay(-1));
    });

    it('resolves "last-7-days" to a 7-day window with the preceding 7 days as comparison', function () {
        $resolved = DateRanges::resolve('last-7-days');

        expect($resolved['start'])->toBe(siteDay(-6));
        expect($resolved['end'])->toBe(siteDay());
        expect($resolved['date'])->toBe(siteDay(-6) . ',' . siteDay());
        expect($resolved['previous'])->toBe(siteDay(-13) . ',' . siteDay(-7));
    });
});

// ---------------------------------------------------------------------------
// Custom ranges
// ---------------------------------------------------------------------------

describe('DateRanges::resolve(): custom ranges', function () {
    it('formats a multi-day custom range as "start,end"', function () {
        $resolved = DateRanges::resolve('custom', siteDay(-3), siteDay(-1));

        expect($resolved['date'])->toBe(siteDay(-3) . ',' . siteDay(-1));
        expect($resolved['start'])->toBe(siteDay(-3));
        expect($resolved['end'])->toBe(siteDay(-1));
    });

    it('swaps reversed start/end dates', function () {
        $resolved = DateRanges::resolve('custom', siteDay(-1), siteDay(-3));

        expect($resolved['start'])->toBe(siteDay(-3));
        expect($resolved['end'])->toBe(siteDay(-1));
    });

    it('caps the end date at today', function () {
        $resolved = DateRanges::resolve('custom', siteDay(-2), siteDay(5));

        expect($resolved['end'])->toBe(siteDay());
    });

    it('falls back to the last 7 days when custom dates are missing', function () {
        $resolved = DateRanges::resolve('custom');

        expect($resolved['start'])->toBe(siteDay(-6));
        expect($resolved['end'])->toBe(siteDay());
    });
});
