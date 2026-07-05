<?php

/**
 * HTTP coverage for CountryController::actionIndex(), the Top Countries
 * drilldown endpoint.
 *
 * These drive the action through a real (CP) request so the permission gate,
 * the required-param guard and the API-failure fallback are all exercised as a
 * browser would hit them. The URL carries the `admin/` cpTrigger prefix so
 * craft-pest flags the request as a CP request (requireCpRequest()). The guard
 * methods throw, so the gate tests assert the thrown exception.
 */

use johnhenry\clickyanalytics\ClickyAnalytics;
use markhuot\craftpest\factories\User as UserFactory;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

// ---------------------------------------------------------------------------
// Permission gate
// ---------------------------------------------------------------------------

describe('CountryController::actionIndex permission', function () {
    it('refuses a user without the add-widgets permission', function () {
        $this->actingAs(UserFactory::factory()->create());

        $this->http('get', 'admin/actions/clicky-analytics/country?country=Ireland')
            ->addHeader('Accept', 'application/json')
            ->send();
    })->throws(ForbiddenHttpException::class);
});

// ---------------------------------------------------------------------------
// Input handling
// ---------------------------------------------------------------------------

describe('CountryController::actionIndex input', function () {
    it('requires the country param', function () {
        $this->actingAs(UserFactory::factory()->admin(true)->create());

        $this->http('get', 'admin/actions/clicky-analytics/country')
            ->addHeader('Accept', 'application/json')
            ->send();
    })->throws(BadRequestHttpException::class);
});

// ---------------------------------------------------------------------------
// API-failure fallback
// ---------------------------------------------------------------------------

describe('CountryController::actionIndex fallback', function () {
    it('returns an html payload, falling back to an empty breakdown when the API fails', function () {
        $this->actingAs(UserFactory::factory()->admin(true)->create());

        // No credentials: getCountryBreakdown() throws "not configured", which the
        // controller swallows into an empty breakdown rather than a 500.
        $settings = ClickyAnalytics::getInstance()->getSettings();
        $settings->siteId = null;
        $settings->siteKey = null;

        $response = $this->http('get', 'admin/actions/clicky-analytics/country?country=Ireland')
            ->addHeader('Accept', 'application/json')
            ->send();

        $response->assertStatus(200);
        expect($response->getJsonContent()['html'])->toContain('clicky-drill__grid');
    });
});
