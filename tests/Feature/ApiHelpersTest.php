<?php

/**
 * Coverage for the Api service's pure transformation helpers: the metric/trend
 * maths, duration formatting, country-name normalisation, row consolidation and
 * city/region filtering. These are private and Craft-free, so they are invoked
 * directly via reflection with no Craft application.
 */

use johnhenry\clickyanalytics\services\Api;

/**
 * Invokes a private Api method via reflection.
 *
 * @param array<int, mixed> $args
 */
function invokeApi(string $method, array $args): mixed
{
    $ref = new ReflectionMethod(Api::class, $method);
    $ref->setAccessible(true);

    return $ref->invokeArgs(new Api(), $args);
}

// ---------------------------------------------------------------------------
// _metric(): trend calculation
// ---------------------------------------------------------------------------

describe('Api::_metric()', function () {
    it('returns no trend when there is no previous value', function () {
        expect(invokeApi('_metric', [120, null, true]))
            ->toBe(['value' => 120, 'trend' => null, 'dir' => null, 'positive' => null]);
    });

    it('returns no trend when the previous value is zero (avoids divide-by-zero)', function () {
        expect(invokeApi('_metric', [120, 0, true]))
            ->toBe(['value' => 120, 'trend' => null, 'dir' => null, 'positive' => null]);
    });

    it('computes an upward trend as a percentage change', function () {
        $metric = invokeApi('_metric', [120, 100, true]);

        expect($metric['trend'])->toBe(20);
        expect($metric['dir'])->toBe('up');
        expect($metric['positive'])->toBeTrue();
    });

    it('marks a downward trend negative when higher is better', function () {
        $metric = invokeApi('_metric', [80, 100, true]);

        expect($metric['trend'])->toBe(20);
        expect($metric['dir'])->toBe('down');
        expect($metric['positive'])->toBeFalse();
    });

    it('marks a downward trend positive when lower is better', function () {
        // e.g. bounce rate falling from 40% to 30%.
        $metric = invokeApi('_metric', [30, 40, false]);

        expect($metric['dir'])->toBe('down');
        expect($metric['positive'])->toBeTrue();
        expect($metric['trend'])->toBe(25);
    });
});

// ---------------------------------------------------------------------------
// _prettyTime(): duration formatting
// ---------------------------------------------------------------------------

describe('Api::_prettyTime()', function () {
    it('formats sub-minute durations in seconds', function () {
        expect(invokeApi('_prettyTime', [45]))->toBe('45s');
    });

    it('formats a whole number of minutes without seconds', function () {
        expect(invokeApi('_prettyTime', [60]))->toBe('1m');
    });

    it('formats minutes and seconds', function () {
        expect(invokeApi('_prettyTime', [99]))->toBe('1m 39s');
    });

    it('keeps counting in minutes past an hour', function () {
        expect(invokeApi('_prettyTime', [3600]))->toBe('60m');
    });
});

// ---------------------------------------------------------------------------
// _countryKey(): loose country-name normalisation
// ---------------------------------------------------------------------------

describe('Api::_countryKey()', function () {
    it('lower-cases and trims', function () {
        expect(invokeApi('_countryKey', ['  Ireland  ']))->toBe('ireland');
    });

    it('strips a leading "the"', function () {
        expect(invokeApi('_countryKey', ['The United States']))->toBe('united states');
    });
});

// ---------------------------------------------------------------------------
// _consolidate(): grouping ranked rows
// ---------------------------------------------------------------------------

describe('Api::_consolidate()', function () {
    it('groups rows by family label, summing value and percent, ordered by value', function () {
        $rows = [
            ['title' => 'Google Chrome 120', 'value' => 5, 'value_percent' => 25.0],
            ['title' => 'Firefox 130', 'value' => 8, 'value_percent' => 40.0],
            ['title' => 'Google Chrome 119', 'value' => 7, 'value_percent' => 35.0],
        ];

        $labelFn = static fn(string $title): string => str_contains($title, 'Chrome') ? 'Chrome' : 'Firefox';

        $result = invokeApi('_consolidate', [$rows, $labelFn]);

        // Chrome: 5 + 7 = 12 (wins); Firefox: 8.
        expect($result[0]['title'])->toBe('Chrome');
        expect($result[0]['value'])->toBe(12);
        expect($result[0]['value_percent'])->toBe(60.0);
        expect($result[1]['title'])->toBe('Firefox');
    });

    it('rounds the summed percentage to one decimal place', function () {
        $rows = [
            ['title' => 'A', 'value' => 1, 'value_percent' => 33.33],
            ['title' => 'A', 'value' => 1, 'value_percent' => 33.34],
        ];

        $result = invokeApi('_consolidate', [$rows, static fn(string $t): string => 'A']);

        expect($result[0]['value_percent'])->toBe(66.7);
    });
});

// ---------------------------------------------------------------------------
// _filterByCountry(): city/region drilldown
// ---------------------------------------------------------------------------

describe('Api::_filterByCountry()', function () {
    it('keeps only rows for the target country and strips the country suffix', function () {
        $rows = [
            ['title' => 'Dublin, Ireland', 'value' => 10],
            ['title' => 'Paris, France', 'value' => 8],
            ['title' => 'Cork, Ireland', 'value' => 5],
        ];

        $result = invokeApi('_filterByCountry', [$rows, 'Ireland', 10]);

        expect($result)->toHaveCount(2);
        expect($result[0]['title'])->toBe('Dublin');
        expect($result[1]['title'])->toBe('Cork');
    });

    it('matches country names loosely (case and leading "the")', function () {
        $rows = [['title' => 'London, The United Kingdom', 'value' => 3]];

        $result = invokeApi('_filterByCountry', [$rows, 'united kingdom', 10]);

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('London');
    });

    it('respects the row limit', function () {
        $rows = [
            ['title' => 'Dublin, Ireland', 'value' => 10],
            ['title' => 'Cork, Ireland', 'value' => 5],
            ['title' => 'Galway, Ireland', 'value' => 3],
        ];

        $result = invokeApi('_filterByCountry', [$rows, 'Ireland', 2]);

        expect($result)->toHaveCount(2);
    });

    it('skips rows with no country component', function () {
        $rows = [['title' => 'Mystery Place', 'value' => 1]];

        expect(invokeApi('_filterByCountry', [$rows, 'Ireland', 10]))->toBeEmpty();
    });
});
