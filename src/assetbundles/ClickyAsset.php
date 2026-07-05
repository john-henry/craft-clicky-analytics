<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Clicky Analytics control panel asset bundle.
 *
 * Registers the dashboard's styles and the small script that swaps the dashboard
 * body when the date-range picker changes.
 *
 * @author JohnHenry <info@johnhenry.ie>
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
     * @author JohnHenry <info@johnhenry.ie>
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
            'js/clicky.js',
        ];

        parent::init();
    }
}
