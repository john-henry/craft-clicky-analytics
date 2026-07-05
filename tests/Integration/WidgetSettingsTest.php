<?php

/**
 * Regression coverage for widget settings persistence.
 *
 * Settings properties declared on an abstract base widget (dateRange on
 * BaseStatsWidget, limit on BaseListWidget) are dropped by Craft's default
 * settingsAttributes(), which excludes properties whose declaring class is
 * abstract. Each base re-includes its own property; these tests prove the
 * settings now serialise (and so persist) instead of reverting to the default.
 */

use johnhenry\clickyanalytics\widgets\Overview;
use johnhenry\clickyanalytics\widgets\TopPages;

// ---------------------------------------------------------------------------
// settingsAttributes() includes inherited settings
// ---------------------------------------------------------------------------

describe('Widget settingsAttributes()', function () {
    it('includes the date range on a date-ranged widget', function () {
        expect((new Overview())->settingsAttributes())->toContain('dateRange');
    });

    it('includes both date range and limit on a list widget', function () {
        $attributes = (new TopPages())->settingsAttributes();

        expect($attributes)->toContain('dateRange');
        expect($attributes)->toContain('limit');
    });
});

// ---------------------------------------------------------------------------
// getSettings() round-trips the values (the persisted payload)
// ---------------------------------------------------------------------------

describe('Widget getSettings()', function () {
    it('serialises a chosen date range so it persists', function () {
        $widget = new Overview();
        $widget->dateRange = 'last-28-days';

        expect($widget->getSettings())->toHaveKey('dateRange', 'last-28-days');
    });

    it('serialises both date range and limit on a list widget', function () {
        $widget = new TopPages();
        $widget->dateRange = 'last-90-days';
        $widget->limit = 25;

        $settings = $widget->getSettings();

        expect($settings)->toHaveKey('dateRange', 'last-90-days');
        expect($settings)->toHaveKey('limit', 25);
    });

    it('round-trips a saved date range back onto a fresh widget instance', function () {
        $saved = (new Overview(['dateRange' => 'yesterday']))->getSettings();

        // Re-hydrate as Craft does when loading a widget from the database.
        $reloaded = new Overview($saved);

        expect($reloaded->dateRange)->toBe('yesterday');
    });
});
