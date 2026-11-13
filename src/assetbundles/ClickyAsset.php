<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Clicky Analytics control panel asset bundle.
 *
 * Registers the widget and field styles, and the script behind the Top
 * Countries drilldown.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class ClickyAsset extends AssetBundle
{
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
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/clicky.css',
        ];

        $this->js = [
            'js/widgets.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     *
     * @param View $view The view the bundle is registered on.
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        $view->registerTranslations('clicky-analytics', [
            'Loading…',
            'Couldn’t load this country.',
            'Request failed.',
        ]);
    }
}
