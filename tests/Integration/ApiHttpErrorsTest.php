<?php

/**
 * Coverage for Api::_request() and Api::ping()'s HTTP error handling.
 *
 * Unlike ApiTest.php (which seeds the response cache to avoid the network
 * entirely), these tests inject a Guzzle client backed by a MockHandler via
 * Api::setHttpClient(), so the real request/response/error-parsing logic in
 * _request() and ping() runs against synthetic HTTP responses - including the
 * failure paths (timeouts, non-JSON bodies, Clicky's {error: …} responses)
 * that a cache-seeding approach can never exercise.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\services\Api;
use yii\caching\ArrayCache;

/**
 * Configures known credentials, a fresh in-memory cache, and an Api service
 * whose HTTP client is backed by the given mock responses/exceptions.
 *
 * @param array<int, Response|\Throwable> $responses Queued in call order.
 */
function clickyApiWithMock(array $responses): Api
{
    Craft::$app->set('cache', new ArrayCache());

    $settings = ClickyAnalytics::getInstance()->getSettings();
    $settings->siteId = '12345';
    $settings->siteKey = 'test-sitekey';
    $settings->cacheDuration = 300;

    $api = ClickyAnalytics::getInstance()->getApi();
    $mock = new MockHandler($responses);
    $api->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

    return $api;
}

// ---------------------------------------------------------------------------
// getStats() / _request() via the real HTTP path
// ---------------------------------------------------------------------------

describe('Api::getStats() over a mocked HTTP client', function () {
    it('parses a successful response', function () {
        $api = clickyApiWithMock([
            new Response(200, [], json_encode([
                ['type' => 'pages', 'dates' => [['items' => [['title' => 'Home', 'value' => 10]]]]],
            ])),
        ]);

        $result = $api->getStats(['pages'], 'today');

        expect($result['pages'][0]['value'])->toBe(10);
    });

    it('throws when the underlying connection fails', function () {
        $api = clickyApiWithMock([
            new ConnectException('Connection timed out', new Request('GET', Api::ENDPOINT)),
        ]);

        $api->getStats(['pages'], 'today');
    })->throws(RuntimeException::class);

    it('throws on a 5xx response with a non-JSON body', function () {
        $api = clickyApiWithMock([
            new Response(500, [], '<html>Internal Server Error</html>'),
        ]);

        $api->getStats(['pages'], 'today');
    })->throws(RuntimeException::class);

    it('throws when Clicky returns an {error: …} payload', function () {
        $api = clickyApiWithMock([
            new Response(200, [], json_encode(['error' => 'Invalid site ID or sitekey'])),
        ]);

        $api->getStats(['pages'], 'today');
    })->throws(RuntimeException::class, 'Invalid site ID or sitekey');

    it('caches a successful response so a second call does not reuse the mock queue', function () {
        // Only one response queued - a second HTTP call would exhaust the
        // MockHandler and throw an OutOfBoundsException, so this only passes
        // if the second getStats() call is served from cache.
        $api = clickyApiWithMock([
            new Response(200, [], json_encode([
                ['type' => 'pages', 'dates' => [['items' => [['title' => 'Home', 'value' => 10]]]]],
            ])),
        ]);

        $api->getStats(['pages'], 'today');
        $second = $api->getStats(['pages'], 'today');

        expect($second['pages'][0]['value'])->toBe(10);
    });
});

// ---------------------------------------------------------------------------
// ping()
// ---------------------------------------------------------------------------

describe('Api::ping()', function () {
    it('fails fast without an HTTP call when either credential is empty', function () {
        // No responses queued - would throw OutOfBoundsException if it tried
        // to make a real request, proving the empty-credential guard runs first.
        $api = clickyApiWithMock([]);

        $result = $api->ping('', 'test-sitekey');

        expect($result['success'])->toBeFalse();
    });

    it('reports failure when the connection fails', function () {
        $api = clickyApiWithMock([
            new ConnectException('Connection timed out', new Request('GET', Api::ENDPOINT)),
        ]);

        $result = $api->ping('12345', 'test-sitekey');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('Connection timed out');
    });

    it('reports failure on a non-JSON response', function () {
        $api = clickyApiWithMock([
            new Response(200, [], 'not json'),
        ]);

        $result = $api->ping('12345', 'test-sitekey');

        expect($result['success'])->toBeFalse();
    });

    it('reports the Clicky-provided message when Clicky returns an error', function () {
        $api = clickyApiWithMock([
            new Response(200, [], json_encode(['error' => 'Invalid site ID or sitekey'])),
        ]);

        $result = $api->ping('12345', 'wrong-sitekey');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toBe('Invalid site ID or sitekey');
    });

    it('reports success on a valid response', function () {
        $api = clickyApiWithMock([
            new Response(200, [], json_encode([
                ['type' => 'visitors', 'dates' => [['items' => [['value' => 1]]]]],
            ])),
        ]);

        $result = $api->ping('12345', 'test-sitekey');

        expect($result['success'])->toBeTrue();
    });
});
