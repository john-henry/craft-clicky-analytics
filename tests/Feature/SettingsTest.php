<?php

use johnhenry\clickyanalytics\models\Settings;

// ---------------------------------------------------------------------------
// Defaults
// ---------------------------------------------------------------------------

describe('Settings defaults', function () {
    it('defaults the date range, cache duration and colour scheme', function () {
        $settings = new Settings();

        expect($settings->defaultDateRange)->toBe('last-7-days');
        expect($settings->cacheDuration)->toBe(300);
        expect($settings->colorScheme)->toBe('clicky');
    });

    it('defaults tracking and layout toggles', function () {
        $settings = new Settings();

        expect($settings->injectTrackingCode)->toBeFalse();
        expect($settings->trackNoScript)->toBeTrue();
        expect($settings->compactDensity)->toBeFalse();
        expect($settings->slimBarStyle)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// UI modifier classes
// ---------------------------------------------------------------------------

describe('Settings::getUiModifierClass()', function () {
    it('returns the comfortable, full-bar classes by default', function () {
        expect((new Settings())->getUiModifierClass())
            ->toBe('clicky--density-comfortable clicky--bar-full');
    });

    it('switches to the compact, slim-bar classes when both are on', function () {
        $settings = new Settings(['compactDensity' => true, 'slimBarStyle' => true]);

        expect($settings->getUiModifierClass())
            ->toBe('clicky--density-compact clicky--bar-slim');
    });
});

// ---------------------------------------------------------------------------
// Required credentials
// ---------------------------------------------------------------------------

describe('Settings validation: credentials', function () {
    it('requires a site ID', function () {
        $settings = new Settings(['siteId' => null, 'siteKey' => 'abc']);

        expect($settings->validate(['siteId']))->toBeFalse();
        expect($settings->getErrors('siteId'))->not->toBeEmpty();
    });

    it('requires a site key', function () {
        $settings = new Settings(['siteId' => '123', 'siteKey' => null]);

        expect($settings->validate(['siteKey']))->toBeFalse();
        expect($settings->getErrors('siteKey'))->not->toBeEmpty();
    });

    it('passes when both credentials are present', function () {
        $settings = new Settings(['siteId' => '123', 'siteKey' => 'abc']);

        expect($settings->validate())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Cache duration
// ---------------------------------------------------------------------------

describe('Settings validation: cacheDuration', function () {
    it('rejects a negative cache duration', function () {
        $settings = new Settings(['siteId' => '123', 'siteKey' => 'abc', 'cacheDuration' => -1]);

        expect($settings->validate(['cacheDuration']))->toBeFalse();
    });

    it('accepts a zero cache duration', function () {
        $settings = new Settings(['siteId' => '123', 'siteKey' => 'abc', 'cacheDuration' => 0]);

        expect($settings->validate(['cacheDuration']))->toBeTrue();
    });

    it('accepts a positive cache duration', function () {
        $settings = new Settings(['siteId' => '123', 'siteKey' => 'abc', 'cacheDuration' => 600]);

        expect($settings->validate(['cacheDuration']))->toBeTrue();
    });
});
