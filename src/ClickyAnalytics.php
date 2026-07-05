<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\console\Application as ConsoleApplication;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Dashboard;
use craft\services\Fields;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use johnhenry\clickyanalytics\fields\PageStats;
use johnhenry\clickyanalytics\helpers\DateRanges;
use johnhenry\clickyanalytics\helpers\Palette;
use johnhenry\clickyanalytics\helpers\TrackingCode;
use johnhenry\clickyanalytics\models\Settings;
use johnhenry\clickyanalytics\services\Api;
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
use yii\base\InvalidConfigException;

/**
 * Clicky Analytics plugin.
 *
 * Brings Clicky web analytics into the Craft Control Panel: a set of native
 * dashboard widgets and a per-entry page-stats field, fed by the Clicky stats
 * API through the {@see Api} service. Credentials are stored in the plugin
 * settings with environment-variable support.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 *
 * @property-read Api $api
 * @property-read Settings $settings
 * @method Settings getSettings()
 */
class ClickyAnalytics extends BasePlugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ClickyAnalytics The plugin instance.
     */
    public static ClickyAnalytics $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether the plugin has settings.
     */
    public bool $hasCpSettings = true;

    /**
     * @var string The plugin's schema version.
     */
    public string $schemaVersion = '1.0.0';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return array The plugin's component configuration.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function config(): array
    {
        return [
            'components' => [
                'api' => Api::class,
            ],
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return void
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerWidgets();
        $this->_registerFieldTypes();
        $this->_registerTwigVariable();
        $this->_registerPermissions();

        if (!Craft::$app instanceof ConsoleApplication) {
            $this->_registerTrackingCode();
        }
    }

    /**
     * Returns the Clicky API service.
     *
     * @return Api The API service.
     * @throws InvalidConfigException
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getApi(): Api
    {
        $component = $this->get('api');
        assert($component instanceof Api);
        return $component;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return Model|null The settings model.
     * @author JohnHenry <info@johnhenry.ie>
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
     * @author JohnHenry <info@johnhenry.ie>
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

    // Private Methods
    // =========================================================================

    /**
     * Registers the plugin's dashboard widgets.
     *
     * @return void
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _registerWidgets(): void
    {
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = ReportWidget::class;
                $event->types[] = LiveVisitors::class;
                $event->types[] = CurrentVisitors::class;
                $event->types[] = Overview::class;
                $event->types[] = NewVsReturning::class;
                $event->types[] = TopPages::class;
                $event->types[] = TopSources::class;
                $event->types[] = TopReferrers::class;
                $event->types[] = SearchEngines::class;
                $event->types[] = TopCountries::class;
                $event->types[] = TopBrowsers::class;
                $event->types[] = OperatingSystems::class;
                $event->types[] = Devices::class;
                $event->types[] = EntryPages::class;
                $event->types[] = ExitPages::class;
                $event->types[] = Downloads::class;
                $event->types[] = Goals::class;
                $event->types[] = Campaigns::class;
                $event->types[] = Cities::class;
                $event->types[] = Regions::class;
                $event->types[] = ScreenResolutions::class;
            }
        );
    }

    /**
     * Registers the plugin's field types.
     *
     * @return void
     * @author JohnHenry <info@johnhenry.ie>
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
     * @author JohnHenry <info@johnhenry.ie>
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
     * @author JohnHenry <info@johnhenry.ie>
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
        $siteId = $settings->getSiteId();

        if (!$settings->injectTrackingCode || $siteId === null) {
            return;
        }

        Craft::$app->getView()->registerHtml(TrackingCode::html($siteId, $settings->trackNoScript));
    }

    /**
     * Registers the plugin's user permissions.
     *
     * @return void
     * @author JohnHenry <info@johnhenry.ie>
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
                        'clicky-analytics:addWidgets' => [
                            'label' => Craft::t('clicky-analytics', 'Add Clicky Analytics dashboard widgets'),
                        ],
                    ],
                ];
            }
        );
    }
}
