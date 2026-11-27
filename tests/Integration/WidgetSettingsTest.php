<?php

/**
 * Widget settings persistence.
 *
 * Craft's default settingsAttributes() leaves out properties declared on an
 * abstract class, which would drop dateRange (BaseStatsWidget) and limit
 * (BaseListWidget). Each base adds its own property back; these tests pin
 * that the settings serialise, so a saved value survives a reload.
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
