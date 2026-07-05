<?php

/**
 * Coverage for the Settings env-aware getters, which resolve $ENV_VAR references
 * through App::parseEnv() and therefore need a Craft application.
 */

use johnhenry\clickyanalytics\models\Settings;

// ---------------------------------------------------------------------------
// Literal values
// ---------------------------------------------------------------------------

describe('Settings getters: literal values', function () {
    it('returns the literal Site ID when no env syntax is used', function () {
        $settings = new Settings(['siteId' => '100861234']);

        expect($settings->getSiteId())->toBe('100861234');
    });

    it('returns the literal Sitekey when no env syntax is used', function () {
        $settings = new Settings(['siteKey' => 'abc123def456']);

        expect($settings->getSiteKey())->toBe('abc123def456');
    });

    it('returns null for an unset Site ID', function () {
        expect((new Settings())->getSiteId())->toBeNull();
    });

    it('returns null for an empty Site ID', function () {
        expect((new Settings(['siteId' => '']))->getSiteId())->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// Environment variable resolution
// ---------------------------------------------------------------------------

describe('Settings getters: env resolution', function () {
    it('resolves a $ENV_VAR reference for the Site ID', function () {
        putenv('CLICKY_TEST_SITEID=55512345');
        $_SERVER['CLICKY_TEST_SITEID'] = '55512345';

        $settings = new Settings(['siteId' => '$CLICKY_TEST_SITEID']);

        expect($settings->getSiteId())->toBe('55512345');

        putenv('CLICKY_TEST_SITEID');
        unset($_SERVER['CLICKY_TEST_SITEID']);
    });

    it('resolves a $ENV_VAR reference for the Sitekey', function () {
        putenv('CLICKY_TEST_SITEKEY=secret-key-value');
        $_SERVER['CLICKY_TEST_SITEKEY'] = 'secret-key-value';

        $settings = new Settings(['siteKey' => '$CLICKY_TEST_SITEKEY']);

        expect($settings->getSiteKey())->toBe('secret-key-value');

        putenv('CLICKY_TEST_SITEKEY');
        unset($_SERVER['CLICKY_TEST_SITEKEY']);
    });
});
