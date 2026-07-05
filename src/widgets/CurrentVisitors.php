<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;

/**
 * Current Visitors widget.
 *
 * Shows the number of visitors currently online, per Clicky's real-time tally.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class CurrentVisitors extends BaseWidget
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Current Visitors');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Visitors Online');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/CurrentVisitors/body';
    }

    /**
     * @inheritdoc
     */
    protected function bodyData(): array
    {
        return [
            'visitorsOnline' => ClickyAnalytics::getInstance()->getApi()->getVisitorsOnline(),
        ];
    }
}
