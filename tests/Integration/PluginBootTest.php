<?php

/**
 * The plugin's wiring lives in a trait; this is the check that it is all still
 * attached to the class that uses it.
 */

use craft\events\RegisterComponentTypesEvent;
use craft\services\Dashboard;
use craft\services\Fields;
use craft\services\UserPermissions;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\fields\PageStats;
use johnhenry\clickyanalytics\models\Settings;
use johnhenry\clickyanalytics\services\Api;
use johnhenry\clickyanalytics\widgets\Overview;
use johnhenry\clickyanalytics\widgets\ReportWidget;

describe('Plugin wiring', function() {
    it('resolves the api service both ways', function() {
        $plugin = ClickyAnalytics::getInstance();

        expect($plugin->getApi())->toBeInstanceOf(Api::class)
            ->and($plugin->api)->toBeInstanceOf(Api::class);
    });

    it('builds its settings model', function() {
        expect(ClickyAnalytics::getInstance()->getSettings())->toBeInstanceOf(Settings::class);
    });

    it('renders the settings screen', function() {
        Craft::$app->getView()->setTemplateMode(craft\web\View::TEMPLATE_MODE_CP);

        // settingsHtml() is protected: Craft reaches it through the settings
        // response, and the trait is what has to still provide it.
        $method = new ReflectionMethod(ClickyAnalytics::class, 'settingsHtml');
        $html = $method->invoke(ClickyAnalytics::getInstance());

        expect($html)->toBeString()
            ->and($html)->toContain('Site ID');
    });

    it('registers every dashboard widget', function() {
        $types = Craft::$app->getDashboard()->getAllWidgetTypes();

        expect($types)->toContain(Overview::class)
            ->and($types)->toContain(ReportWidget::class);
    });

    it('registers the page stats field type', function() {
        expect(Craft::$app->getFields()->getAllFieldTypes())->toContain(PageStats::class);
    });

    it('registers its user permission', function() {
        $all = Craft::$app->getUserPermissions()->getAllPermissions();

        expect(json_encode($all))->toContain('clicky-analytics:addWidgets');
    });

    it('exposes the twig variable', function() {
        expect(Craft::$app->getView()->getTwig()->getGlobals()['craft']->clickyAnalytics)
            ->toBeInstanceOf(johnhenry\clickyanalytics\variables\ClickyVariable::class);
    });
});
