<?php

/**
 * Coverage for DateRanges. Labels use Craft::t(), so these run as integration
 * tests.
 */

use Craft;
use johnhenry\clickyanalytics\helpers\DateRanges;

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

describe('DateRanges::isValid()', function () {
    it('accepts a relative preset', function () {
        expect(DateRanges::isValid('last-7-days'))->toBeTrue();
    });

    it('accepts a YYYY-MM month key', function () {
        expect(DateRanges::isValid('2026-05'))->toBeTrue();
    });

    it('rejects an unknown key, including the old custom keyword', function () {
        expect(DateRanges::isValid('fortnight'))->toBeFalse();
        expect(DateRanges::isValid('custom'))->toBeFalse();
        expect(DateRanges::isValid('2026-13'))->toBeFalse();
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
        expect(DateRanges::normalize('today'))->toBe('today');
    });

    it('falls back to the given default for an invalid value', function () {
        expect(DateRanges::normalize('nope', 'last-28-days'))->toBe('last-28-days');
    });

    it('falls back to the DEFAULT when both value and default are invalid', function () {
        expect(DateRanges::normalize('nope', 'also-nope'))->toBe(DateRanges::DEFAULT);
    });
});

// ---------------------------------------------------------------------------
// Resolution: presets go to Clicky as keywords, so "today" is today in the
// Clicky site's time zone, not Craft's
// ---------------------------------------------------------------------------

describe('DateRanges::resolve()', function () {
    it('sends a relative preset as Clicky\'s own keyword, compared with the period before it', function () {
        $range = DateRanges::resolve('last-7-days');

        expect($range['date'])->toBe('last-7-days');
        expect($range['previous'])->toBe(DateRanges::PREVIOUS_PERIOD);
        expect($range['label'])->toBe('Last 7 days');
    });

    it('sends a month as its full date range, compared with the month before', function () {
        $range = DateRanges::resolve('2026-03');

        expect($range['date'])->toBe('2026-03-01,2026-03-31');
        expect($range['previous'])->toBe('2026-02-01,2026-02-28');
    });

    it('falls back to the default for an unknown key', function () {
        expect(DateRanges::resolve('nope')['date'])->toBe(DateRanges::DEFAULT);
    });
});

describe('DateRanges::precedingPeriod()', function () {
    it('returns the equal-length range before a reported range', function () {
        expect(DateRanges::precedingPeriod('2026-09-19,2026-09-25'))->toBe('2026-09-12,2026-09-18');
    });

    it('returns the day before a single reported day', function () {
        expect(DateRanges::precedingPeriod('2026-09-25'))->toBe('2026-09-24');
    });

    it('returns null for something it can\'t read', function () {
        expect(DateRanges::precedingPeriod('garbage'))->toBeNull();
    });
});

describe('DateRanges::isClickyDate()', function () {
    it('accepts range keys and explicit dates, and nothing else', function () {
        expect(DateRanges::isClickyDate('today'))->toBeTrue();
        expect(DateRanges::isClickyDate('2026-09-01,2026-09-25'))->toBeTrue();
        expect(DateRanges::isClickyDate('2026-09-01'))->toBeTrue();
        expect(DateRanges::isClickyDate('2000-01-01,2026-09-25&type=visitors-list'))->toBeFalse();
        expect(DateRanges::isClickyDate(''))->toBeFalse();
    });
});
