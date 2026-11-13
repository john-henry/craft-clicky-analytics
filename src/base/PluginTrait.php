<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\base;

use Craft;
use craft\base\Model;
use craft\db\Table;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Dashboard;
use craft\services\Fields;
use craft\services\Mcp;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use CraftCms\Cms\Mcp\CapabilityRegistry;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\fields\PageStats;
use johnhenry\clickyanalytics\helpers\DateRanges;
use johnhenry\clickyanalytics\helpers\Palette;
use johnhenry\clickyanalytics\helpers\TrackingCode;
use johnhenry\clickyanalytics\mcp\Capabilities;
use johnhenry\clickyanalytics\models\Settings;
use johnhenry\clickyanalytics\variables\ClickyVariable;
use johnhenry\clickyanalytics\widgets\Campaigns;
use johnhenry\clickyanalytics\widgets\Cities;
use johnhenry\clickyanalytics\widgets\CurrentVisitors;
use johnhenry\clickyanalytics\widgets\Devices;
use johnhenry\clickyanalytics\widgets\Downloads;
use johnhenry\clickyanalytics\widgets\EntryPages;
use johnhenry\clickyanalytics\widgets\ExitPages;
use johnhenry\clickyanalytics\widgets\Goals;
use johnhenry\clickyanalytics\widgets\LiveVisitors;
use johnhenry\clickyanalytics\widgets\NewVsReturning;
use johnhenry\clickyanalytics\widgets\OperatingSystems;
use johnhenry\clickyanalytics\widgets\Overview;
use johnhenry\clickyanalytics\widgets\Regions;
use johnhenry\clickyanalytics\widgets\ReportWidget;
use johnhenry\clickyanalytics\widgets\ScreenResolutions;
use johnhenry\clickyanalytics\widgets\SearchEngines;
use johnhenry\clickyanalytics\widgets\TopBrowsers;
use johnhenry\clickyanalytics\widgets\TopCountries;
use johnhenry\clickyanalytics\widgets\TopPages;
use johnhenry\clickyanalytics\widgets\TopReferrers;
use johnhenry\clickyanalytics\widgets\TopSources;
use yii\base\Event;

/**
 * PluginTrait
 *
 * Wires the plugin's widgets, field type, Twig variable, MCP tools, front-end
 * tracking code and permissions, and owns its settings lifecycle. Keeps the main plugin
 * class a thin shell: the listeners here only hand work to the Api service and
 * never carry reporting logic themselves.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.2
 */
trait PluginTrait
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return Model|null The settings model.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     *
     * @return string|null The rendered settings HTML.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('clicky-analytics/settings', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
            'dateRangeOptions' => DateRanges::relativeOptions(),
            'colorSchemes' => Palette::swatches(),
            'overrides' => array_keys(Craft::$app->getConfig()->getConfigFromFile('clicky-analytics')),
        ]);
    }

    /**
     * Removes the plugin's dashboard widgets once it's uninstalled, so users
     * aren't left with widgets whose class no longer exists.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    protected function afterUninstall(): void
    {
        Craft::$app->getDb()->createCommand()
            ->delete(Table::WIDGETS, ['type' => self::_widgetTypes()])
            ->execute();

        parent::afterUninstall();
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the plugin's dashboard widget classes, in the order they're offered.
     *
     * @return string[] The widget classes.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private static function _widgetTypes(): array
    {
        return [
            ReportWidget::class,
            LiveVisitors::class,
            CurrentVisitors::class,
            Overview::class,
            NewVsReturning::class,
            TopPages::class,
            TopSources::class,
            TopReferrers::class,
            SearchEngines::class,
            TopCountries::class,
            TopBrowsers::class,
            OperatingSystems::class,
            Devices::class,
            EntryPages::class,
            ExitPages::class,
            Downloads::class,
            Goals::class,
            Campaigns::class,
            Cities::class,
            Regions::class,
            ScreenResolutions::class,
        ];
    }

    /**
     * Adds "sitekey" to the words Craft treats as secret, so a Clicky Sitekey
     * in an environment variable is masked in logged request context and the
     * CP's environment-variable suggestions. Craft only masks names containing
     * one of its words, and `CLICKY_SITEKEY` has none.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _registerSensitiveKeywords(): void
    {
        $security = Craft::$app->getSecurity();

        if (!in_array('sitekey', $security->sensitiveKeywords, true)) {
            $security->sensitiveKeywords[] = 'sitekey';
        }
    }

    /**
     * Registers the plugin's dashboard widgets.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerWidgets(): void
    {
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                array_push($event->types, ...self::_widgetTypes());
            }
        );
    }

    /**
     * Registers the plugin's field types.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerFieldTypes(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = PageStats::class;
            }
        );
    }

    /**
     * Registers the `craft.clickyAnalytics` Twig variable.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerTwigVariable(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('clickyAnalytics', ClickyVariable::class);
            }
        );
    }

    /**
     * Queues the Clicky tracking snippet for output just before `</body>` on
     * real front-end page loads, when enabled in the settings. Skipped in
     * devMode (so local development never pollutes live Clicky stats) and on
     * Live Preview / share-token requests (so editors previewing a draft aren't
     * counted as visitors).
     *
     * Note that devMode only covers local development. Staging usually runs with
     * devMode off, so set `injectTrackingCode` to `false` for that environment
     * in `config/clicky-analytics.php` if you don't want staging traffic counted.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerTrackingCode(): void
    {
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            return;
        }

        $request = Craft::$app->getRequest();

        if (!$request->getIsSiteRequest() || $request->getIsPreview()) {
            return;
        }

        $settings = $this->getSettings();
        $siteId = $settings->getSiteId(Craft::$app->getSites()->getCurrentSite()->handle);

        if (!$settings->injectTrackingCode || $siteId === null) {
            return;
        }

        Craft::$app->getView()->registerHtml(TrackingCode::html($siteId, $settings->trackNoScript));
    }

    /**
     * Offers the plugin's tools to Craft's MCP servers. Craft 5.12 collects
     * them through an event on its MCP service; Craft 6 has a registry in the
     * service container instead. Does nothing on Craft versions before 5.12,
     * which have neither.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.2.0
     */
    private function _registerMcpCapabilities(): void
    {
        if (class_exists(Mcp::class)) {
            Event::on(
                Mcp::class,
                Mcp::EVENT_REGISTER_CAPABILITY_TYPES,
                static function(RegisterComponentTypesEvent $event) {
                    $event->types[] = Capabilities::class;
                }
            );

            return;
        }

        if (class_exists(CapabilityRegistry::class)) {
            app(CapabilityRegistry::class)->register(Capabilities::class);
        }
    }

    /**
     * Registers the plugin's user permissions.
     *
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            static function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('clicky-analytics', 'Clicky Analytics'),
                    'permissions' => [
                        ClickyAnalytics::PERMISSION_VIEW_ANALYTICS => [
                            'label' => Craft::t('clicky-analytics', 'View Clicky Analytics data and add its dashboard widgets'),
                        ],
                    ],
                ];
            }
        );
    }
}
