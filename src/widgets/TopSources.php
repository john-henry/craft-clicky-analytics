<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Top Sources widget.
 *
 * Shows the top traffic sources for the widget's date range.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class TopSources extends BaseListWidget
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
        return Craft::t('clicky-analytics', 'Clicky Analytics: Top Sources');
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
        return Craft::t('clicky-analytics', 'Top Sources');
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
        return 'clicky-analytics/_components/widgets/_list';
    }

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function bodyData(): array
    {
        return [
            'rows' => $this->api()->getTopSources($this->resolveRange(), $this->limit),
            'accent' => Palette::accent('sources'),
        ];
    }
}
