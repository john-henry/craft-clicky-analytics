<?php

/**
 * Pest harness for the Clicky Analytics plugin.
 *
 * Feature tests cover the pure mapping/lookup helpers and model validation;
 * no Craft application needed. Anything that reaches Craft::$app (the site time
 * zone, Craft::t(), App::parseEnv(), or the plugin's settings singleton) lives
 * under Integration/. Tests that depend on the active colour scheme set it
 * explicitly, so they do not assume the environment's configured value.
 *
 * The Api service is driven two ways. ApiTest seeds the response cache so
 * _request() returns before it reaches Guzzle, driving the public methods with
 * synthetic Clicky payloads and no network. ApiHttpErrorsTest instead injects a
 * mocked Guzzle client via Api::setHttpClient() to exercise the real request and
 * error-handling paths (timeouts, non-JSON bodies, Clicky {error} responses).
 *
 * Run from the repo root with `ddev exec composer test:ca`, which passes the
 * --test-directory flag this file needs to be loaded at all.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\services\Api;
use markhuot\craftpest\test\RefreshesDatabase;
use markhuot\craftpest\test\TestCase;
use yii\caching\ArrayCache;

uses(
    TestCase::class,
    RefreshesDatabase::class,
)->in('Integration');

uses()->beforeEach(function() {
    // RefreshesDatabase wraps each test in a transaction it rolls back on
    // teardown, and that binding only engages when the suite is run with
    // --test-directory. If it ever stops binding, no transaction is open here
    // and every test would commit to the dev database.
    if (Craft::$app->getDb()->getTransaction() === null) {
        throw new RuntimeException(
            'No open database transaction: RefreshesDatabase did not bind, so tests would '
            . 'commit to the dev database. Run the suite via `composer test:ca`.'
        );
    }

    // The transaction only covers what it can roll back; MySQL commits
    // implicitly on ALTER TABLE, which a Field factory triggers. So the database
    // itself has to be the test one. phpunit.xml.dist pins it, and craft-pest
    // reads that file from the working directory, so running from anywhere but
    // the repo root leaves the pin unapplied and Craft on the dev database.
    $database = Craft::$app->getDb()->createCommand('SELECT DATABASE()')->queryScalar();

    if ($database !== 'db_test') {
        throw new RuntimeException(sprintf(
            'Refusing to run: connected to database "%s", expected "db_test". Run the suite '
            . 'from the repo root via `composer test:ca`.',
            $database,
        ));
    }
})->in('Integration');

// Tests swap in an in-memory cache and a mocked HTTP client; put Craft's own
// cache and a real client back afterwards, so nothing leaks into later tests.
uses()->beforeEach(function() {
    $this->originalCache = Craft::$app->getCache();
})->afterEach(function() {
    Craft::$app->set('cache', $this->originalCache);
    ClickyAnalytics::getInstance()->getApi()->setHttpClient(null);
})->in('Integration');

/**
 * Configures known credentials, a fresh in-memory cache, and an Api service
 * whose HTTP client is backed by the given mock responses/exceptions.
 *
 * @param array<int, Response|\Throwable> $responses Queued in call order.
 */
function clickyApiWithMock(array $responses): Api
{
    // Every request the mock answers is recorded here, for tests that check
    // what was sent.
    $GLOBALS['clickyRequests'] = [];

    Craft::$app->set('cache', new ArrayCache());

    $settings = ClickyAnalytics::getInstance()->getSettings();
    $settings->siteId = '12345';
    $settings->siteKey = 'test-sitekey';
    $settings->cacheDuration = 300;

    $api = ClickyAnalytics::getInstance()->getApi();
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($GLOBALS['clickyRequests']));
    $api->setHttpClient(new Client(['handler' => $stack]));

    return $api;
}


/**
 * Returns the query parameters of each request the mocked client was sent.
 *
 * @return array<int, array<string, string>>
 */
function clickyRequestQueries(): array
{
    return array_map(static function(array $entry): array {
        parse_str($entry['request']->getUri()->getQuery(), $query);
        return $query;
    }, $GLOBALS['clickyRequests'] ?? []);
}

/**
 * A Clicky response carrying one tally value.
 */
function clickyTallyResponse(string $type, int $value): Response
{
    return new Response(200, [], json_encode([
        ['type' => $type, 'dates' => [['date' => '2026-09-25', 'items' => [['value' => (string)$value]]]]],
    ]));
}
