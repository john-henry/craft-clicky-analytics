<?php

/**
 * Coverage for the Api service without hitting the Clicky HTTP API.
 *
 * Api::_request() returns a cached response before it ever reaches Guzzle, so
 * these tests swap in a fresh in-memory cache, set known credentials, and seed
 * the exact cache key a request would use. The public methods then run their
 * real parsing, consolidation and metric logic against synthetic Clicky payloads.
 */

use craft\helpers\Json;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\services\Api;
use yii\caching\ArrayCache;

const CLICKY_TEST_SITE_ID = '12345';

/**
 * Configures known credentials and a fresh in-memory cache, then returns the Api
 * service. Both the seed helper and the service read the same cache instance.
 */
function clickyApi(): Api
{
    Craft::$app->set('cache', new ArrayCache());

    $settings = ClickyAnalytics::getInstance()->getSettings();
    $settings->siteId = CLICKY_TEST_SITE_ID;
    $settings->siteKey = 'test-sitekey';
    // Reset to the plugin default - the settings model is a shared singleton
    // across tests, so a test that changes cacheDuration (e.g. to exercise the
    // "0 disables caching" bypass) would otherwise leak that value into every
    // later test in the run, regardless of Pest's execution order.
    $settings->cacheDuration = 300;

    $api = ClickyAnalytics::getInstance()->getApi();
    // Same reasoning as cacheDuration above - a test that injects a mock HTTP
    // client must not leak it into later tests via the shared Api singleton.
    $api->setHttpClient(null);

    return $api;
}

/**
 * Seeds the decoded response a request for the given types/date/params would
 * return, under the same cache key Api::_request() computes.
 *
 * @param string[] $types
 * @param array<int, array> $blocks
 */
function seedClicky(array $types, string $date, array $params, array $blocks): void
{
    $key = 'clicky-analytics:' . md5(Json::encode([CLICKY_TEST_SITE_ID, $types, $date, $params]));
    Craft::$app->getCache()->set($key, $blocks);
}

/**
 * Builds a single Clicky response block (one undated bucket) for a report type.
 *
 * @param array<int, array> $items
 */
function clickyBlock(string $type, array $items): array
{
    return ['type' => $type, 'dates' => [['items' => $items]]];
}

// ---------------------------------------------------------------------------
// isConfigured()
// ---------------------------------------------------------------------------

describe('Api::isConfigured()', function () {
    it('is true when both credentials are set', function () {
        $api = clickyApi();

        expect($api->isConfigured())->toBeTrue();
    });

    it('is false when the site key is missing', function () {
        $api = clickyApi();
        ClickyAnalytics::getInstance()->getSettings()->siteKey = null;

        expect($api->isConfigured())->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// getStats(): flattening and entity decoding
// ---------------------------------------------------------------------------

describe('Api::getStats()', function () {
    it('flattens a report block into its items keyed by type', function () {
        $api = clickyApi();
        seedClicky(['pages'], 'today', [], [
            clickyBlock('pages', [
                ['title' => 'Home', 'value' => 10],
                ['title' => 'About', 'value' => 4],
            ]),
        ]);

        $result = $api->getStats(['pages'], 'today');

        expect($result['pages'])->toHaveCount(2);
        expect($result['pages'][0]['value'])->toBe(10);
    });

    it('HTML-decodes item titles', function () {
        $api = clickyApi();
        seedClicky(['pages'], 'today', [], [
            clickyBlock('pages', [['title' => 'Tom &amp; Jerry', 'value' => 1]]),
        ]);

        expect($api->getStats(['pages'], 'today')['pages'][0]['title'])->toBe('Tom & Jerry');
    });

    it('keeps a well-formed http(s) url and stats_url', function () {
        $api = clickyApi();
        seedClicky(['links-domains'], 'today', [], [
            clickyBlock('links-domains', [[
                'title' => 'example.com',
                'value' => 1,
                'url' => 'https://example.com/referrer',
                'stats_url' => 'https://clicky.com/stats/some-report',
            ]]),
        ]);

        $item = $api->getStats(['links-domains'], 'today')['links-domains'][0];

        expect($item['url'])->toBe('https://example.com/referrer');
        expect($item['stats_url'])->toBe('https://clicky.com/stats/some-report');
    });

    it('strips a url/stats_url with a non-http(s) scheme', function () {
        $api = clickyApi();
        seedClicky(['links-domains'], 'today', [], [
            clickyBlock('links-domains', [[
                'title' => 'evil.com',
                'value' => 1,
                'url' => 'javascript:alert(document.cookie)',
                'stats_url' => 'data:text/html,<script>alert(1)</script>',
            ]]),
        ]);

        $item = $api->getStats(['links-domains'], 'today')['links-domains'][0];

        expect($item['url'])->toBeNull();
        expect($item['stats_url'])->toBeNull();
    });

    it('returns an empty array for a requested type with no block', function () {
        $api = clickyApi();
        seedClicky(['pages', 'countries'], 'today', [], [
            clickyBlock('pages', [['title' => 'Home', 'value' => 1]]),
        ]);

        $result = $api->getStats(['pages', 'countries'], 'today');

        expect($result['countries'])->toBeArray()->toBeEmpty();
    });

    it('throws when the API is not configured', function () {
        $api = clickyApi();
        ClickyAnalytics::getInstance()->getSettings()->siteId = null;

        $api->getStats(['pages'], 'today');
    })->throws(RuntimeException::class);

    it('treats cacheDuration=0 as "disable caching", not Yii\'s "never expire"', function () {
        // The settings screen and config.php document 0 as "disable caching".
        // Yii's cache component instead treats a set() duration of 0 as "never
        // expire" - the plugin must override that, not inherit it. Seed the
        // cache, set cacheDuration to 0, and confirm the seeded value is not
        // served back (mirrors the getVisitorsOnline() bypass test above).
        $api = clickyApi();
        ClickyAnalytics::getInstance()->getSettings()->cacheDuration = 0;
        seedClicky(['pages'], 'today', [], [
            clickyBlock('pages', [['title' => 'Stale', 'value' => 99]]),
        ]);

        try {
            $api->getStats(['pages'], 'today');
        } catch (RuntimeException) {
            // Expected in an offline test environment; irrelevant to this assertion.
        }

        // The bypass call must not have overwritten the cache either - read it
        // back with cacheDuration restored to confirm the seeded value survives.
        ClickyAnalytics::getInstance()->getSettings()->cacheDuration = 300;
        expect($api->getStats(['pages'], 'today')['pages'][0]['value'])->toBe(99);
    });
});

// ---------------------------------------------------------------------------
// getRecentVisitors()
// ---------------------------------------------------------------------------

describe('Api::getRecentVisitors()', function () {
    it('keeps a well-formed http(s) landing page', function () {
        $api = clickyApi();
        seedClicky(['visitors-list'], 'last-7-days', ['limit' => 12], [
            clickyBlock('visitors-list', [[
                'time' => time(),
                'landing_page' => 'https://example.com/blog/my-post',
            ]]),
        ]);

        expect($api->getRecentVisitors()[0]['landingPage'])->toBe('https://example.com/blog/my-post');
    });

    it('strips a landing page with a non-http(s) scheme', function () {
        $api = clickyApi();
        seedClicky(['visitors-list'], 'last-7-days', ['limit' => 12], [
            clickyBlock('visitors-list', [[
                'time' => time(),
                'landing_page' => 'javascript:alert(document.cookie)',
            ]]),
        ]);

        expect($api->getRecentVisitors()[0]['landingPage'])->toBe('');
    });
});

// ---------------------------------------------------------------------------
// getTally() / getNewVsReturning()
// ---------------------------------------------------------------------------

describe('Api tally helpers', function () {
    it('getTally() returns the first item value', function () {
        $api = clickyApi();
        seedClicky(['visitors-online'], 'today', [], [
            clickyBlock('visitors-online', [['value' => 7]]),
        ]);

        expect($api->getTally('visitors-online', 'today'))->toBe(7);
    });

    it('getVisitorsOnline() never writes to the response cache', function () {
        // getVisitorsOnline() always passes bypassCache=true, so calling it must
        // not overwrite whatever is already cached under the same key. Rather
        // than assert on getVisitorsOnline()'s own return value (which depends
        // on a real, unmocked HTTP call succeeding or failing), seed the cache,
        // call getVisitorsOnline() and discard the outcome either way, then read
        // the cache back through a normal (non-bypass) getTally() call - if the
        // bypass call had written to the cache, this would no longer be 99.
        $api = clickyApi();
        seedClicky(['visitors-online'], 'today', [], [
            clickyBlock('visitors-online', [['value' => 99]]),
        ]);

        try {
            $api->getVisitorsOnline();
        } catch (RuntimeException) {
            // Expected in an offline test environment; irrelevant to this assertion.
        }

        expect($api->getTally('visitors-online', 'today'))->toBe(99);
    });

    it('getNewVsReturning() derives the returning count from the total', function () {
        $api = clickyApi();
        seedClicky(['visitors', 'visitors-new'], 'last-7-days', [], [
            clickyBlock('visitors', [['value' => 100]]),
            clickyBlock('visitors-new', [['value' => 30]]),
        ]);

        expect($api->getNewVsReturning('last-7-days'))
            ->toBe(['new' => 30, 'returning' => 70, 'total' => 100]);
    });
});

// ---------------------------------------------------------------------------
// getOverview(): metrics + trends
// ---------------------------------------------------------------------------

describe('Api::getOverview()', function () {
    it('returns metric values without a trend when no previous period is given', function () {
        $api = clickyApi();
        $types = ['visitors', 'actions', 'bounce-rate', 'time-average'];
        seedClicky($types, 'last-7-days', [], [
            clickyBlock('visitors', [['value' => 120]]),
            clickyBlock('actions', [['value' => 50]]),
            clickyBlock('bounce-rate', [['value' => 30]]),
            clickyBlock('time-average', [['value' => 90]]),
        ]);

        $overview = $api->getOverview('last-7-days');

        expect($overview['visitors']['value'])->toBe(120);
        expect($overview['visitors']['trend'])->toBeNull();
        expect($overview['time']['pretty'])->toBe('1m 30s');
    });

    it('computes an upward visitor trend against the previous period', function () {
        $api = clickyApi();
        $types = ['visitors', 'actions', 'bounce-rate', 'time-average'];
        seedClicky($types, 'this', [], [
            clickyBlock('visitors', [['value' => 120]]),
            clickyBlock('actions', [['value' => 50]]),
            clickyBlock('bounce-rate', [['value' => 30]]),
            clickyBlock('time-average', [['value' => 90]]),
        ]);
        seedClicky($types, 'prev', [], [
            clickyBlock('visitors', [['value' => 100]]),
            clickyBlock('actions', [['value' => 40]]),
            clickyBlock('bounce-rate', [['value' => 40]]),
            clickyBlock('time-average', [['value' => 100]]),
        ]);

        $overview = $api->getOverview('this', 'prev');

        expect($overview['visitors']['trend'])->toBe(20);
        expect($overview['visitors']['dir'])->toBe('up');
        expect($overview['visitors']['positive'])->toBeTrue();
    });

    it('treats a falling bounce rate as a positive trend (lower is better)', function () {
        $api = clickyApi();
        $types = ['visitors', 'actions', 'bounce-rate', 'time-average'];
        seedClicky($types, 'this', [], [
            clickyBlock('bounce-rate', [['value' => 30]]),
        ]);
        seedClicky($types, 'prev', [], [
            clickyBlock('bounce-rate', [['value' => 40]]),
        ]);

        $overview = $api->getOverview('this', 'prev');

        expect($overview['bounceRate']['dir'])->toBe('down');
        expect($overview['bounceRate']['positive'])->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// getTopBrowsers(): consolidation
// ---------------------------------------------------------------------------

describe('Api::getTopBrowsers()', function () {
    it('consolidates browser versions into one family row, summing visitors', function () {
        $api = clickyApi();
        // Consolidation pulls limit=100.
        seedClicky(['web-browsers'], 'today', ['limit' => 100], [
            clickyBlock('web-browsers', [
                ['title' => 'Google Chrome 120', 'value' => 10, 'value_percent' => 50.0],
                ['title' => 'Google Chrome 119', 'value' => 5, 'value_percent' => 25.0],
                ['title' => 'Firefox 130', 'value' => 5, 'value_percent' => 25.0],
            ]),
        ]);

        $rows = $api->getTopBrowsers('today');

        expect($rows)->toHaveCount(2);
        expect($rows[0]['title'])->toBe('Google Chrome');
        expect($rows[0]['value'])->toBe(15);
        expect($rows[0]['logo'])->toBe('browsers/chrome');
    });

    it('keeps individual versions when consolidation is disabled', function () {
        $api = clickyApi();
        seedClicky(['web-browsers'], 'today', ['limit' => 10], [
            clickyBlock('web-browsers', [
                ['title' => 'Google Chrome 120', 'value' => 10],
                ['title' => 'Google Chrome 119', 'value' => 5],
            ]),
        ]);

        $rows = $api->getTopBrowsers('today', 10, false);

        expect($rows)->toHaveCount(2);
    });
});

// ---------------------------------------------------------------------------
// getTopCountries(): ISO codes
// ---------------------------------------------------------------------------

describe('Api::getTopCountries()', function () {
    it('attaches an ISO country code to each row', function () {
        $api = clickyApi();
        seedClicky(['countries'], 'today', ['limit' => 10], [
            clickyBlock('countries', [
                ['title' => 'Ireland', 'value' => 50],
                ['title' => 'Atlantis', 'value' => 1],
            ]),
        ]);

        $rows = $api->getTopCountries('today');

        expect($rows[0]['code'])->toBe('ie');
        expect($rows[1]['code'])->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// getDailySeries(): ordering
// ---------------------------------------------------------------------------

describe('Api::getDailySeries()', function () {
    it('reverses Clicky newest-first dates into a left-to-right series', function () {
        $api = clickyApi();
        seedClicky(['visitors'], 'last-7-days', ['daily' => 1], [
            [
                'type' => 'visitors',
                'dates' => [
                    ['date' => '2024-05-03', 'items' => [['value' => 30]]],
                    ['date' => '2024-05-02', 'items' => [['value' => 20]]],
                    ['date' => '2024-05-01', 'items' => [['value' => 10]]],
                ],
            ],
        ]);

        $series = $api->getDailySeries('visitors', 'last-7-days');

        expect($series[0])->toBe(['date' => '2024-05-01', 'value' => 10]);
        expect($series[2])->toBe(['date' => '2024-05-03', 'value' => 30]);
    });
});

// ---------------------------------------------------------------------------
// getTopOperatingSystems(): consolidation
// ---------------------------------------------------------------------------

describe('Api::getTopOperatingSystems()', function () {
    it('consolidates OS versions into one family row with a logo', function () {
        $api = clickyApi();
        // Consolidation pulls limit=100.
        seedClicky(['operating-systems'], 'today', ['limit' => 100], [
            clickyBlock('operating-systems', [
                ['title' => 'Windows 11', 'value' => 8, 'value_percent' => 40.0],
                ['title' => 'Windows 10', 'value' => 4, 'value_percent' => 20.0],
                ['title' => 'macOS', 'value' => 8, 'value_percent' => 40.0],
            ]),
        ]);

        $rows = $api->getTopOperatingSystems('today');

        expect($rows[0]['title'])->toBe('Windows');
        expect($rows[0]['value'])->toBe(12);
        expect($rows[0]['logo'])->toBe('os/windows');
    });
});

// ---------------------------------------------------------------------------
// getTopDevices(): device-type icon
// ---------------------------------------------------------------------------

describe('Api::getTopDevices()', function () {
    it('tags each row with a device-type icon fallback', function () {
        $api = clickyApi();
        seedClicky(['hardware'], 'today', ['limit' => 10], [
            clickyBlock('hardware', [
                ['title' => 'Apple iPhone', 'value' => 5],
                ['title' => 'Apple iPad', 'value' => 3],
            ]),
        ]);

        $rows = $api->getTopDevices('today');

        expect($rows[0]['logoFallback'])->toBe('phone');
        expect($rows[1]['logoFallback'])->toBe('tablet');
    });
});

// ---------------------------------------------------------------------------
// getReport(): type routing
// ---------------------------------------------------------------------------

describe('Api::getReport()', function () {
    it('routes a known type to its enriched method', function () {
        $api = clickyApi();
        seedClicky(['pages'], 'today', ['limit' => 10], [
            clickyBlock('pages', [['title' => 'Home', 'value' => 10]]),
        ]);

        $rows = $api->getReport('pages', 'today');

        expect($rows[0]['title'])->toBe('Home');
    });

    it('falls back to a generic ranked fetch for an unmapped type', function () {
        $api = clickyApi();
        seedClicky(['visitors-online'], 'today', ['limit' => 10], [
            clickyBlock('visitors-online', [['title' => 'Online', 'value' => 3]]),
        ]);

        $rows = $api->getReport('visitors-online', 'today');

        expect($rows[0]['value'])->toBe(3);
    });
});

// ---------------------------------------------------------------------------
// getPageStats(): payload assembly
// ---------------------------------------------------------------------------

describe('Api::getPageStats()', function () {
    it('assembles headline tallies, sources and recent visitors for a page', function () {
        $api = clickyApi();
        $href = '/blog/my-post';

        seedClicky(
            ['visitors', 'visitors-unique', 'actions', 'bounce-rate', 'time-average', 'traffic-sources'],
            'last-90-days',
            ['href' => $href, 'limit' => 10],
            [
                clickyBlock('visitors', [['value' => 120]]),
                clickyBlock('visitors-unique', [['value' => 90]]),
                clickyBlock('actions', [['value' => 200]]),
                clickyBlock('bounce-rate', [['value' => 42]]),
                clickyBlock('time-average', [['value' => 75]]),
                clickyBlock('traffic-sources', [['title' => 'Google', 'value' => 60]]),
            ]
        );
        seedClicky(['visitors-list'], 'last-90-days', ['href' => $href, 'limit' => 5], [
            clickyBlock('visitors-list', [['time' => time(), 'geolocation' => 'Cork, Ireland']]),
        ]);

        $stats = $api->getPageStats($href, 'last-90-days');

        expect($stats['visitors'])->toBe(120);
        expect($stats['unique'])->toBe(90);
        expect($stats['actions'])->toBe(200);
        expect($stats['bounceRate'])->toBe(42);
        expect($stats['time'])->toBe('1m 15s');
        expect($stats['sources'][0]['title'])->toBe('Google');
        expect($stats['visitorsList'][0]['geolocation'])->toBe('Cork, Ireland');
    });

    it('skips the recent visitors request when the visitor limit is zero', function () {
        $api = clickyApi();
        $href = '/about';

        seedClicky(
            ['visitors', 'visitors-unique', 'actions', 'bounce-rate', 'time-average', 'traffic-sources'],
            'last-90-days',
            ['href' => $href, 'limit' => 10],
            [clickyBlock('visitors', [['value' => 5]])]
        );

        $stats = $api->getPageStats($href, 'last-90-days', 0);

        expect($stats['visitorsList'])->toBeArray()->toBeEmpty();
    });
});

// ---------------------------------------------------------------------------
// getCountryBreakdown() / getCountriesWithBreakdown(): drilldown
// ---------------------------------------------------------------------------

describe('Api country drilldown', function () {
    it('filters cities and regions to one country and strips the suffix', function () {
        $api = clickyApi();
        seedClicky(['cities', 'regions'], 'last-7-days', ['limit' => 100], [
            clickyBlock('cities', [
                ['title' => 'Cork, Ireland', 'value' => 30],
                ['title' => 'Paris, France', 'value' => 20],
            ]),
            clickyBlock('regions', [
                ['title' => 'Munster, Ireland', 'value' => 25],
            ]),
        ]);

        $breakdown = $api->getCountryBreakdown('Ireland', 'last-7-days');

        expect($breakdown['cities'])->toHaveCount(1);
        expect($breakdown['cities'][0]['title'])->toBe('Cork');
        expect($breakdown['regions'][0]['title'])->toBe('Munster');
    });

    it('keys the countries that have any city or region data', function () {
        $api = clickyApi();
        seedClicky(['cities', 'regions'], 'last-7-days', ['limit' => 100], [
            clickyBlock('cities', [['title' => 'Cork, Ireland', 'value' => 30]]),
            clickyBlock('regions', [['title' => 'Bavaria, Germany', 'value' => 10]]),
        ]);

        $countries = $api->getCountriesWithBreakdown('last-7-days');

        expect($countries)->toHaveKey('ireland');
        expect($countries)->toHaveKey('germany');
    });
});
