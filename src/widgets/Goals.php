<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Goals widget.
 *
 * Shows the top goal completions for the widget's date range.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class Goals extends BaseListWidget
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Goals');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Goals');
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
            'rows' => ClickyAnalytics::getInstance()->getApi()->getTopGoals($this->resolveRange(), $this->limit),
            'accent' => Palette::accent('goals'),
        ];
    }
}
