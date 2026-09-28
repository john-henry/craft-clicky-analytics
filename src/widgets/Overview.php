<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Overview widget.
 *
 * Shows the headline tallies (visitors, actions, bounce rate, average time) for
 * the widget's date range, with a visitors-over-time sparkline.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class Overview extends BaseStatsWidget
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Overview');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Overview');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/Overview/body';
    }

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function bodyData(): array
    {
        $api = $this->api();
        $resolved = $this->resolved();

        return [
            'overview' => $api->getOverview($resolved['date'], $resolved['previous']),
            'series' => $api->getDailySeries('visitors', $resolved['date']),
            'compactChart' => ($this->colspan ?? 1) < 2,
            'accent' => Palette::primary(),
            'soft' => Palette::soft(),
        ];
    }
}
