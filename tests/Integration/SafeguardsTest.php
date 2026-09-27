<?php

/**
 * What keeps the Sitekey secret, Clicky data behind the view permission, and
 * pages up when Clicky is slow or down.
 *
 * The Sitekey is in every request URL, so errors must not carry it, even as
 * a chained exception. Craft masks environment variables only by name, so
 * "sitekey" has to be on its list. Craft's own dashboard actions don't check
 * a widget's isSelectable(), so the widgets check the permission themselves.
 * Twig can't catch exceptions, so the template variable returns empty values
 * instead of throwing.
 */

use craft\elements\Entry;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\fields\PageStats;
use johnhenry\clickyanalytics\services\Api;
use johnhenry\clickyanalytics\variables\ClickyVariable;
use johnhenry\clickyanalytics\widgets\CurrentVisitors;
use johnhenry\clickyanalytics\widgets\LiveVisitors;
use johnhenry\clickyanalytics\widgets\ReportWidget;
use markhuot\craftpest\factories\User as UserFactory;

describe('The Sitekey', function () {
    it('isn\'t in a failed request\'s exception, even as a chained one', function () {
        $api = clickyApiWithMock([new Response(401, [], 'Unauthorized')]);

        try {
            $api->getStats(['visitors'], 'today');
            $this->fail('Expected the request to throw.');
        } catch (RuntimeException $e) {
            expect((string)$e)->not->toContain('test-sitekey');
            expect($e->getPrevious())->toBeNull();
        }
    });

    it('is masked by value too, wherever a message quotes it', function () {
        $api = clickyApiWithMock([new ConnectException(
            'Connection to api.clicky.com failed while sending key test-sitekey',
            new Request('GET', 'https://api.clicky.com/api/stats/4'),
        )]);

        try {
            $api->getStats(['visitors'], 'today');
            $this->fail('Expected the request to throw.');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->not->toContain('test-sitekey');
        }
    });

    it('is masked in logged request context, under its old and new variable names', function () {
        $security = Craft::$app->getSecurity();

        expect($security->isSensitive('CLICKY_SITEKEY'))->toBeTrue();
        expect($security->isSensitive('CLICKY_SITE_KEY'))->toBeTrue();
    });
});

describe('A Clicky error', function () {
    it('fails the connection test when Clicky wraps it in a list', function () {
        clickyApiWithMock([new Response(200, [], '[{"error":"Invalid sitekey."}]')]);

        $result = ClickyAnalytics::getInstance()->getApi()->ping('12345', 'wrong-key');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toBe('Invalid sitekey.');
    });

    it('throws, and isn\'t cached as data, when Clicky wraps it in a list', function () {
        $api = clickyApiWithMock([
            new Response(200, [], '[{"error":"Invalid sitekey."}]'),
            clickyTallyResponse('visitors', 7),
        ]);
        ClickyAnalytics::getInstance()->getSettings()->cacheDuration = 0;

        expect(fn() => $api->getStats(['visitors'], 'today'))->toThrow(RuntimeException::class, 'Invalid sitekey.');
        expect($api->getTally('visitors', 'today'))->toBe(7);
    });

    it('stops further calls for a while, instead of waiting on Clicky every time', function () {
        // One response only: a second request would fail the test.
        $api = clickyApiWithMock([new Response(500, [], 'boom')]);

        expect(fn() => $api->getStats(['visitors'], 'today'))->toThrow(RuntimeException::class);
        expect(fn() => $api->getStats(['pages'], 'today'))->toThrow(RuntimeException::class);
        expect(clickyRequestQueries())->toHaveCount(1);
    });
});

describe('A Clicky request', function () {
    it('can\'t have its credentials or type replaced by parameters passed in', function () {
        $api = clickyApiWithMock([clickyTallyResponse('pages', 1)]);

        $api->getStats(['pages'], 'today', ['sitekey' => 'other', 'site_id' => '999', 'type' => 'visitors-list']);

        $query = clickyRequestQueries()[0];
        expect($query['sitekey'])->toBe('test-sitekey');
        expect($query['site_id'])->toBe('12345');
        expect($query['type'])->toBe('pages');
    });

    it('uses a site\'s own credentials when it has them', function () {
        $api = clickyApiWithMock([clickyTallyResponse('pages', 1)]);
        $settings = ClickyAnalytics::getInstance()->getSettings();
        $settings->sites = ['other' => ['siteId' => '555', 'siteKey' => 'other-key']];

        try {
            $api->forSite('other')->getStats(['pages'], 'today');
        } finally {
            $settings->sites = [];
        }

        expect(clickyRequestQueries()[0]['site_id'])->toBe('555');
        expect(clickyRequestQueries()[0]['sitekey'])->toBe('other-key');
    });

    it('uses the Clicky site chosen in a widget\'s settings', function () {
        clickyApiWithMock([clickyTallyResponse('visitors-online', 3)]);
        $this->actingAs(UserFactory::factory()->admin(true)->create());
        $settings = ClickyAnalytics::getInstance()->getSettings();
        $settings->sites = ['other' => ['siteId' => '555', 'siteKey' => 'other-key']];

        try {
            (new CurrentVisitors(['siteHandle' => 'other']))->getBodyHtml();
        } finally {
            $settings->sites = [];
        }

        expect(clickyRequestQueries()[0]['site_id'])->toBe('555');
    });

    it('doesn\'t cache the fields that identify a visitor', function () {
        $api = clickyApiWithMock([new Response(200, [], json_encode([[
            'type' => 'visitors-list',
            'dates' => [['items' => [['ip_address' => '203.0.113.9', 'hostname' => 'host.example', 'latitude' => '51.9', 'time' => '1', 'geolocation' => 'Cork, Ireland']]]],
        ]]))]);

        $api->getRecentVisitors('today', 1);

        $cached = Craft::$app->getCache()->get('clicky-analytics:' . md5(json_encode(['12345', ['visitors-list'], 'today', ['limit' => 1]])));
        $item = $cached[0]['dates'][0]['items'][0];
        expect($item)->not->toHaveKey('ip_address');
        expect($item)->not->toHaveKey('hostname');
        expect($item)->not->toHaveKey('latitude');
        expect($item['geolocation'])->toBe('Cork, Ireland');
    });
});

describe('The view permission', function () {
    it('keeps a widget\'s data from a user without it', function () {
        clickyApiWithMock([]);
        $this->actingAs(UserFactory::factory()->create());

        $widget = new LiveVisitors();

        expect($widget->getBodyHtml())->toBeNull();
        expect($widget->beforeSave(true))->toBeFalse();
    });

    it('keeps the Page Stats field empty for a user without it', function () {
        clickyApiWithMock([]);
        $this->actingAs(UserFactory::factory()->create());

        expect((new PageStats())->getStaticHtml(null, new Entry()))->toBe('');
    });
});

describe('The template variable', function () {
    it('returns empty data instead of throwing when Clicky fails', function () {
        clickyApiWithMock([new Response(500, [], 'boom'), new Response(500, [], 'boom')]);
        $variable = new ClickyVariable();

        expect($variable->topPages())->toBe([]);
        expect($variable->getVisitorsOnline())->toBe(0);
    });

    it('asks Clicky for the path when given a full URL', function () {
        clickyApiWithMock([
            new Response(200, [], json_encode([['type' => 'visitors', 'dates' => [['items' => [['value' => '3']]]]]])),
            new Response(200, [], '[]'),
        ]);

        (new ClickyVariable())->pageStats('https://example.com/blog/my-post?utm=x');

        expect(clickyRequestQueries()[0]['href'])->toBe('/blog/my-post');
    });
});

describe('Api::pathFromUrl()', function () {
    it('keeps only the path, percent-encoding anything outside ASCII', function () {
        expect(Api::pathFromUrl('https://example.com/'))->toBe('/');
        expect(Api::pathFromUrl('https://example.com'))->toBe('/');
        expect(Api::pathFromUrl('https://example.com/café/crème?x=1'))->toBe('/caf%C3%A9/cr%C3%A8me');
    });
});

describe('Settings and widget validation', function () {
    it('wants a numeric Site ID and a known date range and colour scheme', function () {
        $settings = new \johnhenry\clickyanalytics\models\Settings([
            'siteId' => 'abc',
            'siteKey' => 'key',
            'defaultDateRange' => 'forever',
            'colorScheme' => 'neon',
        ]);

        expect($settings->validate())->toBeFalse();
        expect($settings->getErrors())->toHaveKeys(['siteId', 'defaultDateRange', 'colorScheme']);
    });

    it('rejects a Report widget metric or display it doesn\'t offer', function () {
        $widget = new ReportWidget(['metric' => 'organizations', 'display' => 'table']);

        expect($widget->validate())->toBeFalse();
        expect($widget->getErrors())->toHaveKeys(['metric', 'display']);
    });

    it('only lets a widget report on a site with its own Clicky credentials', function () {
        $settings = ClickyAnalytics::getInstance()->getSettings();
        $settings->sites = ['other' => ['siteId' => '555', 'siteKey' => 'other-key'], 'half' => ['siteId' => '777']];

        try {
            expect((new CurrentVisitors(['siteHandle' => 'other']))->validate())->toBeTrue();
            expect((new CurrentVisitors(['siteHandle' => 'half']))->validate())->toBeFalse();
            expect((new CurrentVisitors(['siteHandle' => '']))->validate())->toBeTrue();
        } finally {
            $settings->sites = [];
        }
    });

    it('keeps the Page Stats field out of GraphQL', function () {
        expect((new PageStats())->includeInGqlSchema(new \craft\models\GqlSchema()))->toBeFalse();
    });

    it('can\'t be marked required on the Page Stats field', function () {
        expect(PageStats::isRequirable())->toBeFalse();
    });
});
