<?php

/**
 * HTTP coverage for SettingsController::actionTest(), the "Test connection"
 * endpoint on the settings screen.
 *
 * These drive the action through a real (CP) POST so the admin gate, the POST
 * requirement and the JSON contract are exercised end to end. The empty-
 * credential path short-circuits without a network call; the success path
 * injects a mocked Guzzle client via Api::setHttpClient() so no real request to
 * Clicky is made. The URL carries the `admin/` cpTrigger prefix so craft-pest
 * flags the request as a CP request (requireCpRequest()).
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use johnhenry\clickyanalytics\ClickyAnalytics;
use markhuot\craftpest\factories\User as UserFactory;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;

// The success case injects a mock HTTP client into the shared Api singleton;
// reset it so the mock never leaks into a later test.
afterEach(function () {
    ClickyAnalytics::getInstance()->getApi()->setHttpClient(null);
});

// ---------------------------------------------------------------------------
// Admin gate
// ---------------------------------------------------------------------------

describe('SettingsController::actionTest admin gate', function () {
    it('refuses a non-admin user', function () {
        $this->actingAs(UserFactory::factory()->create());

        $this->postJson('admin/actions/clicky-analytics/settings/test', [
            'siteId' => '12345',
            'siteKey' => 'test-sitekey',
        ]);
    })->throws(ForbiddenHttpException::class);
});

// ---------------------------------------------------------------------------
// Request method
// ---------------------------------------------------------------------------

describe('SettingsController::actionTest method', function () {
    it('rejects a non-POST request', function () {
        $this->actingAs(UserFactory::factory()->admin(true)->create());

        $this->http('get', 'admin/actions/clicky-analytics/settings/test')
            ->addHeader('Accept', 'application/json')
            ->send();
    })->throws(MethodNotAllowedHttpException::class);
});

// ---------------------------------------------------------------------------
// Connection outcome
// ---------------------------------------------------------------------------

describe('SettingsController::actionTest outcome', function () {
    it('reports failure when either credential is empty', function () {
        $this->actingAs(UserFactory::factory()->admin(true)->create());

        $json = $this->postJson('admin/actions/clicky-analytics/settings/test', [
            'siteId' => '',
            'siteKey' => '',
        ])->getJsonContent();

        expect($json['success'])->toBeFalse();
    });

    it('reports success for valid credentials over a mocked Clicky API', function () {
        $this->actingAs(UserFactory::factory()->admin(true)->create());

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                ['type' => 'visitors', 'dates' => [['items' => [['value' => 1]]]]],
            ])),
        ]);
        ClickyAnalytics::getInstance()->getApi()
            ->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $json = $this->postJson('admin/actions/clicky-analytics/settings/test', [
            'siteId' => '12345',
            'siteKey' => 'test-sitekey',
        ])->getJsonContent();

        expect($json['success'])->toBeTrue();
    });
});
