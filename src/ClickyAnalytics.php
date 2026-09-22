<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\console\Application as ConsoleApplication;
use johnhenry\clickyanalytics\base\PluginTrait;
use johnhenry\clickyanalytics\models\Settings;
use johnhenry\clickyanalytics\services\Api;
use yii\base\InvalidConfigException;

/**
 * Clicky Analytics plugin.
 *
 * Brings Clicky web analytics into the Craft Control Panel: a set of native
 * dashboard widgets and a per-entry page-stats field, fed by the Clicky stats
 * API through the {@see Api} service. Credentials are stored in the plugin
 * settings with environment-variable support.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 *
 * @property-read Api $api
 * @property-read Settings $settings
 * @method Settings getSettings()
 */
class ClickyAnalytics extends BasePlugin
{
    // Traits
    // =========================================================================

    use PluginTrait;

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
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getApi(): Api
    {
        $component = $this->get('api');
        assert($component instanceof Api);
        return $component;
    }
}
