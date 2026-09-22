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
