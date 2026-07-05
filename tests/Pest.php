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
 * Run from the parent Craft project (not from inside the plugin folder):
 *
 *   ddev exec vendor/bin/pest plugins/craft-clicky-analytics/tests \
 *     --test-directory=plugins/craft-clicky-analytics/tests
 */

use markhuot\craftpest\test\RefreshesDatabase;
use markhuot\craftpest\test\TestCase;

uses(
    TestCase::class,
    RefreshesDatabase::class,
)->in('Integration');
