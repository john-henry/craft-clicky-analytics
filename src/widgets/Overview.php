<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Overview widget.
 *
 * Shows the headline tallies (visitors, actions, bounce rate, average time) for
 * the widget's date range, with a visitors-over-time sparkline.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class Overview extends BaseStatsWidget
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Overview');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Overview');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/Overview/body';
    }

    /**
     * @inheritdoc
     */
    protected function bodyData(): array
    {
        $api = ClickyAnalytics::getInstance()->getApi();
        $resolved = $this->resolved();

        return [
            'overview' => $api->getOverview($resolved['date'], $resolved['previous']),
            'series' => $api->getDailySeries('visitors', $resolved['date']),
            'accent' => Palette::primary(),
            'soft' => Palette::soft(),
        ];
    }
}
