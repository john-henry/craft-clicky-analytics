<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Search Engines widget.
 *
 * Shows the top search engines (by brand, with logos) for the widget's date range.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class SearchEngines extends BaseListWidget
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Top Search Engines');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Top Search Engines');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/_list';
    }

    /**
     * @inheritdoc
     */
    protected function bodyData(): array
    {
        return [
            'rows' => ClickyAnalytics::getInstance()->getApi()->getTopSearchEngines($this->resolveRange(), $this->limit),
            'accent' => Palette::accent('search'),
        ];
    }
}
