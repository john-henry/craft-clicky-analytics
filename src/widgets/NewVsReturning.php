<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * New vs Returning widget.
 *
 * Shows the split between new and returning visitors for the widget's date range.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class NewVsReturning extends BaseStatsWidget
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: New vs Returning');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'New vs Returning');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/NewVsReturning/body';
    }

    /**
     * @inheritdoc
     */
    protected function bodyData(): array
    {
        return [
            'data' => ClickyAnalytics::getInstance()->getApi()->getNewVsReturning($this->resolveRange()),
            'accent' => Palette::primary(),
            'secondary' => Palette::secondary(),
        ];
    }
}
